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
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="space-y-2">
                                <x-ui.heading level="h3" size="md">
                                    {{ str($report->reason->value)->headline() }} report
                                </x-ui.heading>
                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                    Reported by {{ $report->reporter->name }} · Target: {{ str(class_basename($report->reportable_type))->headline() }}
                                </div>
                                @if ($report->created_at)
                                    <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                        Submitted {{ $report->created_at->format('M j, Y') }}
                                    </div>
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <x-ui.badge variant="outline" color="neutral" icon="flag">{{ str($report->status->value)->headline() }}</x-ui.badge>
                                @if ($report->reviewed_at)
                                    <x-ui.badge variant="outline" color="slate" icon="calendar-days">{{ $report->reviewed_at->format('M j, Y') }}</x-ui.badge>
                                @endif
                            </div>
                        </div>

                        <div class="grid gap-3">
                            @if ($report->reportable instanceof \App\Models\Review)
                                <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="space-y-1">
                                            <div class="font-medium">
                                                <a href="{{ route('public.titles.show', $report->reportable->title) }}" class="hover:opacity-80">
                                                    {{ $report->reportable->headline ?: 'Untitled review' }}
                                                </a>
                                            </div>
                                            <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                                {{ $report->reportable->author->name }} on {{ $report->reportable->title->name }}
                                            </div>
                                        </div>

                                        <x-ui.badge variant="outline" color="neutral" icon="chat-bubble-left-right">
                                            {{ $report->reportable->status->label() }}
                                        </x-ui.badge>
                                    </div>

                                    <x-ui.text class="mt-3 text-sm text-neutral-600 dark:text-neutral-300">
                                        {{ str($report->reportable->body)->limit(220) }}
                                    </x-ui.text>
                                </div>
                            @elseif ($report->reportable instanceof \App\Models\UserList)
                                <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="space-y-1">
                                            <div class="font-medium">
                                                @if ($report->reportable->isShareable())
                                                    <a href="{{ route('public.lists.show', ['user' => $report->reportable->user, 'list' => $report->reportable]) }}" class="hover:opacity-80">
                                                        {{ $report->reportable->name }}
                                                    </a>
                                                @else
                                                    {{ $report->reportable->name }}
                                                @endif
                                            </div>
                                            <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                                {{ $report->reportable->user->name }} · {{ $report->reportable->visibility->label() }} visibility
                                            </div>
                                        </div>

                                        <x-ui.badge variant="outline" color="neutral" icon="queue-list">
                                            {{ number_format((int) ($report->reportable->items_count ?? 0)) }} titles
                                        </x-ui.badge>
                                    </div>

                                    @if (filled($report->reportable->description))
                                        <x-ui.text class="mt-3 text-sm text-neutral-600 dark:text-neutral-300">
                                            {{ str($report->reportable->description)->limit(220) }}
                                        </x-ui.text>
                                    @endif
                                </div>
                            @endif

                            @if (filled($report->details))
                                <div class="rounded-box border border-dashed border-black/10 px-4 py-3 text-sm text-neutral-600 dark:border-white/10 dark:text-neutral-300">
                                    {{ $report->details }}
                                </div>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('admin.reports.update', $report) }}" class="grid gap-4 xl:grid-cols-[220px,220px,minmax(0,1fr),auto]">
                            @csrf
                            @method('PATCH')
                            <select
                                name="status"
                                class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
                            >
                                @foreach ($reportStatuses as $status)
                                    <option value="{{ $status->value }}" @selected($report->status === $status)>
                                        {{ str($status->value)->headline() }}
                                    </option>
                                @endforeach
                            </select>

                            <select
                                name="content_action"
                                class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
                            >
                                <option value="none">No content action</option>
                                <option value="hide_content">Hide reported content</option>
                            </select>

                            <x-ui.input name="resolution_notes" :value="$report->resolution_notes" placeholder="Resolution notes" />

                            <x-ui.button type="submit" icon="check-circle">
                                Update
                            </x-ui.button>

                            @if ($report->reportableOwner() && ! $report->reportableOwner()->canAccessAdminPanel())
                                <label class="xl:col-span-4 flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-300">
                                    <input type="checkbox" name="suspend_owner" value="1" class="rounded border-black/20 dark:border-white/20">
                                    <span>Suspend {{ $report->reportableOwner()->name }} from public activity.</span>
                                </label>
                            @endif
                        </form>

                        @if ($report->reviewer)
                            <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                Reviewed by {{ $report->reviewer->name }}
                                @if ($report->reviewed_at)
                                    · {{ $report->reviewed_at->format('M j, Y') }}
                                @endif
                            </div>
                        @endif
                    </div>
                </x-ui.card>
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
