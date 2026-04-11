<?php

use App\Livewire\Pages\Auth\AuthPage;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::livewire('/login', AuthPage::class)->name('login');
    Route::livewire('/register', AuthPage::class)->name('register');
});
