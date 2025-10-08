<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Button extends Component
{
    public string $variant;
    public string $size;
    public string $classes;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $variant = 'primary',
        string $size = 'md',
        public ?string $type = 'button',
        public ?string $href = null,
        public bool $disabled = false
    ) {
        $this->variant = $variant;
        $this->size = $size;
        $this->classes = $this->getClasses();
    }

    /**
     * Get the classes for the button based on variant and size.
     */
    private function getClasses(): string
    {
        $variantClasses = match($this->variant) {
            'primary' => 'bg-gradient-to-r from-gray-700 to-gray-800 text-white hover:from-gray-600 hover:to-gray-700 shadow-lg hover:shadow-xl',
            'secondary' => 'bg-gray-600 text-gray-100 hover:bg-gray-500 border-2 border-gray-500 hover:border-gray-400',
            'success' => 'bg-gradient-to-r from-green-500 to-green-600 text-white hover:from-green-600 hover:to-green-700 shadow-lg hover:shadow-xl',
            'danger' => 'bg-red-600 text-red-100 hover:bg-red-500 border-2 border-red-500 hover:border-red-400',
            'warning' => 'bg-yellow-600 text-yellow-100 hover:bg-yellow-500 border-2 border-yellow-500 hover:border-yellow-400',
            default => 'bg-gray-600 text-gray-100 hover:bg-gray-500',
        };

        $sizeClasses = match($this->size) {
            'sm' => 'px-3 py-1.5 text-sm',
            'md' => 'px-4 py-2 text-base',
            'lg' => 'px-6 py-3 text-lg',
            'xl' => 'px-8 py-4 text-xl',
            default => 'px-4 py-2 text-base',
        };

        $baseClasses = 'inline-flex items-center gap-2 font-bold rounded-xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed';

        return "$baseClasses $variantClasses $sizeClasses";
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.button');
    }
}