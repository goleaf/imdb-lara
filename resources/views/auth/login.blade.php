@extends('layouts.public')

@section('title', 'Sign in')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Sign in</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <div class="mx-auto w-full max-w-xl">
        <x-ui.card class="!max-w-none">
            <div class="mb-6 space-y-2">
                <x-ui.heading level="h1" size="xl">Sign in</x-ui.heading>
                <x-ui.text class="text-neutral-600 dark:text-neutral-300">
                    Access your watchlist, ratings, reviews, and staff surfaces.
                </x-ui.text>
            </div>

            <livewire:auth.login-form />
        </x-ui.card>
    </div>
@endsection
