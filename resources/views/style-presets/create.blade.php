@extends('layouts.app')

@section('title', 'New voice style')

@section('content')
<x-page-header title="New voice style" description="Save a reusable style instruction for your audio generation." :back="route('style-presets.index')" backLabel="Back to voice styles" />

<x-panel>
    <form method="POST" action="{{ route('style-presets.store') }}" class="flex flex-col gap-5">
        @csrf

        <x-field label="Name" for="name" error="name">
            <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="100"
                   placeholder="e.g. Calm and warm, slow pace"
                   class="input @error('name') is-invalid @enderror">
        </x-field>

        <x-field label="Instruction" for="instruction" error="instruction"
                  hint="Describe tone, accent and pace, e.g. 'Speak calmly and warmly, with a slow, professional pace.'">
            <textarea id="instruction" name="instruction" rows="6" required maxlength="5000"
                      placeholder="Describe how the voice should sound..."
                      class="textarea @error('instruction') is-invalid @enderror">{{ old('instruction') }}</textarea>
        </x-field>

        <div class="flex flex-wrap items-center gap-3">
            <x-button type="submit" icon="floppy-disk">Save voice style</x-button>
            <x-button :href="route('style-presets.index')" variant="secondary">Cancel</x-button>
        </div>
    </form>
</x-panel>
@endsection
