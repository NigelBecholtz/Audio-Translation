<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Alert extends Component
{
    public string $classes;
    public string $icon;

    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $type = 'info',
        public bool $dismissible = false
    ) {
        $this->classes = $this->getClasses();
        $this->icon = $this->getIcon();
    }

    /**
     * Get the classes for the alert based on type.
     */
    private function getClasses(): string
    {
        $typeClasses = match($this->type) {
            'success' => 'bg-green-50 border-green-400 text-green-700',
            'error' => 'bg-red-50 border-red-400 text-red-700',
            'warning' => 'bg-yellow-50 border-yellow-400 text-yellow-700',
            'info' => 'bg-blue-50 border-blue-400 text-blue-700',
            default => 'bg-gray-50 border-gray-400 text-gray-700',
        };

        return "border-l-4 p-4 mb-6 rounded-r-lg shadow-sm fade-in $typeClasses";
    }

    /**
     * Get the icon for the alert type.
     */
    private function getIcon(): string
    {
        return match($this->type) {
            'success' => 'fas fa-check-circle',
            'error' => 'fas fa-exclamation-circle',
            'warning' => 'fas fa-exclamation-triangle',
            'info' => 'fas fa-info-circle',
            default => 'fas fa-info-circle',
        };
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.alert');
    }
}