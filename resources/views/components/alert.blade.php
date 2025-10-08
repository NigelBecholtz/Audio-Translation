<div {{ $attributes->merge(['class' => $classes]) }}>
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="{{ $icon }}"></i>
        </div>
        <div class="ml-3 flex-1">
            {{ $slot }}
        </div>
        @if($dismissible)
            <button type="button" class="ml-auto -mx-1.5 -my-1.5 rounded-lg p-1.5 inline-flex h-8 w-8 hover:bg-gray-100" onclick="this.parentElement.parentElement.style.display='none'">
                <span class="sr-only">Sluiten</span>
                <i class="fas fa-times"></i>
            </button>
        @endif
    </div>
</div>