@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Dashboard</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div>
            <x-ui.heading level="h1" size="xl">Admin Dashboard</x-ui.heading>
            <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                Operational overview for catalog publishing, moderation workload, and report handling.
            </x-ui.text>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <x-ui.card class="!max-w-none">
                <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Titles</div>
                <div class="mt-2 text-3xl font-semibold">{{ number_format($stats['titles']) }}</div>
            </x-ui.card>
            <x-ui.card class="!max-w-none">
                <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Pending Reviews</div>
                <div class="mt-2 text-3xl font-semibold">{{ number_format($stats['pending_reviews']) }}</div>
            </x-ui.card>
            <x-ui.card class="!max-w-none">
                <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Open Reports</div>
                <div class="mt-2 text-3xl font-semibold">{{ number_format($stats['open_reports']) }}</div>
            </x-ui.card>
        </div>
    </section>
@endsection
