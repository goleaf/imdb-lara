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
                <x-ui.card class="!max-w-none">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="space-y-2">
                            <x-ui.heading level="h3" size="md">
                                {{ str($report->reason->value)->headline() }} report
                            </x-ui.heading>
                            <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                Reported by {{ $report->reporter->name }} · Target: {{ str(class_basename($report->reportable_type))->headline() }}
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <x-ui.badge variant="outline" color="neutral">{{ str($report->status->value)->headline() }}</x-ui.badge>
                            @if ($report->reviewed_at)
                                <x-ui.badge variant="outline" color="slate">{{ $report->reviewed_at->format('M j, Y') }}</x-ui.badge>
                            @endif
                        </div>
                    </div>
                </x-ui.card>
            @empty
                <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                    <x-ui.heading level="h3">No reports are currently queued.</x-ui.heading>
                </x-ui.empty>
            @endforelse
        </div>

        <div>
            {{ $reports->links() }}
        </div>
    </section>
@endsection
