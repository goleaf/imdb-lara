<?php

use App\Livewire\Pages\Auth\LoginPage;
use App\Livewire\Pages\Auth\RegisterPage;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::livewire('/login', LoginPage::class)->name('login');
    Route::livewire('/register', RegisterPage::class)->name('register');
});
