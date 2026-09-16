@extends('layouts.app')

@section('title', 'Edit voice style')

@section('content')
<x-page-header title="Edit voice style" :description="$preset->is_default ? 'You are editing a default voice style, visible to all users.' : 'Update your style instruction.'" :back="route('style-presets.index')" backLabel="Back to voice styles">
    @if ($preset->is_default)
        <x-slot:actions>
            <span class="status status-neutral">Default</span>
        </x-slot:actions>
    @endif
</x-page-header>

<x-panel>
    <form method="POST" action="{{ route('style-presets.update', $preset->id) }}" class="flex flex-col gap-5">
        @csrf
        @method('PUT')

        <x-field label="Name" for="name" error="name">
            <input type="text" id="name" name="name" value="{{ old('name', $preset->name) }}" required maxlength="100"
                   class="input @error('name') is-invalid @enderror">
        </x-field>

        <x-field label="Instruction" for="instruction" error="instruction">
            <textarea id="instruction" name="instruction" rows="6" required maxlength="5000"
                      class="textarea @error('instruction') is-invalid @enderror">{{ old('instruction', $preset->instruction) }}</textarea>
        </x-field>

        <div class="flex flex-wrap items-center gap-3">
            <x-button type="submit" icon="floppy-disk">Save voice style</x-button>
            <x-button :href="route('style-presets.index')" variant="secondary">Cancel</x-button>
        </div>
    </form>
</x-panel>
@endsection
