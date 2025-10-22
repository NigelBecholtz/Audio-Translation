@extends('layouts.app')

@section('title', 'Admin - Users')

@section('content')
<div class="px-4 py-6 sm:px-0">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Users</h1>
                    <p class="text-gray-600">Overview of all users</p>
                </div>
                <a href="{{ route('admin.dashboard') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow-lg rounded-xl p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Account Type</label>
                    <select class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All types</option>
                        <option value="free">Free</option>
                        <option value="pay_per_use">Pay per Use</option>
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Credits</label>
                    <select class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All users</option>
                        <option value="with_credits">With credits</option>
                        <option value="no_credits">Without credits</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" placeholder="Name or email..." class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex items-end">
                    <button class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>
                        Filter
                    </button>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white shadow-lg rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                User
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Account Type
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Credits
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Translations
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Payments
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Registered
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($users as $user)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                <i class="fas fa-user text-gray-600"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                            <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($user->subscription_type === 'free') bg-gray-100 text-gray-800
                                        @elseif($user->subscription_type === 'pay_per_use') bg-blue-100 text-blue-800
                                        @elseif($user->subscription_type === 'monthly') bg-green-100 text-green-800
                                        @elseif($user->subscription_type === 'yearly') bg-purple-100 text-purple-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        @if($user->subscription_type === 'free')
                                            <i class="fas fa-gift mr-1"></i>
                                        @elseif($user->subscription_type === 'pay_per_use')
                                            <i class="fas fa-coins mr-1"></i>
                                        @elseif($user->subscription_type === 'monthly')
                                            <i class="fas fa-calendar mr-1"></i>
                                        @elseif($user->subscription_type === 'yearly')
                                            <i class="fas fa-calendar-alt mr-1"></i>
                                        @endif
                                        {{ ucfirst(str_replace('_', ' ', $user->subscription_type)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="text-sm font-medium text-gray-900">{{ $user->credits }}</div>
                                        <div class="ml-2 text-sm text-gray-500">credits</div>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $user->getRemainingTranslations() }} translations left
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $user->audio_files_count }}</div>
                                    <div class="text-sm text-gray-500">audio files</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $user->payments_count }}</div>
                                    <div class="text-sm text-gray-500">payments</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $user->created_at->format('d-m-Y') }}</div>
                                    <div class="text-sm text-gray-500">{{ $user->created_at->diffForHumans() }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button onclick="openCreditModal({{ $user->id }}, '{{ $user->name }}', {{ $user->credits }})" 
                                                class="text-green-600 hover:text-green-900" title="Manage Credits">
                                            <i class="fas fa-coins"></i>
                                        </button>
                                        <a href="{{ route('admin.users.credit-history', $user) }}" 
                                           class="text-blue-600 hover:text-blue-900" title="Credit History">
                                            <i class="fas fa-history"></i>
                                        </a>
                                        <button class="text-orange-600 hover:text-orange-900" title="Edit Account">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="text-gray-500">
                                        <i class="fas fa-users text-4xl mb-4"></i>
                                        <p class="text-lg font-medium">No users found</p>
                                        <p class="text-sm">No users have been registered yet.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($users->hasPages())
                <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    {{ $users->links() }}
                </div>
            @endif
        </div>

        <!-- Summary Stats -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white shadow-lg rounded-xl p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-users text-blue-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Users</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $users->total() }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white shadow-lg rounded-xl p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-coins text-green-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">With Credits</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $users->where('credits', '>', 0)->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white shadow-lg rounded-xl p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-credit-card text-purple-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">With Payments</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $users->where('payments_count', '>', 0)->count() }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white shadow-lg rounded-xl p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-file-audio text-orange-500 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Active Users</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $users->where('audio_files_count', '>', 0)->count() }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Credit Management Modal -->
<div id="creditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Manage Credits</h3>
                <button onclick="closeCreditModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- User Info -->
            <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-600">User: <span id="modalUserName" class="font-medium"></span></p>
                <p class="text-sm text-gray-600">Current balance: <span id="modalUserCredits" class="font-medium text-green-600"></span> credits</p>
            </div>

            <!-- Add Credits Form -->
            <div class="mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Add Credits</h4>
                <form id="addCreditsForm" method="POST">
                    @csrf
                    <div class="flex space-x-2">
                        <input type="number" 
                               name="amount" 
                               step="0.01" 
                               min="0.01" 
                               max="1000" 
                               placeholder="Number of credits"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        <input type="text" 
                               name="description" 
                               placeholder="Description (optional)"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        <button type="submit" 
                                class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Remove Credits Form -->
            <div class="mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Remove Credits</h4>
                <form id="removeCreditsForm" method="POST">
                    @csrf
                    <div class="flex space-x-2">
                        <input type="number" 
                               name="amount" 
                               step="0.01" 
                               min="0.01" 
                               max="1000" 
                               placeholder="Number of credits"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                        <input type="text" 
                               name="description" 
                               placeholder="Description (optional)"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                        <button type="submit" 
                                class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition-colors">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Quick Actions -->
            <div class="mb-4">
                <h4 class="text-sm font-medium text-gray-700 mb-2">Quick Actions</h4>
                <div class="grid grid-cols-2 gap-2">
                    <button onclick="quickAddCredits(5)" class="bg-blue-100 text-blue-700 px-3 py-2 rounded-md hover:bg-blue-200 transition-colors text-sm">
                        +5 Credits
                    </button>
                    <button onclick="quickAddCredits(10)" class="bg-blue-100 text-blue-700 px-3 py-2 rounded-md hover:bg-blue-200 transition-colors text-sm">
                        +10 Credits
                    </button>
                    <button onclick="quickAddCredits(25)" class="bg-blue-100 text-blue-700 px-3 py-2 rounded-md hover:bg-blue-200 transition-colors text-sm">
                        +25 Credits
                    </button>
                    <button onclick="quickAddCredits(50)" class="bg-blue-100 text-blue-700 px-3 py-2 rounded-md hover:bg-blue-200 transition-colors text-sm">
                        +50 Credits
                    </button>
                </div>
            </div>

            <!-- Close Button -->
            <div class="flex justify-end">
                <button onclick="closeCreditModal()" 
                        class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentUserId = null;

function openCreditModal(userId, userName, userCredits) {
    currentUserId = userId;
    document.getElementById('modalUserName').textContent = userName;
    document.getElementById('modalUserCredits').textContent = userCredits;
    
    // Set form actions
    document.getElementById('addCreditsForm').action = `/admin/users/${userId}/add-credits`;
    document.getElementById('removeCreditsForm').action = `/admin/users/${userId}/remove-credits`;
    
    document.getElementById('creditModal').classList.remove('hidden');
}

function closeCreditModal() {
    document.getElementById('creditModal').classList.add('hidden');
    currentUserId = null;
}

function quickAddCredits(amount) {
    if (currentUserId) {
        const form = document.getElementById('addCreditsForm');
        form.querySelector('input[name="amount"]').value = amount;
        form.querySelector('input[name="description"]').value = `Snelle toevoeging van ${amount} credits`;
        form.submit();
    }
}

// Close modal when clicking outside
document.getElementById('creditModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCreditModal();
    }
});
</script>
@endsection
