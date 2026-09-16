@extends('layouts.app')

@section('title', 'Voice styles')

@section('content')
<x-page-header title="Voice styles" description="Saved style instructions you can reuse when generating audio.">
    <x-slot:actions>
        <x-button :href="route('style-presets.create')" icon="plus">New voice style</x-button>
    </x-slot:actions>
</x-page-header>

@if ($presets->isEmpty())
    <x-panel>
        <x-empty-state title="No voice styles yet" description="Create a voice style to reuse the same tone and pace across translations." icon="sliders">
            <x-slot:action>
                <x-button :href="route('style-presets.create')" icon="plus">New voice style</x-button>
            </x-slot:action>
        </x-empty-state>
    </x-panel>
@else
    <div class="flex flex-col gap-3">
        @foreach ($presets as $preset)
            <x-panel>
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg">{{ $preset->name }}</h3>
                            @if ($preset->is_default)
                                <span class="status status-neutral">Default</span>
                            @endif
                        </div>
                        <p class="mt-1 max-w-2xl text-sm text-muted">{{ \Illuminate\Support\Str::limit($preset->instruction, 160) }}</p>
                    </div>

                    <div class="flex shrink-0 items-center gap-2">
                        @if ($preset->is_default && ! auth()->user()->isAdmin())
                            <x-button variant="secondary" size="sm" icon="lock" disabled aria-disabled="true">Admin only</x-button>
                        @else
                            <x-button :href="route('style-presets.edit', $preset->id)" variant="secondary" size="sm" icon="pen">Edit</x-button>
                            <form method="POST" action="{{ route('style-presets.destroy', $preset->id) }}" onsubmit="return confirm('Delete this preset?{{ $preset->is_default ? ' This is a system preset!' : '' }}')">
                                @csrf
                                @method('DELETE')
                                <x-button type="submit" variant="danger" size="sm" icon="trash">Delete</x-button>
                            </form>
                        @endif
                    </div>
                </div>
            </x-panel>
        @endforeach
    </div>
@endif
@endsection
