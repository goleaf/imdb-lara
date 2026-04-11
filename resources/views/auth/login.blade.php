@extends('layouts.public')

@section('title', 'Sign in')
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
                            <div class="sb-auth-kicker">Member Access</div>
                            <div class="sb-auth-section-title">Screenbase</div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <h1 class="sb-auth-title">Sign in to Screenbase</h1>
                        <p class="sb-auth-copy">
                            Return to your private watchlist, ratings, reviews, and editorial profile in one calm member entry.
                        </p>
                    </div>
                </div>

                <nav class="sb-auth-toggle" aria-label="Authentication pages">
                    <a href="{{ route('login') }}" class="sb-auth-toggle-link" data-active="true">Sign in</a>
                    <a href="{{ route('register') }}" class="sb-auth-toggle-link" data-active="false">Sign up</a>
                </nav>

                <div class="space-y-3">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-ui.button
                            type="button"
                            variant="none"
                            disabled
                            class="sb-auth-social-option !h-auto !w-full !justify-start !rounded-[1rem] !px-4 !py-4 text-left"
                        >
                            <span class="sb-auth-social-button-badge">A</span>
                            <span>
                                <span class="block text-sm font-semibold text-[#f4eee5]">Continue with Apple</span>
                                <span class="mt-0.5 block text-xs text-[#8f877a]">Available soon</span>
                            </span>
                        </x-ui.button>

                        <x-ui.button
                            type="button"
                            variant="none"
                            disabled
                            class="sb-auth-social-option !h-auto !w-full !justify-start !rounded-[1rem] !px-4 !py-4 text-left"
                        >
                            <span class="sb-auth-social-button-badge">G</span>
                            <span>
                                <span class="block text-sm font-semibold text-[#f4eee5]">Continue with Google</span>
                                <span class="mt-0.5 block text-xs text-[#8f877a]">Available soon</span>
                            </span>
                        </x-ui.button>
                    </div>

                    <p class="sb-auth-note">
                        Social entry is being prepared. Email access is available now for every Screenbase member.
                    </p>
                </div>

                <div class="sb-auth-divider">
                    <span>Or continue with email</span>
                </div>

                <livewire:auth.login-form />
            </div>
        </div>
    </section>
@endsection
