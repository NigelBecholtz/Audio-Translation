<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use App\Models\AudioFile;
use App\Models\CreditTransaction;
use App\Jobs\TranslateCsvJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    public function dashboard()
    {
        // Payment statistics
        $totalRevenue = Payment::where('status', 'completed')->sum('amount');
        $totalPayments = Payment::where('status', 'completed')->count();
        $pendingPayments = Payment::where('status', 'pending')->count();
        $failedPayments = Payment::where('status', 'failed')->count();
        
        // User statistics
        $totalUsers = User::count();
        $usersWithCredits = User::where('credits', '>', 0)->count();
        $usersWithPayments = User::whereHas('payments')->count();
        
        // Audio processing statistics
        $totalAudioFiles = AudioFile::count();
        $completedAudioFiles = AudioFile::where('status', 'completed')->count();
        $failedAudioFiles = AudioFile::where('status', 'failed')->count();
        $processingAudioFiles = AudioFile::where('status', 'processing')->count();
        
        // Recent payments
        $recentPayments = Payment::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Recent users
        $recentUsers = User::orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        // Monthly revenue chart data - works for both MySQL and SQLite
        $monthlyRevenue = Payment::query()
            ->where('status', 'completed')
            ->selectRaw(
                config('database.default') === 'sqlite' 
                    ? "strftime('%Y-%m', created_at) as month"
                    : "DATE_FORMAT(created_at, '%Y-%m') as month"
            )
            ->selectRaw('SUM(amount) as revenue')
            ->selectRaw('COUNT(*) as payments')
            ->groupBy('month')
            ->orderByDesc('month')
            ->limit(12)
            ->get();

        return view('admin.dashboard', compact(
            'totalRevenue',
            'totalPayments',
            'pendingPayments',
            'failedPayments',
            'totalUsers',
            'usersWithCredits',
            'usersWithPayments',
            'totalAudioFiles',
            'completedAudioFiles',
            'failedAudioFiles',
            'processingAudioFiles',
            'recentPayments',
            'recentUsers',
            'monthlyRevenue'
        ));
    }

    public function payments()
    {
        $payments = Payment::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('admin.payments', compact('payments'));
    }

    public function users()
    {
        $users = User::withCount(['payments', 'audioFiles'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('admin.users', compact('users'));
    }

    public function audioFiles()
    {
        $audioFiles = AudioFile::with('user', 'translations')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('admin.audio-files', compact('audioFiles'));
    }

    public function addCredits(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:1000',
            'description' => 'nullable|string|max:255',
        ]);

        $amount = $request->amount;
        $description = $request->description ?? "Credits added by admin";

        // Use database transaction with row-level locking to prevent race conditions
        DB::transaction(function() use ($user, $amount, $description) {
            // Lock the user row to prevent concurrent credit modifications
            $lockedUser = User::lockForUpdate()->find($user->id);
            
            // Add credits to user
            $lockedUser->increment('credits', $amount);
            $newBalance = $lockedUser->fresh()->credits;

            // Create transaction record
            CreditTransaction::create([
                'user_id' => $lockedUser->id,
                'admin_id' => auth()->id(),
                'amount' => $amount,
                'type' => 'admin_add',
                'description' => $description,
                'balance_after' => $newBalance,
            ]);
        });

        $user->refresh();
        return back()->with('success', 
            "{$amount} credits added to {$user->name}. New balance: {$user->credits} credits."
        );
    }

    public function removeCredits(Request $request, User $user)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:1000',
            'description' => 'nullable|string|max:255',
        ]);

        $amount = $request->amount;
        $description = $request->description ?? "Credits removed by admin";

        try {
            // Use database transaction with row-level locking to prevent race conditions
            DB::transaction(function() use ($user, $amount, $description) {
                // Lock the user row to prevent concurrent credit modifications
                $lockedUser = User::lockForUpdate()->find($user->id);
                
                // Check if user has enough credits after lock
                if ($lockedUser->credits < $amount) {
                    throw new \Exception("User does not have enough credits. Current balance: {$lockedUser->credits} credits.");
                }

                // Remove credits from user
                $lockedUser->decrement('credits', $amount);
                $newBalance = $lockedUser->fresh()->credits;

                // Create transaction record
                CreditTransaction::create([
                    'user_id' => $lockedUser->id,
                    'admin_id' => auth()->id(),
                    'amount' => -$amount, // Negative amount for removal
                    'type' => 'admin_remove',
                    'description' => $description,
                    'balance_after' => $newBalance,
                ]);
            });
            
            $user->refresh();
            return back()->with('success', 
                "{$amount} credits removed from {$user->name}. New balance: {$user->credits} credits."
            );
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function creditHistory(User $user)
    {
        $transactions = $user->creditTransactions()
            ->with('admin')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.credit-history', compact('user', 'transactions'));
    }

    /**
     * Show CSV translation page
     */
    public function csvTranslations()
    {
        // Get list of previous translations
        $translations = Storage::disk('public')->files('csv-translations');
        $translationFiles = [];
        
        foreach ($translations as $file) {
            if (str_ends_with($file, '.csv')) {
                $translationFiles[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'size' => Storage::disk('public')->size($file),
                    'modified' => Storage::disk('public')->lastModified($file),
                ];
            }
        }

        // Sort by modified date, newest first
        usort($translationFiles, fn($a, $b) => $b['modified'] - $a['modified']);

        return view('admin.csv-translations', compact('translationFiles'));
    }

    /**
     * Upload and process CSV translation
     */
    public function uploadCsvTranslation(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // Max 10MB
        ]);

        try {
            $file = $request->file('csv_file');
            $originalFilename = $file->getClientOriginalName();
            $filename = time() . '_' . $originalFilename;
            
            // Store uploaded file
            $inputPath = $file->storeAs('csv-translations/input', $filename, 'public');
            
            // Prepare output path
            $outputFilename = time() . '_translated_' . $originalFilename;
            $outputPath = 'csv-translations/' . $outputFilename;

            Log::info('CSV translation upload received', [
                'admin_id' => auth()->id(),
                'filename' => $originalFilename,
                'size' => $file->getSize()
            ]);

            // Dispatch job for async processing
            TranslateCsvJob::dispatch($inputPath, $outputPath, auth()->id());

            return redirect()->route('admin.csv-translations')
                ->with('success', 'CSV file uploaded successfully! Translation is processing in the background. This may take several minutes.');

        } catch (\Exception $e) {
            Log::error('CSV upload failed', [
                'admin_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Download translated CSV file
     */
    public function downloadCsvTranslation(string $filename)
    {
        $path = 'csv-translations/' . $filename;
        
        if (!Storage::disk('public')->exists($path)) {
            return back()->with('error', 'File not found.');
        }

        return Storage::disk('public')->download($path);
    }

    /**
     * Check CSV translation status (AJAX endpoint)
     */
    public function checkCsvStatus(Request $request)
    {
        $filename = $request->input('filename');
        $statusPath = 'csv-translations/' . str_replace('.csv', '_status.json', $filename);
        
        if (Storage::disk('public')->exists($statusPath)) {
            $status = json_decode(Storage::disk('public')->get($statusPath), true);
            return response()->json($status);
        }

        // Check if output file exists (for backward compatibility)
        $outputPath = 'csv-translations/' . $filename;
        if (Storage::disk('public')->exists($outputPath)) {
            return response()->json([
                'status' => 'completed',
                'output_file' => $filename
            ]);
        }

        return response()->json([
            'status' => 'processing'
        ]);
    }

    /**
     * Delete CSV translation file
     */
    public function deleteCsvTranslation(string $filename)
    {
        $path = 'csv-translations/' . $filename;
        
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            
            // Also delete status file if exists
            $statusPath = str_replace('.csv', '_status.json', $path);
            if (Storage::disk('public')->exists($statusPath)) {
                Storage::disk('public')->delete($statusPath);
            }
            
            return back()->with('success', 'File deleted successfully.');
        }

        return back()->with('error', 'File not found.');
    }
}
