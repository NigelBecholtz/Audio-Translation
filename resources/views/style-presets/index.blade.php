@extends('layouts.app')

@section('title', 'My Style Presets')

@section('content')
<div class="px-4 py-6 sm:px-6">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8 fade-in">
            <div>
                <h1 class="text-4xl font-bold text-white mb-2">Style Instruction Presets</h1>
                <p class="text-lg text-gray-300">Manage your custom voice style presets</p>
            </div>
            <a href="{{ route('style-presets.create') }}" class="px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl font-bold">
                <i class="fas fa-plus mr-2"></i>
                New Preset
            </a>
        </div>

        <!-- Presets Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($presets as $preset)
                <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-6 hover:border-blue-400/50 transition-all fade-in">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-white mb-2">
                                @if($preset->is_default)
                                    <i class="fas fa-star text-yellow-400 mr-2"></i>
                                @else
                                    <i class="fas fa-bookmark text-blue-400 mr-2"></i>
                                @endif
                                {{ $preset->name }}
                            </h3>
                            @if($preset->is_default)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Default Preset
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <p class="text-sm text-gray-300 leading-relaxed line-clamp-3">
                            {{ $preset->instruction }}
                        </p>
                    </div>
                    
                    <div class="flex gap-2">
                        @if($preset->is_default && !auth()->user()->isAdmin())
                            <button disabled 
                                    class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-gray-600 text-gray-400 rounded-lg cursor-not-allowed font-medium">
                                <i class="fas fa-lock mr-2"></i>
                                Admin Only
                            </button>
                        @else
                            <a href="{{ route('style-presets.edit', $preset->id) }}" 
                               class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition font-medium">
                                <i class="fas fa-edit mr-2"></i>
                                Edit
                            </a>
                            
                            <form method="POST" action="{{ route('style-presets.destroy', $preset->id) }}" 
                                  onsubmit="return confirm('Delete this preset?{{ $preset->is_default ? ' This is a system preset!' : '' }}')" class="flex-1">
                                @csrf
                                @method('DELETE')
                                <button type="submit" 
                                        class="w-full inline-flex items-center justify-center px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition font-medium">
                                    <i class="fas fa-trash mr-2"></i>
                                    Delete
                                </button>
                            </form>
                        @endif
                    </div>
                    
                    <div class="mt-3 text-xs text-gray-400">
                        Created: {{ $preset->created_at->format('d-m-Y H:i') }}
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-12 text-center">
                    <i class="fas fa-inbox text-6xl text-gray-600 mb-4"></i>
                    <h3 class="text-2xl font-bold text-white mb-2">No Presets Yet</h3>
                    <p class="text-gray-300 mb-6">Create your first style instruction preset to get started!</p>
                    <a href="{{ route('style-presets.create') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg font-bold">
                        <i class="fas fa-plus mr-2"></i>
                        Create Your First Preset
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection

