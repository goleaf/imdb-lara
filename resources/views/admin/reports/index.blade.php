@extends('layouts.admin')

@section('title', 'Reports')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Reports</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div>
            <x-ui.heading level="h1" size="xl">Reports</x-ui.heading>
            <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                Triage abuse, spoiler, spam, and accuracy reports submitted by members and moderators.
            </x-ui.text>
        </div>

        <div class="grid gap-4">
            @forelse ($reports as $report)
                <livewire:admin.report-moderation-card :report="$report" :key="'admin-report-'.$report->id" />
            @empty
                <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                    <x-ui.empty.media>
                        <x-ui.icon name="flag" class="size-8 text-neutral-400 dark:text-neutral-500" />
                    </x-ui.empty.media>
                    <x-ui.heading level="h3">No reports are currently queued.</x-ui.heading>
                </x-ui.empty>
            @endforelse
        </div>

        <div>
            {{ $reports->links() }}
        </div>
    </section>
@endsection
