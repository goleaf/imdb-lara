@extends('layouts.public')

@section('title', $person->name)
@section('meta_description', $person->biography ?: 'Browse credits and biography for '.$person->name.'.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.people.index')">People</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $person->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    @php
        $headshot = $person->mediaAssets->first();
    @endphp

    <section class="grid gap-6 xl:grid-cols-[minmax(0,0.7fr)_minmax(0,1.3fr)]">
        <x-ui.card class="!max-w-none overflow-hidden">
            @if ($headshot)
                <img
                    src="{{ $headshot->url }}"
                    alt="{{ $headshot->alt_text ?: $person->name }}"
                    class="aspect-[3/4] w-full rounded-box object-cover"
                >
            @else
                <div class="flex aspect-[3/4] items-center justify-center rounded-box bg-neutral-100 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">
                    <x-ui.icon name="user" class="size-12" />
                </div>
            @endif
        </x-ui.card>

        <x-ui.card class="!max-w-none">
            <div class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($person->known_for_department)
                        <x-ui.badge variant="outline">{{ $person->known_for_department }}</x-ui.badge>
                    @endif

                    @if ($person->birth_place)
                        <x-ui.badge variant="outline" color="slate">{{ $person->birth_place }}</x-ui.badge>
                    @endif
                </div>

                @if ($person->professions->isNotEmpty())
                    <div class="flex flex-wrap gap-2">
                        @foreach ($person->professions as $profession)
                            <x-ui.badge variant="outline" color="neutral">{{ $profession->profession }}</x-ui.badge>
                        @endforeach
                    </div>
                @endif

                <div class="space-y-2">
                    <x-ui.heading level="h1" size="xl">{{ $person->name }}</x-ui.heading>
                    <x-ui.text class="text-base text-neutral-600 dark:text-neutral-300">
                        {{ $person->biography ?: 'No biography has been published for this person yet.' }}
                    </x-ui.text>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                        <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Birth date</div>
                        <div class="mt-2 text-lg font-semibold">{{ $person->birth_date?->format('M j, Y') ?: 'Unknown' }}</div>
                    </div>
                    <div class="rounded-box border border-black/5 p-3 dark:border-white/10">
                        <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Credits</div>
                        <div class="mt-2 text-lg font-semibold">{{ number_format($person->credits->count()) }}</div>
                    </div>
                </div>
            </div>
        </x-ui.card>
    </section>

    <section class="space-y-4">
        <x-ui.heading level="h2" size="lg">Credits</x-ui.heading>

        <div class="grid gap-3">
            @forelse ($person->credits as $credit)
                <x-ui.card class="!max-w-none">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <x-ui.heading level="h3" size="md">
                                <a href="{{ route('public.titles.show', $credit->title) }}" class="hover:opacity-80">
                                    {{ $credit->title->name }}
                                </a>
                            </x-ui.heading>
                            <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                {{ str($credit->title->title_type->value)->headline() }} ·
                                @if ($credit->title->release_year)
                                    <a href="{{ route('public.years.show', ['year' => $credit->title->release_year]) }}" class="hover:opacity-80">
                                        {{ $credit->title->release_year }}
                                    </a>
                                @else
                                    TBA
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <x-ui.badge variant="outline" color="neutral">{{ $credit->department }}</x-ui.badge>
                            <x-ui.badge variant="outline" color="slate">{{ $credit->job }}</x-ui.badge>
                        </div>
                    </div>
                </x-ui.card>
            @empty
                <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                    <x-ui.heading level="h3">No credits have been published yet.</x-ui.heading>
                </x-ui.empty>
            @endforelse
        </div>
    </section>
@endsection
