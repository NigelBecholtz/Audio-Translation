@extends('layouts.app')

@section('title', 'New text to speech')

@section('content')
<x-page-header title="New text to speech" description="Convert written text into spoken audio." :back="route('text-to-audio.index')" backLabel="Back to text to speech" />

<div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_18rem] lg:items-start">
    <form id="textToAudioForm" action="{{ route('text-to-audio.store') }}" method="POST" class="flex flex-col gap-6">
        @csrf

        <x-panel title="Text">
            <x-field label="Text to convert" for="text_content" error="text_content">
                <textarea id="text_content" name="text_content" rows="10" required
                          maxlength="{{ config('audio.max_text_length', 50000) }}"
                          placeholder="Enter the text you want to convert to audio..."
                          class="textarea @error('text_content') is-invalid @enderror">{{ old('text_content') }}</textarea>
            </x-field>

            <div class="mt-2 flex flex-wrap items-start justify-between gap-3">
                <p class="text-sm text-muted">For best voice consistency, keep text under 900 characters. Longer texts are chunked automatically.</p>
                <span id="charCount" class="whitespace-nowrap text-sm text-muted">0 / {{ config('audio.max_text_length', 50000) }}</span>
            </div>

            <p id="chunkingWarning" hidden class="alert alert-warning mt-3">
                Text will be split into chunks — voice may vary slightly between segments.
            </p>
        </x-panel>

        <x-panel title="Language & voice">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-field label="Language" for="language" error="language">
                    <select id="language" name="language" required class="select @error('language') is-invalid @enderror">
                        <option value="">Select language</option>
                        @include('partials.language-options', ['selectedLanguage' => old('language')])
                    </select>
                </x-field>

                <x-field label="Voice" for="voice" error="voice" hint="Gemini 2.5 Pro TTS — best accent support">
                    <select id="voice" name="voice" required class="select @error('voice') is-invalid @enderror">
                        <option value="">Select voice</option>
                        <optgroup label="Female voices">
                            <option value="achernar" {{ old('voice') == 'achernar' ? 'selected' : '' }}>Achernar — clear and expressive</option>
                            <option value="aoede" {{ old('voice') == 'aoede' ? 'selected' : '' }}>Aoede — warm and engaging</option>
                            <option value="autonoe" {{ old('voice') == 'autonoe' ? 'selected' : '' }}>Autonoe — soft and gentle</option>
                            <option value="callirrhoe" {{ old('voice') == 'callirrhoe' ? 'selected' : '' }}>Callirrhoe — bright and energetic</option>
                            <option value="despina" {{ old('voice') == 'despina' ? 'selected' : '' }}>Despina — smooth and professional</option>
                            <option value="erinome" {{ old('voice') == 'erinome' ? 'selected' : '' }}>Erinome — wise and calm</option>
                            <option value="gacrux" {{ old('voice') == 'gacrux' ? 'selected' : '' }}>Gacrux — vibrant and lively</option>
                            <option value="kore" {{ old('voice') == 'kore' ? 'selected' : '' }}>Kore — balanced and versatile</option>
                            <option value="laomedeia" {{ old('voice') == 'laomedeia' ? 'selected' : '' }}>Laomedeia — warm and engaging</option>
                            <option value="leda" {{ old('voice') == 'leda' ? 'selected' : '' }}>Leda — clear and expressive</option>
                            <option value="pulcherrima" {{ old('voice') == 'pulcherrima' ? 'selected' : '' }}>Pulcherrima — bright and energetic</option>
                            <option value="sulafat" {{ old('voice') == 'sulafat' ? 'selected' : '' }}>Sulafat — soft and gentle</option>
                            <option value="vindemiatrix" {{ old('voice') == 'vindemiatrix' ? 'selected' : '' }}>Vindemiatrix — smooth and professional</option>
                            <option value="zephyr" {{ old('voice') == 'zephyr' ? 'selected' : '' }}>Zephyr — vibrant and lively</option>
                        </optgroup>
                        <optgroup label="Male voices">
                            <option value="achird" {{ old('voice') == 'achird' ? 'selected' : '' }}>Achird — deep and authoritative</option>
                            <option value="algenib" {{ old('voice') == 'algenib' ? 'selected' : '' }}>Algenib — strong and confident</option>
                            <option value="algieba" {{ old('voice') == 'algieba' ? 'selected' : '' }}>Algieba — warm and engaging</option>
                            <option value="alnilam" {{ old('voice') == 'alnilam' ? 'selected' : '' }}>Alnilam — clear and expressive</option>
                            <option value="charon" {{ old('voice') == 'charon' ? 'selected' : '' }}>Charon — deep and authoritative</option>
                            <option value="enceladus" {{ old('voice') == 'enceladus' ? 'selected' : '' }}>Enceladus — strong and confident</option>
                            <option value="fenrir" {{ old('voice') == 'fenrir' ? 'selected' : '' }}>Fenrir — powerful and commanding</option>
                            <option value="lapetus" {{ old('voice') == 'lapetus' ? 'selected' : '' }}>Lapetus — warm and engaging</option>
                            <option value="orus" {{ old('voice') == 'orus' ? 'selected' : '' }}>Orus — clear and expressive</option>
                            <option value="puck" {{ old('voice') == 'puck' ? 'selected' : '' }}>Puck — energetic and lively</option>
                            <option value="rasalgethi" {{ old('voice') == 'rasalgethi' ? 'selected' : '' }}>Rasalgethi — deep and authoritative</option>
                            <option value="sadachbia" {{ old('voice') == 'sadachbia' ? 'selected' : '' }}>Sadachbia — strong and confident</option>
                            <option value="sadaltager" {{ old('voice') == 'sadaltager' ? 'selected' : '' }}>Sadaltager — warm and engaging</option>
                            <option value="schedar" {{ old('voice') == 'schedar' ? 'selected' : '' }}>Schedar — clear and expressive</option>
                            <option value="umbriel" {{ old('voice') == 'umbriel' ? 'selected' : '' }}>Umbriel — deep and authoritative</option>
                            <option value="zubenelgenubi" {{ old('voice') == 'zubenelgenubi' ? 'selected' : '' }}>Zubenelgenubi — strong and confident</option>
                        </optgroup>
                    </select>
                </x-field>
            </div>
        </x-panel>

        <x-panel>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-lg">Style instruction <span class="text-sm font-normal text-faint">optional</span></h2>
                <a href="{{ route('style-presets.index') }}" target="_blank" class="text-sm font-medium text-muted no-underline hover:text-ink">
                    <i class="fa-solid fa-sliders" aria-hidden="true"></i> Manage voice styles
                </a>
            </div>

            <x-field label="Preset" for="stylePresetSelect" hint="Select a preset to auto-fill, or write your own below.">
                <select id="stylePresetSelect" class="select">
                    <option value="">Choose a preset or write custom…</option>
                    <option value="custom">Custom (manual)</option>
                </select>
            </x-field>

            <x-field class="mt-4" label="Style instruction" for="style_instruction" error="style_instruction"
                     hint="Customize tone, emotion and pace for the generated speech.">
                <textarea id="style_instruction" name="style_instruction" rows="3"
                          placeholder="e.g. 'Speak with enthusiasm and energy', 'Use a calm and soothing tone'"
                          class="textarea @error('style_instruction') is-invalid @enderror">{{ old('style_instruction') }}</textarea>
            </x-field>
        </x-panel>

        <div class="flex justify-end gap-3">
            <x-button :href="route('text-to-audio.index')" variant="secondary">Cancel</x-button>
            <x-button type="submit" id="submitButton" size="lg" icon="wand-magic-sparkles">Generate audio</x-button>
        </div>
    </form>

    <aside class="flex flex-col gap-4">
        <x-panel title="How it works">
            @php
                $steps = [
                    ['num' => '1', 'title' => 'Enter text', 'desc' => 'Type or paste up to '.number_format(config('audio.max_text_length', 50000)).' characters.'],
                    ['num' => '2', 'title' => 'Choose language and voice', 'desc' => 'Select from '.count(config('audio.languages')).' languages and 30 voices.'],
                    ['num' => '3', 'title' => 'Processing', 'desc' => 'Gemini TTS converts your text to speech.'],
                    ['num' => '4', 'title' => 'Download', 'desc' => 'Get your audio file as MP3.'],
                ];
            @endphp
            <div class="flex flex-col gap-4">
                @foreach ($steps as $step)
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-accent-soft text-xs font-bold text-accent">{{ $step['num'] }}</span>
                        <div>
                            <p class="text-sm font-semibold">{{ $step['title'] }}</p>
                            <p class="text-sm text-muted">{{ $step['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-panel>

        <x-alert type="info">
            For best results, use clear, well-formatted text with proper punctuation for a natural speech rhythm.
        </x-alert>
    </aside>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const textarea = document.getElementById('text_content');
    const charCount = document.getElementById('charCount');
    const chunkingWarning = document.getElementById('chunkingWarning');
    const maxLength = {{ (int) config('audio.max_text_length', 50000) }};

    function updateCharCount() {
        const count = textarea.value.length;
        charCount.textContent = `${count} / ${maxLength}`;
        charCount.classList.toggle('text-danger', count > maxLength);
        charCount.classList.toggle('text-muted', count <= maxLength);
        chunkingWarning.hidden = count <= 900;
    }

    textarea.addEventListener('input', updateCharCount);
    updateCharCount();

    // Style preset functionality
    const stylePresetSelect = document.getElementById('stylePresetSelect');
    const styleInstructionTextarea = document.getElementById('style_instruction');

    if (stylePresetSelect && styleInstructionTextarea) {
        fetch('{{ route("style-presets.api") }}')
            .then(response => response.json())
            .then(presets => {
                presets.forEach(preset => {
                    const option = document.createElement('option');
                    option.value = preset.id;
                    option.textContent = preset.name + (preset.is_default ? ' (default)' : '');
                    option.dataset.instruction = preset.instruction;
                    stylePresetSelect.appendChild(option);
                });
            })
            .catch(error => console.error('Error loading presets:', error));

        stylePresetSelect.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.dataset.instruction) {
                styleInstructionTextarea.value = selectedOption.dataset.instruction;
            } else if (this.value === '') {
                styleInstructionTextarea.value = '';
            }
        });
    }

    // Submit loading state
    document.getElementById('textToAudioForm').addEventListener('submit', function () {
        const btn = document.getElementById('submitButton');
        btn.setAttribute('aria-disabled', 'true');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Generating…';
    });
});
</script>
@endpush
