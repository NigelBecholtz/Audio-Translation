@extends('layouts.app')

@section('title', 'Text to speech')

@section('content')
<x-page-header title="Text to speech" description="Convert written text into spoken audio.">
    <x-slot:actions>
        <x-button :href="route('text-to-audio.create')" icon="plus">New text to speech</x-button>
    </x-slot:actions>
</x-page-header>

@if ($textToAudioFiles->isEmpty())
    <x-panel>
        <x-empty-state title="No text to speech yet" icon="align-left"
            description="Convert your first piece of text to audio. Choose from 30 voices and {{ count(config('audio.languages')) }} languages.">
            <x-slot:action>
                <x-button :href="route('text-to-audio.create')" icon="plus">New text to speech</x-button>
            </x-slot:action>
        </x-empty-state>
    </x-panel>
@else
    <x-panel>
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        <th>Text</th>
                        <th>Language</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($textToAudioFiles as $textToAudioFile)
                        <tr>
                            <td class="max-w-sm">
                                <a href="{{ route('text-to-audio.show', $textToAudioFile->id) }}" class="line-clamp-2 font-medium text-ink no-underline hover:text-accent">
                                    {{ Str::limit($textToAudioFile->text_content, 90) }}
                                </a>
                            </td>
                            <td><span class="lang-code">{{ strtoupper($textToAudioFile->language) }}</span></td>
                            <td><x-status :status="$textToAudioFile->status" /></td>
                            <td class="whitespace-nowrap text-muted">{{ $textToAudioFile->created_at->format('d-m-Y H:i') }}</td>
                            <td>
                                <div class="flex justify-end gap-2">
                                    <x-button :href="route('text-to-audio.show', $textToAudioFile->id)" variant="secondary" size="sm" icon="eye">View</x-button>
                                    @if ($textToAudioFile->isCompleted())
                                        <x-button :href="route('text-to-audio.download', $textToAudioFile->id)" variant="secondary" size="sm" icon="download">Download</x-button>
                                    @endif
                                    <form method="POST" action="{{ route('text-to-audio.destroy', $textToAudioFile->id) }}" onsubmit="return confirm('Delete this conversion?')">
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
    </x-panel>

    <div class="mt-6">
        {{ $textToAudioFiles->links() }}
    </div>
@endif
@endsection
