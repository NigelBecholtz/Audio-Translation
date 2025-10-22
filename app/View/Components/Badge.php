<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Badge extends Component
{
    public string $classes;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $variant = 'default',
        public ?string $icon = null
    ) {
        $this->classes = $this->getClasses();
    }

    /**
     * Get the classes for the badge based on variant.
     */
    private function getClasses(): string
    {
        $variantClasses = match($this->variant) {
            'success' => 'bg-green-600 text-white border-2 border-green-500',
            'danger' => 'bg-red-600 text-white border-2 border-red-500',
            'warning' => 'bg-yellow-600 text-white border-2 border-yellow-500',
            'info' => 'bg-blue-600 text-white border-2 border-blue-500',
            'processing' => 'bg-yellow-600 text-white border-2 border-yellow-500 pulse-animation',
            default => 'bg-gray-600 text-gray-100 border-2 border-gray-500',
        };

        return "inline-flex items-center px-4 py-2 rounded-full text-sm font-bold $variantClasses";
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.badge');
    }
}