@extends('layouts.app')

@section('title', __('Add more languages'))

@section('content')

<x-page-header
    :title="__('Add more languages')"
    :description="__('Translate :file to another language.', ['file' => $audioFile->original_filename])"
    :back="route('audio.show', $audioFile->id)"
    :backLabel="__('Back to translation')"
/>

<div class="flex flex-col gap-6">

    <x-panel :title="__('Original audio')">
        <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-3">
            <div class="min-w-0">
                <dt class="text-sm text-muted">{{ __('Filename') }}</dt>
                <dd class="mt-0.5 truncate font-medium text-ink" title="{{ $audioFile->original_filename }}">{{ $audioFile->original_filename }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted">{{ __('Source language') }}</dt>
                <dd class="mt-0.5 font-medium text-ink">{{ strtoupper($audioFile->source_language) }}</dd>
            </div>
            <div>
                <dt class="text-sm text-muted">{{ __('Size') }}</dt>
                <dd class="mt-0.5 font-medium text-ink">{{ number_format($audioFile->file_size / 1024, 2) }} KB</dd>
            </div>
        </dl>
    </x-panel>

    @if ($audioTranslations && $audioTranslations->count() > 0)
        <x-panel :title="__('Existing translations')">
            <div class="flex flex-col gap-3">
                @foreach ($audioTranslations as $translation)
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-control border border-line p-4">
                        <div class="flex items-center gap-3">
                            <x-language-pair :from="$audioFile->source_language" :to="$translation->target_language" />
                            <x-status :status="$translation->status" />
                        </div>
                        @if ($translation->isCompleted())
                            <x-button variant="secondary" size="sm" :href="route('audio.download-translation', [$audioFile->id, $translation->id])" icon="download">{{ __('Download') }}</x-button>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-panel>
    @endif

    <x-panel :title="__('Add a language')" :description="__('Generate a new translated audio file in another language.')">
        @if ($availableLanguages->isEmpty())
            <x-empty-state
                :title="__('All languages are done')"
                :description="__('Every configured language has already been generated for this recording.')"
                icon="circle-check"
            />
        @else
            <form method="POST" action="{{ route('audio.store-additional-translations', $audioFile->id) }}" class="flex flex-col gap-5">
                @csrf

                <x-field :label="__('Target language')" for="additional_languages" error="additional_languages">
                    <select name="additional_languages" id="additional_languages" class="select">
                        <option value="">{{ __('Select a language...') }}</option>
                        @include('partials.language-options', ['languageOptions' => $availableLanguages, 'selectedLanguage' => old('additional_languages')])
                    </select>
                </x-field>

                <x-field :label="__('Voice')" for="voice" error="voice">
                    <select name="voice" id="voice" class="select">
                        @foreach (config('audio.available_voices') as $voiceCode => $voiceName)
                            <option value="{{ $voiceCode }}" {{ $audioFile->voice === $voiceCode ? 'selected' : '' }}>{{ $voiceName }}</option>
                        @endforeach
                    </select>
                </x-field>

                <x-field :label="__('Style instruction')" for="style_instruction" error="style_instruction" :hint="__('Leave empty to reuse the style from the original translation.')">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <select id="stylePresetSelect" class="select">
                            <option value="">{{ __('Choose a preset...') }}</option>
                            <option value="custom">{{ __('Custom (write below)') }}</option>
                        </select>
                        <a href="{{ route('style-presets.index') }}" target="_blank" class="shrink-0 whitespace-nowrap text-sm text-muted no-underline hover:text-ink">
                            <i class="fa-solid fa-sliders" aria-hidden="true"></i> {{ __('Presets') }}
                        </a>
                    </div>
                    <textarea name="style_instruction" id="style_instruction" rows="3" class="textarea" placeholder="{{ __('Describe tone, pacing, mood...') }}">{{ old('style_instruction', $audioFile->style_instruction) }}</textarea>
                </x-field>

                <x-alert type="info">
                    @if (auth()->user()?->hasUnlimitedCredits())
                        {{ __('Admin account: unlimited translations, no credits are charged.') }}
                    @else
                        {{ __('This translation costs') }} <strong>{{ config('stripe.default_cost_per_translation') }}</strong> {{ __('credits.') }}
                        {{ __('Your balance:') }} <strong>{{ auth()->user()->credits ?? 0 }}</strong> {{ __('credits.') }}
                    @endif
                </x-alert>

                <div class="flex justify-end">
                    <x-button type="submit" variant="primary" size="lg" icon="plus">{{ __('Start additional translation') }}</x-button>
                </div>
            </form>
        @endif
    </x-panel>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const stylePresetSelect = document.getElementById('stylePresetSelect');
    const styleInstructionTextarea = document.getElementById('style_instruction');
    if (!stylePresetSelect || !styleInstructionTextarea) return;

    fetch(@json(route('style-presets.api')))
        .then((r) => r.json())
        .then((presets) => {
            presets.forEach((preset) => {
                const option = document.createElement('option');
                option.value = preset.id;
                option.textContent = preset.is_default ? `${preset.name} (${@json(__('default'))})` : preset.name;
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
});
</script>
@endpush
@endsection
