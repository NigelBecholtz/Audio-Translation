<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Card extends Component
{
    public string $classes;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $title = null,
        public string $padding = 'default',
        public string $border = 'default'
    ) {
        $this->classes = $this->getClasses();
    }

    /**
     * Get the classes for the card.
     */
    private function getClasses(): string
    {
        $paddingClasses = match($this->padding) {
            'none' => '',
            'sm' => 'p-4',
            'md' => 'p-6',
            'lg' => 'p-8',
            default => 'p-6',
        };

        $borderClasses = match($this->border) {
            'none' => '',
            'primary' => 'border-2 border-blue-500',
            'success' => 'border-2 border-green-500',
            'danger' => 'border-2 border-red-500',
            default => 'border-2 border-gray-600',
        };

        return "bg-gray-800 rounded-2xl shadow-xl $borderClasses $paddingClasses";
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.card');
    }
}