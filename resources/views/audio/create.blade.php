@extends('layouts.app')

@section('title', 'Translate audio')

@section('content')
@php
    $femaleVoiceKeys = [
        'achernar', 'aoede', 'autonoe', 'callirrhoe', 'despina', 'erinome', 'gacrux', 'kore',
        'laomedeia', 'leda', 'pulcherrima', 'sulafat', 'vindemiatrix', 'zephyr',
    ];
    $voices = collect(config('audio.available_voices'));
    $femaleVoices = $voices->only($femaleVoiceKeys);
    $maleVoices = $voices->except($femaleVoiceKeys);
@endphp

<x-page-header
    title="Translate audio"
    description="Upload an audio file, then review the transcript and translation before we generate the new audio."
    :back="route('audio.index')"
    backLabel="Dashboard"
/>

<div class="grid items-start gap-6 lg:grid-cols-[1fr_18rem]">
    <form id="uploadForm" action="{{ route('audio.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6">
        @csrf

        <x-panel title="1. Audio file">
            <input type="file" id="audio" name="audio" accept=".mp3,.wav,.m4a,.mp4" required class="sr-only">

            <label for="audio" id="dropZone" class="dropzone flex cursor-pointer flex-col items-center gap-4 px-6 py-10 text-center">
                <div id="dropZoneContent" class="flex flex-col items-center gap-3">
                    <i class="fa-solid fa-microphone-lines text-2xl text-accent" aria-hidden="true"></i>
                    <div>
                        <p class="font-semibold text-ink">Drag and drop your audio file</p>
                        <p class="text-sm text-muted">or click to browse</p>
                    </div>
                    <div class="flex flex-wrap justify-center gap-2">
                        <span class="rounded-full bg-sunken px-2.5 py-1 text-xs text-muted">MP3</span>
                        <span class="rounded-full bg-sunken px-2.5 py-1 text-xs text-muted">WAV</span>
                        <span class="rounded-full bg-sunken px-2.5 py-1 text-xs text-muted">M4A</span>
                        <span class="rounded-full bg-sunken px-2.5 py-1 text-xs text-muted">Max {{ config('audio.max_upload_size', 100) }}MB</span>
                    </div>
                </div>
                <div id="fileInfo" class="flex-col items-center gap-1" hidden>
                    <i class="fa-solid fa-circle-check text-2xl text-success" aria-hidden="true"></i>
                    <p id="fileName" class="font-semibold text-ink"></p>
                    <p id="fileSize" class="text-sm text-muted"></p>
                </div>
            </label>

            @error('audio')
                <p class="field-error">{{ $message }}</p>
            @enderror
        </x-panel>

        <x-panel title="2. Languages">
            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Source language" for="source_language" error="source_language">
                    <select id="source_language" name="source_language" required class="select @error('source_language') is-invalid @enderror">
                        <option value="">Select source...</option>
                        @include('partials.language-options', ['selectedLanguage' => old('source_language')])
                    </select>
                </x-field>

                <x-field label="Target language" for="target_language" error="target_language">
                    <select id="target_language" name="target_language" required class="select @error('target_language') is-invalid @enderror">
                        <option value="">Select target...</option>
                        @include('partials.language-options', ['selectedLanguage' => old('target_language')])
                    </select>
                </x-field>
            </div>
        </x-panel>

        <x-panel title="3. Voice">
            <x-field label="Voice for the translated audio" for="voice" error="voice" hint="Gemini 2.5 Pro TTS voices offer natural pronunciation in every supported language.">
                <select id="voice" name="voice" required class="select @error('voice') is-invalid @enderror">
                    <option value="">Select a voice...</option>
                    <optgroup label="Female voices">
                        @foreach ($femaleVoices as $value => $label)
                            <option value="{{ $value }}"{{ old('voice') === $value ? ' selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Male voices">
                        @foreach ($maleVoices as $value => $label)
                            <option value="{{ $value }}"{{ old('voice') === $value ? ' selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </optgroup>
                </select>
            </x-field>
        </x-panel>

        <x-panel title="4. Style instruction" description="Optional">
            <x-slot:actions>
                <a href="{{ route('style-presets.index') }}" target="_blank" class="inline-flex items-center gap-1.5 text-sm text-muted no-underline hover:text-ink">
                    <i class="fa-solid fa-sliders" aria-hidden="true"></i>Manage presets
                </a>
            </x-slot:actions>

            <div class="flex flex-col gap-4">
                <x-field label="Load a preset" for="stylePresetSelect">
                    <select id="stylePresetSelect" class="select">
                        <option value="">Choose a preset...</option>
                        <option value="custom">Custom (write below)</option>
                    </select>
                </x-field>

                <x-field label="Style instruction" for="style_instruction" error="style_instruction" hint="Customize tone, emotion, and pace. Only works with Gemini 2.5 Pro TTS voices.">
                    <textarea id="style_instruction" name="style_instruction" rows="3" class="textarea @error('style_instruction') is-invalid @enderror" placeholder="e.g. Speak with enthusiasm and energy, use a calm and soothing tone...">{{ old('style_instruction') }}</textarea>
                </x-field>
            </div>
        </x-panel>

        <x-alert type="info" id="uploadProgress" hidden>Uploading your file...</x-alert>

        <div class="flex justify-end gap-3">
            <x-button :href="route('audio.index')" variant="ghost">Cancel</x-button>
            <x-button type="submit" id="submitButton" variant="primary" size="lg" icon="wand-magic-sparkles">Upload and translate</x-button>
        </div>
    </form>

    <aside>
        <x-panel title="What happens next">
            <ol class="flex flex-col gap-4 text-sm">
                @foreach ([
                    ['title' => 'Transcribe', 'desc' => 'Whisper converts your audio to text.'],
                    ['title' => 'Review the transcript', 'desc' => 'Edit it before translation starts.'],
                    ['title' => 'Translate', 'desc' => 'Google Translate translates the approved text.'],
                    ['title' => 'Review the translation', 'desc' => 'Edit it before we generate audio.'],
                    ['title' => 'Generate audio', 'desc' => 'Gemini TTS creates the new audio in your voice.'],
                    ['title' => 'Download', 'desc' => 'Get the finished file from your dashboard.'],
                ] as $index => $step)
                    <li class="flex gap-3">
                        <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-sunken text-xs font-semibold text-muted">{{ $index + 1 }}</span>
                        <span>
                            <span class="block font-medium text-ink">{{ $step['title'] }}</span>
                            <span class="text-muted">{{ $step['desc'] }}</span>
                        </span>
                    </li>
                @endforeach
            </ol>
        </x-panel>
    </aside>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const dropZone = document.getElementById('dropZone');
    const dropZoneContent = document.getElementById('dropZoneContent');
    const fileInfo = document.getElementById('fileInfo');
    const audioInput = document.getElementById('audio');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    const uploadForm = document.getElementById('uploadForm');
    const submitButton = document.getElementById('submitButton');
    const uploadProgress = document.getElementById('uploadProgress');

    const maxUploadSize = {{ (int) config('audio.max_upload_size', 100) }} * 1024 * 1024;
    const allowedExtensions = ['.mp3', '.wav', '.m4a', '.mp4', '.ogg', '.flac'];
    const allowedMimeTypes = ['audio/mpeg', 'audio/wav', 'audio/mp4', 'audio/x-m4a', 'video/mp4'];

    dropZone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropZone.classList.add('drag-over');
    });

    dropZone.addEventListener('dragleave', function (e) {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
    });

    dropZone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            const dt = new DataTransfer();
            dt.items.add(files[0]);
            audioInput.files = dt.files;
            handleFile(files[0]);
        }
    });

    audioInput.addEventListener('change', function (e) {
        if (e.target.files.length > 0) handleFile(e.target.files[0]);
    });

    uploadForm.addEventListener('submit', function () {
        uploadProgress.hidden = false;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Uploading...';
    });

    function handleFile(file) {
        const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
        if (!allowedExtensions.includes(fileExtension) && !allowedMimeTypes.includes(file.type)) {
            alert('Only audio files are allowed (MP3, WAV, M4A).');
            audioInput.value = '';
            return;
        }
        if (file.size > maxUploadSize) {
            alert('File is too large. Maximum {{ (int) config('audio.max_upload_size', 100) }}MB.');
            audioInput.value = '';
            return;
        }
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        dropZoneContent.hidden = true;
        fileInfo.hidden = false;
        fileInfo.classList.add('flex');
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024, sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Style preset loading.
    const stylePresetSelect = document.getElementById('stylePresetSelect');
    const styleInstructionTextarea = document.getElementById('style_instruction');
    if (stylePresetSelect && styleInstructionTextarea) {
        fetch('{{ route('style-presets.api') }}')
            .then((r) => r.json())
            .then((presets) => {
                presets.forEach((preset) => {
                    const option = document.createElement('option');
                    option.value = preset.id;
                    option.textContent = (preset.is_default ? '[Default] ' : '') + preset.name;
                    option.dataset.instruction = preset.instruction;
                    stylePresetSelect.appendChild(option);
                });
            })
            .catch(() => {});

        stylePresetSelect.addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            if (selected.dataset.instruction) {
                styleInstructionTextarea.value = selected.dataset.instruction;
            } else if (this.value === '') {
                styleInstructionTextarea.value = '';
            }
        });
    }
});
</script>
@endpush
@endsection
