<?php

namespace App\Http\Controllers;

use App\Models\StyleInstructionPreset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StylePresetController extends Controller
{
    /**
     * Display a listing of the user's presets
     */
    public function index()
    {
        $presets = StyleInstructionPreset::forUser(auth()->id())
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('style-presets.index', compact('presets'));
    }

    /**
     * Show the form for creating a new preset
     */
    public function create()
    {
        return view('style-presets.create');
    }

    /**
     * Store a newly created preset
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'instruction' => 'required|string|max:5000',
        ]);

        $preset = StyleInstructionPreset::create([
            'user_id' => auth()->id(),
            'name' => $validated['name'],
            'instruction' => $validated['instruction'],
            'is_default' => false,
        ]);

        Log::info('Style preset created', [
            'user_id' => auth()->id(),
            'preset_id' => $preset->id,
            'name' => $preset->name
        ]);

        return redirect()->route('style-presets.index')
            ->with('success', 'Style preset created successfully!');
    }

    /**
     * Show the form for editing a preset
     */
    public function edit($id)
    {
        $preset = auth()->user()->stylePresets()->findOrFail($id);
        
        // Cannot edit default presets
        if ($preset->is_default) {
            return redirect()->route('style-presets.index')
                ->with('error', 'Default presets cannot be edited.');
        }
        
        return view('style-presets.edit', compact('preset'));
    }

    /**
     * Update the specified preset
     */
    public function update(Request $request, $id)
    {
        $preset = auth()->user()->stylePresets()->findOrFail($id);
        
        // Cannot edit default presets
        if ($preset->is_default) {
            return redirect()->route('style-presets.index')
                ->with('error', 'Default presets cannot be edited.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'instruction' => 'required|string|max:5000',
        ]);

        $preset->update($validated);

        return redirect()->route('style-presets.index')
            ->with('success', 'Style preset updated successfully!');
    }

    /**
     * Remove the specified preset
     */
    public function destroy($id)
    {
        $preset = auth()->user()->stylePresets()->findOrFail($id);
        
        // Cannot delete default presets
        if ($preset->is_default) {
            return redirect()->route('style-presets.index')
                ->with('error', 'Default presets cannot be deleted.');
        }

        $preset->delete();

        return redirect()->route('style-presets.index')
            ->with('success', 'Style preset deleted successfully!');
    }

    /**
     * Get presets as JSON for AJAX
     */
    public function getPresets()
    {
        $presets = StyleInstructionPreset::forUser(auth()->id())
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get(['id', 'name', 'instruction', 'is_default']);

        return response()->json($presets);
    }
}
