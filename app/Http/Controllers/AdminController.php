<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use App\Models\AudioFile;
use App\Models\CreditTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        // Add credits to user
        $user->increment('credits', $amount);
        $newBalance = $user->fresh()->credits;

        // Create transaction record
        CreditTransaction::create([
            'user_id' => $user->id,
            'admin_id' => auth()->id(),
            'amount' => $amount,
            'type' => 'admin_add',
            'description' => $description,
            'balance_after' => $newBalance,
        ]);

        return back()->with('success', 
            "{$amount} credits added to {$user->name}. New balance: {$newBalance} credits."
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

        // Check if user has enough credits
        if ($user->credits < $amount) {
            return back()->with('error', 
                "User does not have enough credits. Current balance: {$user->credits} credits."
            );
        }

        // Remove credits from user
        $user->decrement('credits', $amount);
        $newBalance = $user->fresh()->credits;

        // Create transaction record
        CreditTransaction::create([
            'user_id' => $user->id,
            'admin_id' => auth()->id(),
            'amount' => -$amount, // Negative amount for removal
            'type' => 'admin_remove',
            'description' => $description,
            'balance_after' => $newBalance,
        ]);

        return back()->with('success', 
            "{$amount} credits removed from {$user->name}. New balance: {$newBalance} credits."
        );
    }

    public function creditHistory(User $user)
    {
        $transactions = $user->creditTransactions()
            ->with('admin')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.credit-history', compact('user', 'transactions'));
    }
}
