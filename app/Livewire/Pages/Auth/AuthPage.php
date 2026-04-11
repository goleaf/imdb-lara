<?php

namespace App\Livewire\Pages\Auth;

use App\Livewire\Pages\Concerns\RendersPageView;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AuthPage extends Component
{
    use RendersPageView;

    protected function renderAuthPage(string $view): View
    {
        return $this->renderPageView($view);
    }
}
