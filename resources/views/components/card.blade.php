<div {{ $attributes->merge(['class' => $classes]) }}>
    @if($title)
        <h3 class="text-xl font-bold text-white mb-4">
            {{ $title }}
        </h3>
    @endif
    
    {{ $slot }}
</div>