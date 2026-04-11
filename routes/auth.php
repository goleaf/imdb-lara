<?php

use App\Http\Requests\Auth\LogoutRequest;
use App\Livewire\Pages\Auth\AuthPage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::livewire('/login', AuthPage::class)->name('login');
    Route::livewire('/register', AuthPage::class)->name('register');
});

Route::middleware('auth')->post('/logout', function (LogoutRequest $request) {
    Auth::guard('web')->logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return to_route('public.home');
})->name('logout');
