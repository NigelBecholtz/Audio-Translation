@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    // Show items waiting on the user's review first, without disturbing pagination.
    $sortedAudioFiles = $audioFiles->getCollection()
        ->sortByDesc(fn ($file) => in_array($file->status, ['pending_approval', 'pending_tts_approval'], true))
        ->values();
@endphp

<x-page-header title="Dashboard" description="Your audio translations and text-to-speech files.">
    <x-slot:actions>
        <x-button :href="route('audio.create')" variant="primary" icon="microphone-lines">Translate audio</x-button>
        <x-button :href="route('text-to-audio.create')" variant="secondary" icon="align-left">Text to speech</x-button>
    </x-slot:actions>
</x-page-header>

<div class="mb-8 -mt-4 inline-flex items-center gap-2 rounded-full bg-sunken px-3 py-1.5 text-sm text-muted">
    <i class="fa-solid fa-wallet text-faint" aria-hidden="true"></i>
    <span class="font-semibold text-ink">{{ $user->hasUnlimitedCredits() ? '∞' : $user->getRemainingTranslations() }}</span> translations left
</div>

<div class="flex flex-col gap-10">
    <x-panel title="Audio translations">
        <x-slot:actions>
            <x-button :href="route('audio.create')" variant="ghost" size="sm" icon="plus">New</x-button>
        </x-slot:actions>

        @if ($audioFiles->count() > 0)
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Languages</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sortedAudioFiles as $audioFile)
                            <tr>
                                <td class="max-w-[16rem] truncate font-medium text-ink">{{ $audioFile->original_filename }}</td>
                                <td><x-language-pair :from="$audioFile->source_language" :to="$audioFile->target_language" size="md" /></td>
                                <td><x-status :status="$audioFile->status" /></td>
                                <td class="whitespace-nowrap text-muted">{{ $audioFile->created_at->diffForHumans() }}</td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <x-button :href="route('audio.show', $audioFile->id)" variant="ghost" size="sm" icon="eye">Open</x-button>
                                        @if ($audioFile->isCompleted())
                                            <x-button :href="route('audio.download', $audioFile->id)" variant="secondary" size="sm" icon="download">Download</x-button>
                                        @endif
                                        <form method="POST" action="{{ route('audio.destroy', $audioFile->id) }}" onsubmit="return confirm('Delete this translation?')">
                                            @csrf
                                            @method('DELETE')
                                            <x-button type="submit" variant="danger" size="sm" icon="trash">
                                                <span class="sr-only">Delete</span>
                                            </x-button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $audioFiles->links() }}</div>
        @else
            <x-empty-state
                title="No audio translations yet"
                description="Upload an audio file (MP3, WAV or M4A, max {{ config('audio.max_upload_size', 100) }}MB) and get it translated."
                icon="microphone-lines"
            >
                <x-slot:action>
                    <x-button :href="route('audio.create')" variant="primary" icon="upload">Upload first audio file</x-button>
                </x-slot:action>
            </x-empty-state>
        @endif
    </x-panel>

    <x-panel title="Text to speech">
        <x-slot:actions>
            <x-button :href="route('text-to-audio.create')" variant="ghost" size="sm" icon="plus">New</x-button>
        </x-slot:actions>

        @if ($textToAudioFiles->count() > 0)
            <div class="overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Text</th>
                            <th>Language</th>
                            <th>Voice</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($textToAudioFiles as $textToAudio)
                            <tr>
                                <td class="max-w-[16rem] truncate text-ink">{{ Str::limit($textToAudio->text_content, 60) }}</td>
                                <td><span class="lang-code text-sm">{{ strtoupper($textToAudio->language) }}</span></td>
                                <td class="text-muted">{{ ucfirst($textToAudio->voice) }}</td>
                                <td><x-status :status="$textToAudio->status" /></td>
                                <td class="whitespace-nowrap text-muted">{{ $textToAudio->created_at->diffForHumans() }}</td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <x-button :href="route('text-to-audio.show', $textToAudio->id)" variant="ghost" size="sm" icon="eye">Open</x-button>
                                        @if ($textToAudio->isCompleted())
                                            <x-button :href="route('text-to-audio.download', $textToAudio->id)" variant="secondary" size="sm" icon="download">Download</x-button>
                                        @endif
                                        <form method="POST" action="{{ route('text-to-audio.destroy', $textToAudio->id) }}" onsubmit="return confirm('Delete this conversion?')">
                                            @csrf
                                            @method('DELETE')
                                            <x-button type="submit" variant="danger" size="sm" icon="trash">
                                                <span class="sr-only">Delete</span>
                                            </x-button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $textToAudioFiles->links() }}</div>
        @else
            <x-empty-state
                title="No text-to-audio yet"
                description="Convert text to speech in any of your voices and languages."
                icon="align-left"
            >
                <x-slot:action>
                    <x-button :href="route('text-to-audio.create')" variant="primary" icon="plus">Create first text-to-audio</x-button>
                </x-slot:action>
            </x-empty-state>
        @endif
    </x-panel>
</div>
@endsection
