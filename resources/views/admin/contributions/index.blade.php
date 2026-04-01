@extends('layouts.admin')

@section('title', 'Contributions Queue')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Contributions Queue</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div>
            <x-ui.heading level="h1" size="xl">Contributions Queue</x-ui.heading>
            <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                Review structured catalog contributions before they are accepted into the database.
            </x-ui.text>
        </div>

        <div class="grid gap-4">
            @forelse ($contributions as $contribution)
                @php
                    $contributable = $contribution->contributable;
                @endphp
                <x-ui.card class="!max-w-none">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="space-y-2">
                                <x-ui.heading level="h3" size="md">{{ str($contribution->action->value)->headline() }} contribution</x-ui.heading>
                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $contribution->user->name }} · {{ class_basename($contribution->contributable_type) }} suggestion
                                </div>
                                @if ($contributable instanceof \App\Models\Title)
                                    <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                        <a href="{{ route('admin.titles.edit', $contributable) }}" class="hover:opacity-80">
                                            {{ $contributable->name }}
                                        </a>
                                    </div>
                                @elseif ($contributable instanceof \App\Models\Person)
                                    <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                        <a href="{{ route('admin.people.edit', $contributable) }}" class="hover:opacity-80">
                                            {{ $contributable->name }}
                                        </a>
                                    </div>
                                @endif
                            </div>

                            <x-ui.badge variant="outline" icon="clipboard-document-check">
                                {{ str($contribution->status->value)->headline() }}
                            </x-ui.badge>
                        </div>

                        <div class="grid gap-3">
                            <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                <div class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Field</div>
                                <div class="mt-1 text-sm text-neutral-800 dark:text-neutral-100">
                                    {{ $contribution->proposed_field_label ?: 'General catalog correction' }}
                                </div>
                            </div>

                            @if ($contribution->proposed_value)
                                <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                    <div class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Proposed update</div>
                                    <div class="mt-1 text-sm text-neutral-800 dark:text-neutral-100">
                                        {{ $contribution->proposed_value }}
                                    </div>
                                </div>
                            @endif

                            @if ($contribution->submission_notes)
                                <div class="rounded-box border border-dashed border-black/10 px-4 py-3 text-sm text-neutral-600 dark:border-white/10 dark:text-neutral-300">
                                    {{ $contribution->submission_notes }}
                                </div>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('admin.contributions.update', $contribution) }}" class="grid gap-4 md:grid-cols-[220px,1fr,auto]">
                            @csrf
                            @method('PATCH')
                            <select
                                name="status"
                                class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
                            >
                                @foreach ($contributionStatuses as $status)
                                    <option value="{{ $status->value }}" @selected($contribution->status === $status)>
                                        {{ str($status->value)->headline() }}
                                    </option>
                                @endforeach
                            </select>

                            <x-ui.input name="notes" :value="$contribution->notes" placeholder="Review notes" />

                            <x-ui.button type="submit" icon="check-circle">
                                Update
                            </x-ui.button>
                        </form>

                        @if ($contribution->review_notes)
                            <div class="rounded-box border border-black/5 px-4 py-3 text-sm text-neutral-600 dark:border-white/10 dark:text-neutral-300">
                                {{ $contribution->review_notes }}
                            </div>
                        @endif
                    </div>
                </x-ui.card>
            @empty
                <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                    <x-ui.empty.media>
                        <x-ui.icon name="clipboard-document-check" class="size-8 text-neutral-400 dark:text-neutral-500" />
                    </x-ui.empty.media>
                    <x-ui.heading level="h3">No contributions are queued.</x-ui.heading>
                </x-ui.empty>
            @endforelse
        </div>

        <div>
            {{ $contributions->links() }}
        </div>
    </section>
@endsection
