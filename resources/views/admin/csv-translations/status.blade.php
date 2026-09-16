@extends('layouts.app')

@section('title', 'File translation status — Audio Translator')

@section('content')

<x-page-header
    title="File translation"
    :description="'File: '.$job->original_filename"
    :back="route('admin.csv-translations.index')"
    backLabel="Back to upload"
/>

<div class="flex flex-col gap-6">
    <x-panel>
        <div class="mb-6">
            <p class="mb-1 text-sm text-muted">Status</p>
            <x-status id="statusPill" :status="$job->status" />
        </div>

        <div id="progressSection" class="mb-6 {{ $job->isCompleted() || $job->isFailed() ? 'hidden' : '' }}">
            <div class="mb-2 flex items-center justify-between text-sm font-medium text-ink">
                <span>Progress</span>
                <span><span id="progressPercentage">{{ $job->progress_percentage }}</span>%</span>
            </div>
            <div class="progress">
                <span id="progressBar" style="width: {{ $job->progress_percentage }}%"></span>
            </div>
            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted">
                <span><span id="processedItems" class="font-medium text-ink">{{ number_format($job->processed_items) }}</span> processed</span>
                <span><span id="totalItems" class="font-medium text-ink">{{ number_format($job->total_items) }}</span> total</span>
                <span><span id="failedItems" class="font-medium text-ink">{{ number_format($job->failed_items) }}</span> failed</span>
            </div>
        </div>

        <div id="loadingAnimation" class="mb-6 flex items-center justify-center gap-3 text-sm text-muted {{ $job->isCompleted() || $job->isFailed() ? 'hidden' : '' }}">
            <i class="fa-solid fa-circle-notch fa-spin text-accent" aria-hidden="true"></i>
            <span>Processing your file. This may take several minutes for large files.</span>
        </div>

        <div id="completedSection" class="mb-6 {{ !$job->isCompleted() ? 'hidden' : '' }}">
            <x-alert type="success" class="mb-4">
                <p class="font-semibold">Translation completed</p>
                <p class="mt-1">Your file has been translated successfully. <strong id="finalProcessedItems">{{ number_format($job->processed_items) }}</strong> items translated.</p>
            </x-alert>

            <x-button href="{{ route('admin.csv-translations.download', $job->id) }}" variant="primary" size="lg" icon="download" class="w-full justify-center">
                Download translated file
            </x-button>
        </div>

        <div id="failedSection" class="mb-6 {{ !$job->isFailed() ? 'hidden' : '' }}">
            <x-alert type="error">
                <p class="font-semibold">Translation failed</p>
                <p class="mt-1" id="errorMessage">{{ $job->error_message }}</p>
            </x-alert>
            <a href="{{ route('admin.csv-translations.index') }}" class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-accent no-underline hover:text-accent-hover">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Try again with a different file
            </a>
        </div>

        <div class="mt-6 border-t border-line pt-4">
            <h3 class="mb-3 text-sm font-semibold text-ink">Job details</h3>
            <dl class="flex flex-col gap-2 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">Job ID</dt>
                    <dd class="font-medium text-ink">{{ $job->id }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-muted">Uploaded</dt>
                    <dd class="font-medium text-ink">{{ $job->created_at->format('Y-m-d H:i:s') }}</dd>
                </div>
                @if ($job->started_at)
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">Started</dt>
                        <dd class="font-medium text-ink">{{ $job->started_at->format('Y-m-d H:i:s') }}</dd>
                    </div>
                @endif
                @if ($job->completed_at)
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">Completed</dt>
                        <dd class="font-medium text-ink">{{ $job->completed_at->format('Y-m-d H:i:s') }}</dd>
                    </div>
                @endif
                @if ($job->target_languages)
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">Target languages</dt>
                        <dd class="flex flex-wrap justify-end gap-x-1.5 font-medium text-ink">
                            @foreach ($job->target_languages as $code)
                                <span class="lang-code text-xs">{{ $code }}</span>
                            @endforeach
                        </dd>
                    </div>
                @endif
                @if ($job->use_smart_fallback)
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted">Mode</dt>
                        <dd class="font-medium text-ink">Smart fallback</dd>
                    </div>
                @endif
            </dl>
        </div>
    </x-panel>

    <x-alert type="info">
        <p class="font-semibold">Note</p>
        <ul class="mt-1 flex flex-col gap-0.5">
            <li>This page updates automatically every 3 seconds.</li>
            <li>Large files (100k+ rows) may take 20–30 minutes.</li>
            <li>You can close this page and return later.</li>
            <li>The download link appears once the file has completed.</li>
        </ul>
    </x-alert>
</div>

@endsection

@push('scripts')
<script>
let pollingInterval;
const statusUrl = "{{ route('admin.csv-translations.status-api', $job) }}";

function statusMeta(status) {
    switch (status) {
        case 'completed':
            return { variant: 'success', label: 'Ready' };
        case 'failed':
            return { variant: 'danger', label: 'Failed' };
        case 'processing':
            return { variant: 'progress', label: 'Processing' };
        default:
            return { variant: 'neutral', label: 'Queued' };
    }
}

function updateStatus() {
    fetch(statusUrl)
        .then((response) => response.json())
        .then((data) => {
            const pill = document.getElementById('statusPill');
            const meta = statusMeta(data.status);
            pill.className = `status status-${meta.variant}`;
            pill.textContent = meta.label;

            if (data.total_items > 0) {
                document.getElementById('progressPercentage').textContent = data.progress_percentage.toFixed(2);
                document.getElementById('progressBar').style.width = data.progress_percentage + '%';
                document.getElementById('processedItems').textContent = data.processed_items.toLocaleString();
                document.getElementById('totalItems').textContent = data.total_items.toLocaleString();
                document.getElementById('failedItems').textContent = data.failed_items.toLocaleString();
            }

            const progressSection = document.getElementById('progressSection');
            const loadingAnimation = document.getElementById('loadingAnimation');
            const completedSection = document.getElementById('completedSection');
            const failedSection = document.getElementById('failedSection');

            if (data.is_completed) {
                progressSection.classList.add('hidden');
                loadingAnimation.classList.add('hidden');
                completedSection.classList.remove('hidden');
                failedSection.classList.add('hidden');

                document.getElementById('finalProcessedItems').textContent = data.processed_items.toLocaleString();

                if (pollingInterval) {
                    clearInterval(pollingInterval);
                }

                if (!window.completedNotified) {
                    window.completedNotified = true;
                    alert('Translation completed. You can now download your file.');
                }
            } else if (data.is_failed) {
                progressSection.classList.add('hidden');
                loadingAnimation.classList.add('hidden');
                completedSection.classList.add('hidden');
                failedSection.classList.remove('hidden');

                document.getElementById('errorMessage').textContent = data.error_message || 'Unknown error occurred';

                if (pollingInterval) {
                    clearInterval(pollingInterval);
                }
            } else {
                progressSection.classList.remove('hidden');
                loadingAnimation.classList.remove('hidden');
                completedSection.classList.add('hidden');
                failedSection.classList.add('hidden');
            }
        })
        .catch((error) => {
            console.error('Error fetching status:', error);
        });
}

updateStatus();

@if ($job->isProcessing())
pollingInterval = setInterval(updateStatus, 3000);
@endif
</script>
@endpush
