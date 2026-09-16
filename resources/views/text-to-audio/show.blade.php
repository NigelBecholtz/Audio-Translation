@extends('layouts.app')

@section('title', 'Text to speech details')

@section('content')
<x-page-header :back="route('text-to-audio.index')" backLabel="Back to text to speech">
    <x-slot:title>
        <span class="lang-code">{{ strtoupper($textToAudioFile->language) }}</span> text to speech
    </x-slot:title>
</x-page-header>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_18rem] lg:items-start">
    <div class="flex flex-col gap-6">
        <x-panel title="Status">
            <x-slot:actions>
                <x-status id="statusBadge" :status="$textToAudioFile->status" />
            </x-slot:actions>

            @if ($textToAudioFile->isProcessing())
                <div id="pollingIndicator" class="alert alert-info">
                    <i class="fa-solid fa-arrows-rotate fa-spin mt-1 shrink-0" aria-hidden="true"></i>
                    <div class="min-w-0">
                        <p>Generating your audio. This page updates automatically every few seconds.</p>
                        <p id="lastCheck" class="mt-1 text-sm text-muted"></p>
                    </div>
                </div>
            @elseif ($textToAudioFile->isFailed())
                <x-alert type="error">
                    {{ $textToAudioFile->error_message ?: 'Audio generation failed.' }}
                </x-alert>
            @else
                <p class="text-sm text-muted">Audio generated with the {{ $textToAudioFile->voice }} voice, ready to download.</p>
            @endif
        </x-panel>

        <x-panel title="Text">
            <p class="max-w-[70ch] leading-relaxed whitespace-pre-line">{{ $textToAudioFile->text_content }}</p>
        </x-panel>

        @if ($textToAudioFile->isCompleted() && $textToAudioFile->audio_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($textToAudioFile->audio_path))
            <x-panel title="Audio">
                <audio controls preload="metadata">
                    <source src="{{ asset('storage/'.$textToAudioFile->audio_path) }}" type="audio/mpeg">
                    Your browser does not support the audio element.
                </audio>
            </x-panel>
        @endif
    </div>

    <aside class="flex flex-col gap-4">
        <x-panel title="Details">
            <dl class="flex flex-col gap-3 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-muted">Language</dt>
                    <dd><span class="lang-code">{{ strtoupper($textToAudioFile->language) }}</span></dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-muted">Voice</dt>
                    <dd class="capitalize">{{ $textToAudioFile->voice }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-muted">Text length</dt>
                    <dd>{{ strlen($textToAudioFile->text_content) }} characters</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-muted">Created</dt>
                    <dd>{{ $textToAudioFile->created_at->format('d-m-Y H:i') }}</dd>
                </div>
                @if ($textToAudioFile->isCompleted())
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Completed</dt>
                        <dd>{{ $textToAudioFile->updated_at->format('d-m-Y H:i') }}</dd>
                    </div>
                @endif
            </dl>
        </x-panel>

        <x-panel title="Actions">
            <div class="flex flex-col gap-2">
                @if ($textToAudioFile->isCompleted())
                    <x-button :href="route('text-to-audio.download', $textToAudioFile->id)" icon="download" class="w-full justify-center">Download audio</x-button>
                @endif
                <form method="POST" action="{{ route('text-to-audio.destroy', $textToAudioFile->id) }}"
                      onsubmit="return confirm('Delete this text to speech conversion?')">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" icon="trash" class="w-full justify-center">Delete conversion</x-button>
                </form>
            </div>
        </x-panel>
    </aside>
</div>
@endsection

@push('scripts')
@if ($textToAudioFile->isProcessing())
<script>
(function () {
    const textToAudioId = {{ $textToAudioFile->id }};
    let pollInterval;

    function updateStatus(data) {
        const lastCheckEl = document.getElementById('lastCheck');
        if (lastCheckEl) {
            lastCheckEl.textContent = `Last checked ${new Date().toLocaleTimeString()}`;
        }

        const statusBadge = document.getElementById('statusBadge');

        if (data.is_completed) {
            if (statusBadge) {
                statusBadge.className = 'status status-success';
                statusBadge.textContent = 'Ready';
            }

            const pollingIndicator = document.getElementById('pollingIndicator');
            if (pollingIndicator) {
                pollingIndicator.hidden = true;
            }

            clearInterval(pollInterval);
            setTimeout(() => window.location.reload(), 1000);
        } else if (data.is_failed) {
            if (statusBadge) {
                statusBadge.className = 'status status-danger';
                statusBadge.textContent = 'Failed';
            }

            clearInterval(pollInterval);
            setTimeout(() => window.location.reload(), 2000);
        }
    }

    function pollStatus() {
        fetch(`/text-to-audio/${textToAudioId}/status`)
            .then((response) => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            })
            .then(updateStatus)
            .catch(() => {
                const lastCheckEl = document.getElementById('lastCheck');
                if (lastCheckEl) {
                    lastCheckEl.textContent = 'Error checking status — retrying…';
                }
            });
    }

    pollInterval = setInterval(pollStatus, 3000);
    pollStatus();

    window.addEventListener('beforeunload', () => {
        if (pollInterval) {
            clearInterval(pollInterval);
        }
    });
})();
</script>
@endif
@endpush
