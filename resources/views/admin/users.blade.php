@extends('layouts.app')

@section('title', 'Admin - Users')

@section('content')
<x-page-header title="Users" description="{{ $users->total() }} registered users." :back="route('admin.dashboard')" backLabel="Back to overview" />

<x-panel>
    <div class="overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Credits</th>
                    <th>Translations</th>
                    <th>Payments</th>
                    <th>Audio files</th>
                    <th>Joined</th>
                    <th>Admin</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td>
                            <p class="font-medium text-ink">{{ $user->name }}</p>
                            <p class="text-sm text-muted">{{ $user->email }}</p>
                        </td>
                        <td>{{ number_format($user->credits, 2) }}</td>
                        <td class="whitespace-nowrap">{{ $user->translations_used }} / {{ $user->translations_limit }}</td>
                        <td>{{ $user->payments_count }}</td>
                        <td>{{ $user->audio_files_count }}</td>
                        <td class="whitespace-nowrap text-sm text-muted">{{ $user->created_at->format('d M Y') }}</td>
                        <td>
                            @if ($user->is_admin)
                                <span class="text-sm font-medium text-accent">Admin</span>
                            @else
                                <span class="text-sm text-faint">&mdash;</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    class="btn btn-ghost btn-sm"
                                    data-open-credit-dialog
                                    data-user-name="{{ $user->name }}"
                                    data-user-credits="{{ number_format($user->credits, 2) }}"
                                    data-add-url="{{ route('admin.users.add-credits', $user) }}"
                                    data-remove-url="{{ route('admin.users.remove-credits', $user) }}"
                                >
                                    <i class="fa-solid fa-coins" aria-hidden="true"></i>Credits
                                </button>
                                <a href="{{ route('admin.users.credit-history', $user) }}" class="btn btn-ghost btn-sm">
                                    <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>History
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="py-8 text-center text-sm text-muted">No users have registered yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
        <div class="mt-4">{{ $users->links() }}</div>
    @endif
</x-panel>

<dialog id="credit-dialog" class="panel w-full max-w-md p-0">
    <div class="panel-body">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="text-lg">Manage credits</h3>
            <button type="button" class="btn btn-ghost btn-sm" data-dialog-close>
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                <span class="sr-only">Close</span>
            </button>
        </div>

        <div class="mb-5 rounded-control bg-sunken px-3 py-2.5 text-sm">
            <p class="text-muted">User: <span id="credit-dialog-name" class="font-medium text-ink"></span></p>
            <p class="text-muted">Current balance: <span id="credit-dialog-balance" class="font-semibold text-ink"></span> credits</p>
        </div>

        <form id="add-credits-form" method="POST" class="mb-5 flex flex-col gap-3">
            @csrf
            <p class="field-label mb-0">Add credits</p>
            <div class="flex flex-wrap items-end gap-2">
                <x-field label="Amount" class="min-w-[7rem] flex-1">
                    <input type="number" name="amount" step="0.01" min="0.01" max="1000" class="input" required>
                </x-field>
                <x-field label="Description" class="min-w-[10rem] flex-[2]">
                    <input type="text" name="description" class="input" maxlength="255" placeholder="Optional">
                </x-field>
                <x-button type="submit" variant="primary" size="sm">Add</x-button>
            </div>
        </form>

        <form id="remove-credits-form" method="POST" class="flex flex-col gap-3">
            @csrf
            <p class="field-label mb-0">Remove credits</p>
            <div class="flex flex-wrap items-end gap-2">
                <x-field label="Amount" class="min-w-[7rem] flex-1">
                    <input type="number" name="amount" step="0.01" min="0.01" max="1000" class="input" required>
                </x-field>
                <x-field label="Description" class="min-w-[10rem] flex-[2]">
                    <input type="text" name="description" class="input" maxlength="255" placeholder="Optional">
                </x-field>
                <x-button type="submit" variant="danger" size="sm">Remove</x-button>
            </div>
        </form>
    </div>
</dialog>
@endsection

@push('scripts')
<script>
    (function () {
        const dialog = document.getElementById('credit-dialog');
        if (!dialog) {
            return;
        }

        const addForm = document.getElementById('add-credits-form');
        const removeForm = document.getElementById('remove-credits-form');
        const nameEl = document.getElementById('credit-dialog-name');
        const balanceEl = document.getElementById('credit-dialog-balance');

        document.querySelectorAll('[data-open-credit-dialog]').forEach((button) => {
            button.addEventListener('click', () => {
                nameEl.textContent = button.dataset.userName;
                balanceEl.textContent = button.dataset.userCredits;
                addForm.action = button.dataset.addUrl;
                removeForm.action = button.dataset.removeUrl;
                addForm.reset();
                removeForm.reset();
                dialog.showModal();
            });
        });

        dialog.querySelectorAll('[data-dialog-close]').forEach((button) => {
            button.addEventListener('click', () => dialog.close());
        });

        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) {
                dialog.close();
            }
        });
    })();
</script>
@endpush
