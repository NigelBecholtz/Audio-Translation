@extends('layouts.app')

@section('title', 'Edit Style Preset')

@section('content')
<div class="px-4 py-6 sm:px-6">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="mb-8 fade-in">
            <a href="{{ route('style-presets.index') }}" class="text-blue-400 hover:text-blue-300 mb-4 inline-block">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Presets
            </a>
            <h1 class="text-4xl font-bold text-white mb-2">
                Edit Style Preset
                @if($preset->is_default)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 ml-3">
                        <i class="fas fa-star mr-1"></i>
                        System Preset
                    </span>
                @endif
            </h1>
            <p class="text-lg text-gray-300">
                @if($preset->is_default)
                    You are editing a system preset (visible to all users)
                @else
                    Update your style instruction
                @endif
            </p>
        </div>

        <!-- Form -->
        <div class="bg-gray-800/90 backdrop-blur-sm shadow-xl rounded-2xl border border-gray-600/30 p-8 fade-in">
            <form method="POST" action="{{ route('style-presets.update', $preset->id) }}" class="space-y-6">
                @csrf
                @method('PUT')
                
                <!-- Preset Name -->
                <div>
                    <label for="name" class="block text-xl font-bold text-white mb-3">
                        <i class="fas fa-tag mr-2 text-blue-400"></i>
                        Preset Name
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name', $preset->name) }}"
                           required
                           maxlength="100"
                           class="w-full px-6 py-4 text-lg border-2 border-blue-300 rounded-xl focus:outline-none focus:ring-4 focus:ring-blue-400 focus:border-blue-500 transition-all bg-white shadow-lg">
                    @error('name')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Style Instruction -->
                <div>
                    <label for="instruction" class="block text-xl font-bold text-white mb-3">
                        <i class="fas fa-microphone mr-2 text-purple-400"></i>
                        Style Instruction
                    </label>
                    <textarea id="instruction" 
                              name="instruction" 
                              rows="6"
                              required
                              maxlength="5000"
                              class="w-full px-6 py-4 text-lg border-2 border-purple-300 rounded-xl focus:outline-none focus:ring-4 focus:ring-purple-400 focus:border-purple-500 transition-all bg-white shadow-lg resize-none">{{ old('instruction', $preset->instruction) }}</textarea>
                    @error('instruction')
                        <p class="mt-2 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Actions -->
                <div class="flex gap-4">
                    <button type="submit" 
                            class="flex-1 inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl font-bold text-lg">
                        <i class="fas fa-save mr-2"></i>
                        Update Preset
                    </button>
                    <a href="{{ route('style-presets.index') }}" 
                       class="flex-1 inline-flex items-center justify-center px-8 py-4 bg-gray-600 text-white rounded-xl hover:bg-gray-500 transition-all shadow-lg font-bold text-lg">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

