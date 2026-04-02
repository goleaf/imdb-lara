@extends('layouts.public')

@section('title', 'Create account')
@section('shell_variant', 'auth')
@section('show_footer', '0')

@section('content')
    <section class="w-full max-w-[32rem]">
        <div class="sb-auth-card rounded-[1.75rem] px-5 py-6 sm:px-7 sm:py-8">
            <div class="space-y-7">
                <div class="space-y-5">
                    <div class="inline-flex items-center gap-3">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-full border border-[#d6b574]/25 bg-white/4 text-[0.72rem] font-semibold uppercase tracking-[0.3em] text-[#d6b574]">
                            SB
                        </span>

                        <div class="min-w-0">
                            <div class="sb-auth-kicker">Create Your Identity</div>
                            <div class="sb-auth-section-title">Screenbase</div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <h1 class="sb-auth-title">Create your Screenbase account</h1>
                        <p class="sb-auth-copy">
                            Start a premium member profile for watchlists, ratings, curated lists, and moderated reviews without the usual clutter.
                        </p>
                    </div>
                </div>

                <nav class="sb-auth-toggle" aria-label="Authentication pages">
                    <a href="{{ route('login') }}" class="sb-auth-toggle-link" data-active="false">Sign in</a>
                    <a href="{{ route('register') }}" class="sb-auth-toggle-link" data-active="true">Sign up</a>
                </nav>

                <div class="space-y-3">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <button type="button" class="sb-auth-social-button" disabled aria-disabled="true">
                            <span class="sb-auth-social-button-badge">A</span>
                            <span>
                                <span class="block text-sm font-semibold text-[#f4eee5]">Continue with Apple</span>
                                <span class="mt-0.5 block text-xs text-[#8f877a]">Available soon</span>
                            </span>
                        </button>

                        <button type="button" class="sb-auth-social-button" disabled aria-disabled="true">
                            <span class="sb-auth-social-button-badge">G</span>
                            <span>
                                <span class="block text-sm font-semibold text-[#f4eee5]">Continue with Google</span>
                                <span class="mt-0.5 block text-xs text-[#8f877a]">Available soon</span>
                            </span>
                        </button>
                    </div>

                    <p class="sb-auth-note">
                        Social entry is being prepared. You can create a full member account with email right away.
                    </p>
                </div>

                <div class="sb-auth-divider">
                    <span>Or create with email</span>
                </div>

                <livewire:auth.register-form />
            </div>
        </div>
    </section>
@endsection
