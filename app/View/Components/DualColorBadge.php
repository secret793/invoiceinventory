<?php

namespace App\View\Components;

use Illuminate\View\Component;

class DualColorBadge extends Component
{
    public string $text;
    public float $receivedRatio;
    public float $otherRatio;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(string $text, float $receivedRatio, float $otherRatio)
    {
        $this->text = $text;
        $this->receivedRatio = $receivedRatio;
        $this->otherRatio = $otherRatio;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.dual-color-badge');
    }
}