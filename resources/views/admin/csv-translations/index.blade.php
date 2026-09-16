@extends('layouts.app')

@section('title', 'File translation — Audio Translator')

@section('content')

@php
    $languageNames = config('audio.languages');
    $presetCodes = collect(config('audio.csv_preset_languages'))->filter(fn ($code) => isset($languageNames[$code]));
    $langs = $presetCodes->reject(fn ($code) => $code === 'en')
        ->mapWithKeys(fn ($code) => [$code => $languageNames[$code]])
        ->all();
    $presetOrder = $presetCodes->map(fn ($code) => strtoupper($code))->implode(' → ');
@endphp

<x-page-header
    title="File translation"
    description="Upload a CSV or XLSX file to translate from English to multiple languages."
    :back="route('admin.dashboard')"
    backLabel="Back to dashboard"
/>

<div class="flex flex-col gap-6">
    <x-panel title="Upload file">
        <form action="{{ route('admin.csv-translations.process') }}" method="POST" enctype="multipart/form-data" id="uploadForm" class="flex flex-col gap-6">
            @csrf

            @if ($errors->any())
                <x-alert type="error">
                    <ul class="flex flex-col gap-1 pl-4 list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            <x-field label="Select CSV or XLSX file" for="csv_file" hint="Maximum file size: 100MB. Supports CSV and XLSX files.">
                <input type="file" name="csv_file" id="csv_file" accept=".csv,.xlsx" required class="input">
            </x-field>

            <div>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <label class="field-label mb-0">Select languages to translate <span class="font-normal text-muted">(optional)</span></label>
                    <div class="flex gap-2">
                        <button type="button" id="selectAll" class="btn btn-secondary btn-sm">Select all</button>
                        <button type="button" id="selectNone" class="btn btn-secondary btn-sm">Select none</button>
                    </div>
                </div>
                <p class="field-hint mt-1">Optional — if none are selected, all empty columns are translated.</p>

                <div class="mt-3 max-h-52 overflow-y-auto rounded-control border border-line bg-sunken p-4">
                    <div class="grid grid-cols-[repeat(auto-fill,minmax(160px,1fr))] gap-2">
                        @foreach ($langs as $code => $name)
                            <label class="flex items-center gap-2 text-sm text-ink">
                                <input type="checkbox" name="languages[]" value="{{ $code }}">
                                <span>{{ $name }} <span class="text-faint">({{ $code }})</span></span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div>
                <x-button type="submit" variant="primary" size="lg" id="submitBtn" class="w-full justify-center">
                    <i class="fa-solid fa-language" id="btnIcon" aria-hidden="true"></i>
                    <span id="btnText">Translate file</span>
                </x-button>
                <p class="field-hint mt-2 text-center">Large files are processed in the background. You will be redirected to a status page.</p>
            </div>
        </form>
    </x-panel>

    <x-panel title="How files are handled" description="Rules for CSV and XLSX uploads.">
        <div class="flex flex-col gap-5 text-sm text-muted">
            <div>
                <h3 class="mb-1.5 text-sm font-semibold text-ink">Background processing</h3>
                <ul class="flex flex-col gap-1 list-disc pl-4">
                    <li>Files are processed in the background, so there are no timeout issues.</li>
                    <li>Upload files up to <strong class="text-ink">100MB</strong>.</li>
                    <li>Works well for huge files with 100,000+ rows.</li>
                    <li>Progress updates in real time; you can close the browser and come back.</li>
                </ul>
            </div>

            <div>
                <h3 class="mb-1.5 text-sm font-semibold text-ink">File format requirements</h3>
                <ul class="flex flex-col gap-1 list-disc pl-4">
                    <li><strong class="text-ink">CSV:</strong> delimiter is semicolon (;).</li>
                    <li><strong class="text-ink">XLSX:</strong> standard Excel format.</li>
                    <li>First column must be <strong class="text-ink">en</strong> (English source) — required.</li>
                    <li>Only empty cells are translated.</li>
                    <li>Existing translations are kept.</li>
                    <li>A KEY column is optional.</li>
                </ul>
            </div>

            <div>
                <h3 class="mb-1.5 text-sm font-semibold text-ink">Smart fallback mode</h3>
                <p>If your file does not match the standard format, we automatically:</p>
                <ul class="mt-1 flex flex-col gap-1 list-disc pl-4">
                    <li>Detect the source language.</li>
                    <li>Translate to the preset languages in order.</li>
                    <li>Create a separate sheet for each language.</li>
                    <li>Produce an XLSX download with multiple sheets.</li>
                </ul>
                <p class="mt-2 text-xs text-faint"><strong class="text-muted">Preset order:</strong> {{ $presetOrder }}</p>
            </div>

            <details class="rounded-control border border-line bg-sunken p-3">
                <summary class="cursor-pointer text-sm font-semibold text-ink">
                    <i class="fa-solid fa-table mr-1.5 text-muted" aria-hidden="true"></i>Example file structure
                </summary>
                <div class="mt-3">
                    <pre class="overflow-x-auto rounded-control border border-line bg-surface p-3 text-xs leading-relaxed text-ink"><code>en;es;fr;de;it;pt;ru
Welcome;;;;
Hello world;;;;
Goodbye;;;;;</code></pre>
                    <p class="mt-2 text-xs text-faint">The same structure applies to CSV and XLSX files. The KEY column is optional.</p>
                </div>
            </details>
        </div>
    </x-panel>

    <x-panel title="Available translation languages">
        <p class="mb-3 text-sm text-faint"><strong class="text-muted">Preset order:</strong> {{ $presetOrder }}</p>
        <div class="grid grid-cols-[repeat(auto-fill,minmax(160px,1fr))] gap-2">
            @foreach ($langs as $code => $name)
                <div class="flex items-center gap-2 rounded-control bg-sunken px-2.5 py-2 text-sm">
                    <span class="lang-code min-w-[22px] text-xs text-accent">{{ $code }}</span>
                    <span class="text-muted">{{ $name }}</span>
                </div>
            @endforeach
        </div>
    </x-panel>
</div>

@endsection

@push('scripts')
<script>
document.getElementById('uploadForm').addEventListener('submit', function () {
    const btn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnIcon = document.getElementById('btnIcon');
    btn.disabled = true;
    btnText.textContent = 'Translating...';
    btnIcon.className = 'fa-solid fa-spinner fa-spin';
});

document.getElementById('csv_file').addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (!file) return;
    const maxSize = 100 * 1024 * 1024;
    if (file.size > maxSize) {
        alert('File size exceeds 100MB limit');
        e.target.value = '';
        return;
    }
    const fileName = file.name.toLowerCase();
    if (!fileName.endsWith('.csv') && !fileName.endsWith('.xlsx')) {
        alert('Please select a CSV or XLSX file');
        e.target.value = '';
    }
});

document.getElementById('selectAll').addEventListener('click', function () {
    document.querySelectorAll('input[name="languages[]"]').forEach((cb) => cb.checked = true);
});

document.getElementById('selectNone').addEventListener('click', function () {
    document.querySelectorAll('input[name="languages[]"]').forEach((cb) => cb.checked = false);
});
</script>
@endpush
