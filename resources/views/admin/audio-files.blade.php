@extends('layouts.app')

@section('title', 'Admin - Audio Files')

@section('content')
<x-page-header title="Audio files" description="{{ $audioFiles->total() }} files total." :back="route('admin.dashboard')" backLabel="Back to overview" />

<x-panel>
    <div class="overflow-x-auto">
        <table class="table">
            <thead>
                <tr>
                    <th>File</th>
                    <th>User</th>
                    <th>Languages</th>
                    <th>Status</th>
                    <th>Size</th>
                    <th>Date</th>
                    <th><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($audioFiles as $audioFile)
                    <tr>
                        <td>
                            <p class="max-w-[14rem] truncate font-medium text-ink">{{ $audioFile->original_filename }}</p>
                            <p class="text-sm text-muted">{{ $audioFile->translations->count() }} translations</p>
                        </td>
                        <td>
                            <p class="font-medium text-ink">{{ $audioFile->user->name }}</p>
                            <p class="text-sm text-muted">{{ $audioFile->user->email }}</p>
                        </td>
                        <td><x-language-pair :from="$audioFile->source_language" :to="$audioFile->target_language" /></td>
                        <td><x-status :status="$audioFile->status" /></td>
                        <td class="whitespace-nowrap">{{ number_format($audioFile->file_size / 1024, 1) }} KB</td>
                        <td>
                            <p class="whitespace-nowrap text-sm text-ink">{{ $audioFile->created_at->format('d M Y') }}</p>
                            <p class="text-sm text-muted">{{ $audioFile->created_at->format('H:i') }}</p>
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('audio.show', $audioFile->id) }}" class="text-muted hover:text-ink" title="View">
                                    <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    <span class="sr-only">View</span>
                                </a>
                                @if ($audioFile->status === 'completed')
                                    <a href="{{ route('audio.download', $audioFile->id) }}" class="text-muted hover:text-ink" title="Download">
                                        <i class="fa-solid fa-download" aria-hidden="true"></i>
                                        <span class="sr-only">Download</span>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-sm text-muted">No audio files have been uploaded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($audioFiles->hasPages())
        <div class="mt-4">{{ $audioFiles->links() }}</div>
    @endif
</x-panel>
@endsection
