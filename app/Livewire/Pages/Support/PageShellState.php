<?php

namespace App\Livewire\Pages\Support;

class PageShellState
{
    /**
     * @var array<string, mixed>
     */
    private array $layoutData = [];

    /**
     * @param  array<string, mixed>  $layoutData
     */
    public function replace(array $layoutData): void
    {
        $this->layoutData = $layoutData;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->layoutData;
    }
}
