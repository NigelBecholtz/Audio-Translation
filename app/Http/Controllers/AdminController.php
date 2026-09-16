<?php

namespace App\Http\Controllers;

use App\Constants\CreditConstants;
use App\Exceptions\InsufficientCreditsException;
use App\Models\AudioFile;
use App\Models\Payment;
use App\Models\User;
use App\Services\CreditService;
use Illuminate\Http\Request;

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

    public function addCredits(Request $request, User $user, CreditService $credits)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:1000',
            'description' => 'nullable|string|max:255',
        ]);

        $amount = (float) $validated['amount'];

        $credits->addCredit(
            $user,
            $amount,
            CreditConstants::TRANSACTION_TYPE_ADMIN_ADD,
            $validated['description'] ?? 'Credits added by admin',
            auth()->id()
        );

        $user->refresh();

        return back()->with('success', "{$amount} credits added to {$user->name}. New balance: {$user->credits} credits.");
    }

    public function removeCredits(Request $request, User $user, CreditService $credits)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:1000',
            'description' => 'nullable|string|max:255',
        ]);

        $amount = (float) $validated['amount'];

        try {
            $credits->removeCredit(
                $user,
                $amount,
                CreditConstants::TRANSACTION_TYPE_ADMIN_REMOVE,
                $validated['description'] ?? 'Credits removed by admin',
                auth()->id()
            );
        } catch (InsufficientCreditsException $e) {
            return back()->with('error', "{$user->name} does not have enough credits. ".$e->getMessage());
        }

        $user->refresh();

        return back()->with('success', "{$amount} credits removed from {$user->name}. New balance: {$user->credits} credits.");
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
