@extends('layouts.public')

@section('title', 'Create account')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Create account</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <div class="mx-auto w-full max-w-xl">
        <x-ui.card class="!max-w-none">
            <div class="mb-6 space-y-2">
                <x-ui.heading level="h1" size="xl">Create account</x-ui.heading>
                <x-ui.text class="text-neutral-600 dark:text-neutral-300">
                    Create a member account to save titles, rate releases, and publish moderated reviews.
                </x-ui.text>
            </div>

            <livewire:auth.register-form />
        </x-ui.card>
    </div>
@endsection
