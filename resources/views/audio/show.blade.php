@extends('layouts.app')

@section('title', $audioFile->original_filename)

@section('content')

@php
    $showTranscriptReview = $audioFile->isPendingApproval();
    $showTranscriptReadonly = $audioFile->transcription && ! $showTranscriptReview;
    $showTranslationReview = $audioFile->isPendingTTSApproval();
    $showTranslationReadonly = $audioFile->translated_text && ! $showTranslationReview;
    $sideBySide = $showTranscriptReadonly && $showTranslationReadonly;

    $pipelineSteps = [
        [
            'title' => __('Upload'),
            'desc' => $audioFile->created_at->format('d M Y, H:i'),
            'state' => 'done',
        ],
        [
            'title' => __('Transcript (your review)'),
            'desc' => match (true) {
                $showTranscriptReview => __('Review and approve the transcript to continue.'),
                $audioFile->status === 'transcribing' => __('Whisper is transcribing the audio.'),
                $showTranscriptReadonly => __('Approved.'),
                default => __('Whisper will transcribe the audio.'),
            },
            'state' => match (true) {
                $showTranscriptReview => 'review',
                $audioFile->status === 'transcribing' => 'processing',
                $showTranscriptReadonly => 'done',
                default => 'upcoming',
            },
        ],
        [
            'title' => __('Translation (your review)'),
            'desc' => match (true) {
                $showTranslationReview => __('Review and approve the translation to generate audio.'),
                $audioFile->status === 'translating' => __('Translating to').' '.strtoupper($audioFile->target_language).'.',
                $showTranslationReadonly => __('Approved.'),
                default => __('Will be translated to').' '.strtoupper($audioFile->target_language).'.',
            },
            'state' => match (true) {
                $showTranslationReview => 'review',
                $audioFile->status === 'translating' => 'processing',
                $showTranslationReadonly => 'done',
                default => 'upcoming',
            },
        ],
        [
            'title' => __('Audio'),
            'desc' => match (true) {
                $audioFile->status === 'generating_audio' => __('Generating the translated audio.'),
                $audioFile->isCompleted() => __('Voice:').' '.ucfirst((string) $audioFile->voice).'.',
                default => __('Gemini TTS will generate the translated audio.'),
            },
            'state' => match (true) {
                $audioFile->status === 'generating_audio' => 'processing',
                $audioFile->isCompleted() => 'done',
                default => 'upcoming',
            },
        ],
    ];
@endphp

<div class="mb-8">
    <a href="{{ route('audio.index') }}" class="mb-3 inline-flex items-center gap-2 text-sm font-medium text-muted no-underline hover:text-ink">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>{{ __('Back to dashboard') }}
    </a>

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <h1 class="truncate text-3xl sm:text-[2.125rem]" title="{{ $audioFile->original_filename }}">{{ $audioFile->original_filename }}</h1>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <x-language-pair :from="$audioFile->source_language" :to="$audioFile->target_language" size="lg" />
                <x-status id="status-badge" :status="$audioFile->status" />
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($audioFile->isCompleted())
                <x-button variant="secondary" :href="route('audio.additional-translations', $audioFile->id)" icon="plus">{{ __('Add more languages') }}</x-button>
                <x-button variant="primary" :href="route('audio.download', $audioFile->id)" icon="download">{{ __('Download') }}</x-button>
            @endif
            <form method="POST" action="{{ route('audio.destroy', $audioFile->id) }}" onsubmit="return confirm('{{ __('Delete this translation?') }}')">
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger" icon="trash">{{ __('Delete') }}</x-button>
            </form>
        </div>
    </div>
</div>

<div class="flex flex-col gap-6">

    @if ($audioFile->isFailed())
        <x-alert type="error">
            <p class="font-semibold">{{ __('Processing failed') }}</p>
            <p class="mt-0.5">{{ $audioFile->error_message ?? __('An unknown error occurred.') }}</p>
        </x-alert>
    @endif

    @if ($audioFile->status === 'uploaded' && $audioFile->created_at->diffInMinutes(now()) > 2)
        <x-alert type="warning">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-semibold">{{ __('Processing seems stuck') }}</p>
                    <p class="mt-0.5">{{ __('This file has been waiting to start for a while.') }}</p>
                </div>
                <form method="POST" action="{{ route('audio.retry', $audioFile->id) }}">
                    @csrf
                    <x-button type="submit" variant="secondary" size="sm" icon="rotate-right">{{ __('Retry processing') }}</x-button>
                </form>
            </div>
        </x-alert>
    @endif

    @if ($audioFile->isProcessing())
        <x-panel>
            <div class="flex items-center justify-between gap-3 text-sm">
                <span id="progress-message" class="text-muted">{{ $audioFile->processing_message ?? __('Starting...') }}</span>
                <span id="progress-percentage" class="font-semibold text-ink">{{ $audioFile->processing_progress ?? 0 }}%</span>
            </div>
            <div class="progress mt-2">
                <span id="progress-bar" style="width: {{ $audioFile->processing_progress ?? 0 }}%"></span>
            </div>
        </x-panel>
    @endif

    <x-panel :title="__('Pipeline')">
        <ol class="flex flex-col">
            @foreach ($pipelineSteps as $step)
                <li class="flex gap-4">
                    <div class="flex flex-col items-center">
                        <span @class([
                            'grid h-8 w-8 shrink-0 place-items-center rounded-full text-xs font-semibold',
                            'bg-success-soft text-success' => $step['state'] === 'done',
                            'bg-review-soft text-review' => $step['state'] === 'review',
                            'bg-accent-soft text-accent' => $step['state'] === 'processing',
                            'bg-sunken text-faint' => $step['state'] === 'upcoming',
                        ])>
                            @switch($step['state'])
                                @case('done')
                                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                                    @break
                                @case('review')
                                    <i class="fa-solid fa-clock" aria-hidden="true"></i>
                                    @break
                                @case('processing')
                                    <i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i>
                                    @break
                                @default
                                    {{ $loop->iteration }}
                            @endswitch
                        </span>
                        @unless ($loop->last)
                            <span class="mt-1 w-px flex-1 bg-line" aria-hidden="true"></span>
                        @endunless
                    </div>
                    <div class="pb-6">
                        <p @class([
                            'font-semibold',
                            'text-review' => $step['state'] === 'review',
                            'text-muted' => $step['state'] === 'upcoming',
                            'text-ink' => ! in_array($step['state'], ['review', 'upcoming']),
                        ])>{{ $step['title'] }}</p>
                        <p class="mt-0.5 text-sm text-muted">{{ $step['desc'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </x-panel>

    @if ($showTranscriptReview)
        <x-panel :title="__('Transcript')" :description="__('Review and edit before translation continues.')">
            <x-slot:actions>
                <x-status status="pending_approval" />
            </x-slot:actions>

            <form method="POST" action="{{ route('audio.approve-transcription', $audioFile->id) }}" id="approveTranscriptionForm">
                @csrf
                <x-field :label="__('Edit the transcript if needed')" for="edited_transcription" error="transcription" :hint="__('The edited version is used for translation and audio generation.')">
                    <textarea id="edited_transcription" name="transcription" rows="10" class="textarea leading-relaxed @error('transcription') is-invalid @enderror" placeholder="{{ __('Edit the transcript here...') }}">{{ old('transcription', $audioFile->transcription) }}</textarea>
                </x-field>

                <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-button type="button" variant="ghost" size="sm" onclick="resetTranscription()">{{ __('Reset') }}</x-button>
                        <x-button type="button" variant="secondary" size="sm" id="saveTranscriptionBtn" onclick="saveTranscription()">{{ __('Save changes') }}</x-button>
                        <span id="saveStatus" class="hidden items-center gap-1.5 text-sm text-success">
                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i><span id="saveStatusText"></span>
                        </span>
                    </div>
                    <x-button type="submit" id="continueBtn" variant="primary">{{ __('Approve transcript') }}</x-button>
                </div>
            </form>
        </x-panel>
    @endif

    @if ($showTranslationReview)
        <x-panel :title="__('Translation')" :description="__('Review and edit before audio is generated.')">
            <x-slot:actions>
                <x-status status="pending_tts_approval" />
            </x-slot:actions>

            <form method="POST" action="{{ route('audio.save-translated-text', $audioFile->id) }}" id="saveTranslatedTextForm">
                @csrf
                <x-field :label="__('Edit the translation if needed')" for="edited_translated_text" error="translated_text" :hint="__('The edited version is used for audio generation.')">
                    <textarea id="edited_translated_text" name="translated_text" rows="10" class="textarea leading-relaxed @error('translated_text') is-invalid @enderror" placeholder="{{ __('Edit the translated text here...') }}">{{ old('translated_text', $audioFile->translated_text) }}</textarea>
                </x-field>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <x-button type="button" variant="ghost" size="sm" onclick="resetTranslatedText()">{{ __('Reset') }}</x-button>
                    <x-button type="button" variant="secondary" size="sm" id="saveTranslatedTextBtn" onclick="saveTranslatedText()">{{ __('Save changes') }}</x-button>
                    <span id="saveTranslatedTextStatus" class="hidden items-center gap-1.5 text-sm text-success">
                        <i class="fa-solid fa-circle-check" aria-hidden="true"></i><span id="saveTranslatedTextStatusText"></span>
                    </span>
                </div>
            </form>

            <form id="approveTTSForm" method="POST" action="{{ route('audio.approve-tts', $audioFile->id) }}" class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-4">
                @csrf
                <p class="text-sm text-muted">{{ __('Voice:') }} <strong class="text-ink">{{ ucfirst((string) $audioFile->voice) }}</strong> — {{ __('save changes first') }}</p>
                <x-button type="submit" id="generateAudioBtn" variant="primary" icon="volume-high">{{ __('Generate audio') }}</x-button>
            </form>
        </x-panel>
    @endif

    @if ($showTranscriptReadonly || $showTranslationReadonly)
        <div class="{{ $sideBySide ? 'grid gap-6 lg:grid-cols-2' : 'flex flex-col gap-6' }}">
            @if ($showTranscriptReadonly)
                <x-panel :title="__('Transcript')" :description="strtoupper($audioFile->source_language)">
                    <p class="whitespace-pre-wrap text-[1.0625rem] leading-relaxed text-ink">{{ $audioFile->transcription }}</p>
                </x-panel>
            @endif

            @if ($showTranslationReadonly)
                <x-panel :title="__('Translation')" :description="strtoupper($audioFile->target_language)">
                    <p class="whitespace-pre-wrap text-[1.0625rem] leading-relaxed text-ink">{{ $audioFile->translated_text }}</p>
                </x-panel>
            @endif
        </div>
    @endif

    @if ($audioFile->file_path && $audioFile->status !== 'failed' && \Storage::disk('public')->exists($audioFile->file_path))
        <x-panel :title="__('Original audio')">
            <audio controls preload="metadata">
                <source src="{{ asset('storage/'.$audioFile->file_path) }}" type="audio/mpeg">
            </audio>
        </x-panel>
    @endif

    @if ($audioFile->isCompleted() && $audioFile->translated_audio_path && \Storage::disk('public')->exists($audioFile->translated_audio_path))
        <x-panel :title="__('Translated audio')">
            <x-slot:actions>
                <x-button variant="secondary" size="sm" :href="route('audio.download', $audioFile->id)" icon="download">{{ __('Download') }}</x-button>
            </x-slot:actions>
            <audio controls preload="metadata">
                <source src="{{ asset('storage/'.$audioFile->translated_audio_path) }}" type="audio/mpeg">
            </audio>
        </x-panel>
    @endif

    @if ($audioFile->isCompleted() || ($audioFile->audioTranslations && $audioFile->audioTranslations->count() > 0))
        <x-panel :title="__('Additional translations')" :description="__('More languages generated from this recording.')">
            <x-slot:actions>
                @if ($audioFile->isCompleted())
                    <x-button variant="secondary" size="sm" :href="route('audio.additional-translations', $audioFile->id)" icon="plus">{{ __('Add language') }}</x-button>
                @endif
            </x-slot:actions>

            @if ($audioFile->audioTranslations && $audioFile->audioTranslations->count() > 0)
                <div class="flex flex-col gap-3">
                    @foreach ($audioFile->audioTranslations as $translation)
                        <div class="rounded-control border border-line p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <x-language-pair :from="$audioFile->source_language" :to="$translation->target_language" />
                                    <x-status :status="$translation->status" />
                                </div>
                                @if ($translation->isCompleted())
                                    <x-button variant="secondary" size="sm" :href="route('audio.download-translation', [$audioFile->id, $translation->id])" icon="download">{{ __('Download') }}</x-button>
                                @endif
                            </div>

                            @if ($translation->isCompleted() && $translation->translated_audio_path && \Storage::disk('public')->exists($translation->translated_audio_path))
                                <audio controls preload="metadata" class="mt-3">
                                    <source src="{{ asset('storage/'.$translation->translated_audio_path) }}" type="audio/mpeg">
                                </audio>
                            @elseif ($translation->isCompleted())
                                <p class="mt-2 text-sm text-muted">{{ __('Audio is ready to download.') }}</p>
                            @elseif ($translation->isFailed())
                                <p class="mt-2 text-sm text-danger">{{ $translation->error_message ?: __('Translation failed.') }}</p>
                            @elseif ($translation->isProcessing())
                                <p class="mt-2 text-sm text-muted">{{ $translation->processing_message ?? __('Generating...') }}</p>
                                @if ($translation->processing_progress > 0)
                                    <div class="progress mt-2">
                                        <span style="width: {{ $translation->processing_progress }}%"></span>
                                    </div>
                                @endif
                            @else
                                <p class="mt-2 text-sm text-muted">{{ __('Queued for processing.') }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <x-empty-state :title="__('No additional translations yet')" :description="__('Add another language to generate a second translated audio file from this recording.')" icon="language" />
            @endif
        </x-panel>
    @endif

    <x-panel :title="__('Details')">
        <dl class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3">
            @foreach ([
                [__('Filename'), $audioFile->original_filename],
                [__('Size'), number_format($audioFile->file_size / 1024, 2).' KB'],
                [__('Source'), strtoupper($audioFile->source_language)],
                [__('Target'), strtoupper($audioFile->target_language)],
                [__('Voice'), ucfirst((string) $audioFile->voice)],
                [__('Uploaded'), $audioFile->created_at->format('d M Y, H:i')],
            ] as [$label, $value])
                <div class="min-w-0">
                    <dt class="text-sm text-muted">{{ $label }}</dt>
                    <dd class="mt-0.5 truncate font-medium text-ink" title="{{ $value }}">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </x-panel>

</div>

@push('scripts')
<script>
@if ($audioFile->isProcessing())
(function () {
    const audioFileId = {{ $audioFile->id }};
    const progressBar = document.getElementById('progress-bar');
    const progressPercentage = document.getElementById('progress-percentage');
    const progressMessage = document.getElementById('progress-message');
    const statusBadge = document.getElementById('status-badge');

    const statusMeta = {
        uploaded: ['status-neutral', @json(__('Queued'))],
        pending: ['status-neutral', @json(__('Queued'))],
        transcribing: ['status-progress', @json(__('Transcribing'))],
        translating: ['status-progress', @json(__('Translating'))],
        generating_audio: ['status-progress', @json(__('Generating audio'))],
        pending_approval: ['status-review', @json(__('Review transcript'))],
        pending_tts_approval: ['status-review', @json(__('Review translation'))],
        completed: ['status-success', @json(__('Ready'))],
        failed: ['status-danger', @json(__('Failed'))],
    };

    function updateStatusBadge(status) {
        if (!statusBadge || !statusMeta[status]) return;
        const [variant, label] = statusMeta[status];
        statusBadge.className = 'status ' + variant;
        statusBadge.textContent = label;
    }

    function updateProgress(data) {
        if (progressBar && data.processing_progress !== null) progressBar.style.width = data.processing_progress + '%';
        if (progressPercentage && data.processing_progress !== null) progressPercentage.textContent = data.processing_progress + '%';
        if (progressMessage && data.processing_message) progressMessage.textContent = data.processing_message;
        updateStatusBadge(data.status);

        if (data.is_pending_approval || data.is_pending_tts_approval || data.is_completed || data.is_failed) {
            clearInterval(pollInterval);
            setTimeout(() => window.location.reload(), 1000);
        }
    }

    function pollStatus() {
        fetch(@json(route('audio.status', $audioFile->id)))
            .then((r) => r.json())
            .then(updateProgress)
            .catch(() => {});
    }

    var pollInterval = setInterval(pollStatus, 3000);
    pollStatus();
    window.addEventListener('beforeunload', () => clearInterval(pollInterval));
})();
@endif

function saveTranscription() {
    const textarea = document.getElementById('edited_transcription');
    const saveBtn = document.getElementById('saveTranscriptionBtn');
    const saveStatus = document.getElementById('saveStatus');
    const saveStatusText = document.getElementById('saveStatusText');
    if (!textarea || !saveBtn) return;

    const transcription = textarea.value.trim();
    if (!transcription) {
        alert(@json(__('Transcription cannot be empty.')));
        return;
    }

    const originalLabel = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> '+@json(__('Saving...'));

    fetch(@json(route('audio.save-transcription', $audioFile->id)), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify({ transcription }),
    })
        .then((r) => r.json())
        .then((data) => {
            if (data.success) {
                saveStatusText.textContent = data.message;
                saveStatus.classList.remove('hidden');
                saveStatus.classList.add('flex');
                setTimeout(() => {
                    saveStatus.classList.add('hidden');
                    saveStatus.classList.remove('flex');
                }, 3000);
            } else {
                alert(data.error || @json(__('Failed to save.')));
            }
        })
        .catch(() => alert(@json(__('Failed to save transcription.'))))
        .finally(() => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalLabel;
        });
}

function resetTranscription() {
    const textarea = document.getElementById('edited_transcription');
    if (textarea) textarea.value = @json($audioFile->transcription ?? '');
}

function saveTranslatedText() {
    const textarea = document.getElementById('edited_translated_text');
    const saveBtn = document.getElementById('saveTranslatedTextBtn');
    const saveStatus = document.getElementById('saveTranslatedTextStatus');
    const saveStatusText = document.getElementById('saveTranslatedTextStatusText');
    if (!textarea || !saveBtn) return;

    const translated_text = textarea.value.trim();
    if (!translated_text) {
        alert(@json(__('Translated text cannot be empty.')));
        return;
    }

    const originalLabel = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> '+@json(__('Saving...'));

    fetch(@json(route('audio.save-translated-text', $audioFile->id)), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify({ translated_text }),
    })
        .then((r) => r.json())
        .then((data) => {
            if (data.success) {
                saveStatusText.textContent = data.message;
                saveStatus.classList.remove('hidden');
                saveStatus.classList.add('flex');
                setTimeout(() => {
                    saveStatus.classList.add('hidden');
                    saveStatus.classList.remove('flex');
                }, 3000);
            } else {
                alert(data.error || @json(__('Failed to save.')));
            }
        })
        .catch(() => alert(@json(__('Failed to save translated text.'))))
        .finally(() => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalLabel;
        });
}

function resetTranslatedText() {
    const textarea = document.getElementById('edited_translated_text');
    if (textarea) textarea.value = @json($audioFile->translated_text ?? '');
}

@if ($audioFile->audioTranslations && $audioFile->audioTranslations->whereIn('status', ['translating', 'generating_audio', 'pending'])->count() > 0)
(function () {
    const refreshInterval = setInterval(() => window.location.reload(), 5000);
    setTimeout(() => clearInterval(refreshInterval), 600000);
})();
@endif
</script>
@endpush
@endsection
