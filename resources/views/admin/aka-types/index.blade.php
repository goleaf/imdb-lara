@extends('layouts.admin')

@section('title', 'Manage AKA Types')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Manage AKA Types</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        @if (session('status'))
            <x-ui.alerts variant="success" icon="check-circle">
                <x-ui.alerts.description>{{ session('status') }}</x-ui.alerts.description>
            </x-ui.alerts>
        @endif

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <x-ui.heading level="h1" size="xl">Manage AKA Types</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                    Curate imported alternate-title classifications used by title detail pages.
                </x-ui.text>
            </div>

            <x-ui.button as="a" :href="route('admin.aka-types.create')" icon="plus">
                New AKA type
            </x-ui.button>
        </div>

        <x-ui.card class="!max-w-none">
            <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                <x-ui.field>
                    <x-ui.label>Search AKA types</x-ui.label>
                    <x-ui.input
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by label or numeric id"
                        left-icon="magnifying-glass"
                    />
                </x-ui.field>

                @if ($hasActiveFilters)
                    <x-ui.button type="button" wire:click="$set('search', '')" variant="ghost" icon="x-mark">
                        Clear
                    </x-ui.button>
                @endif
            </div>
        </x-ui.card>

        <x-ui.card class="!max-w-none overflow-hidden p-0">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-black/5 text-left text-sm dark:divide-white/10">
                    <thead class="bg-black/5 dark:bg-white/5">
                        <tr class="text-neutral-500 dark:text-neutral-400">
                            <th class="px-4 py-3 font-medium">Type</th>
                            <th class="px-4 py-3 font-medium">Linked AKA rows</th>
                            <th class="px-4 py-3 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black/5 dark:divide-white/10">
                        @forelse ($akaTypes as $akaType)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ $akaType->resolvedLabel() }}</div>
                                    <div class="text-xs text-neutral-500 dark:text-neutral-400">
                                        {{ $akaType->name }} · #{{ $akaType->getKey() }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">{{ number_format($akaType->movieAkaUsageCount()) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <x-ui.link :href="route('admin.aka-types.edit', $akaType)" variant="ghost" iconAfter="arrow-right">
                                        Edit
                                    </x-ui.link>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8">
                                    <x-ui.empty>
                                        <x-ui.empty.media>
                                            <x-ui.icon name="queue-list" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                        </x-ui.empty.media>
                                        <x-ui.heading level="h3">No AKA types are available.</x-ui.heading>
                                    </x-ui.empty>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <div>
            {{ $akaTypes->links() }}
        </div>
    </section>
@endsection
