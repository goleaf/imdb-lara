@extends('layouts.public')

@section('title', $person->meta_title ?: $person->name)
@section('meta_description', $person->meta_description ?: ($biographyIntro ?: 'Browse credits and biography for '.$person->name.'.'))

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.people.index')">People</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $person->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-ui.card class="!max-w-none overflow-hidden p-0">
            <div class="grid gap-6 p-6 xl:grid-cols-[14rem_minmax(0,1fr)]">
                <div class="flex justify-center xl:justify-start">
                    <x-ui.avatar
                        :src="$headshot?->url"
                        :alt="$headshot?->alt_text ?: $person->name"
                        :name="$person->name"
                        color="auto"
                        class="!h-[22rem] !w-56 border border-black/5 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800"
                    />
                </div>

                <div class="space-y-6">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($person->known_for_department)
                                <x-ui.badge variant="outline" icon="briefcase">{{ $person->known_for_department }}</x-ui.badge>
                            @endif

                            @if ($person->nationality)
                                <x-ui.badge variant="outline" color="slate" icon="globe-alt">{{ $person->nationality }}</x-ui.badge>
                            @endif

                            @if ($person->birth_place)
                                <x-ui.badge variant="outline" color="neutral" icon="map-pin">{{ $person->birth_place }}</x-ui.badge>
                            @endif
                        </div>

                        <div class="space-y-2">
                            <x-ui.heading level="h1" size="xl">{{ $person->name }}</x-ui.heading>

                            @if ($alternateNames->isNotEmpty())
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($alternateNames as $alternateName)
                                        <x-ui.badge variant="outline" color="neutral" icon="identification">{{ $alternateName }}</x-ui.badge>
                                    @endforeach
                                </div>
                            @endif

                            @if ($professionLabels->isNotEmpty())
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($professionLabels as $professionLabel)
                                        <x-ui.badge variant="outline" color="slate" icon="sparkles">{{ $professionLabel }}</x-ui.badge>
                                    @endforeach
                                </div>
                            @endif

                            @if ($biographyIntro)
                                <x-ui.text class="max-w-4xl text-base text-neutral-700 dark:text-neutral-200">
                                    {{ $biographyIntro }}
                                </x-ui.text>
                            @endif
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        @forelse ($detailItems as $item)
                            <div class="rounded-box border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/5">
                                <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">{{ $item['label'] }}</div>
                                <div class="mt-2 text-lg font-semibold">{{ $item['value'] }}</div>
                            </div>
                        @empty
                            <div class="rounded-box border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/5 sm:col-span-2 xl:col-span-4">
                                <div class="text-sm text-neutral-500 dark:text-neutral-400">Public metadata is still being curated.</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </x-ui.card>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <x-ui.card class="!max-w-none">
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-4">
                        <x-ui.heading level="h2" size="lg">Profile notes</x-ui.heading>
                        <x-ui.badge variant="outline" color="neutral" icon="sparkles">{{ number_format($professionLabels->count()) }} professions</x-ui.badge>
                    </div>

                    <x-ui.accordion class="rounded-box border border-black/5 dark:border-white/10">
                        <x-ui.accordion.item expanded>
                            <x-ui.accordion.trigger>
                                <span class="inline-flex items-center gap-2">
                                    <x-ui.icon name="document-text" class="size-4" />
                                    Biography
                                </span>
                            </x-ui.accordion.trigger>
                            <x-ui.accordion.content>
                                @if ($person->biography)
                                    <x-ui.text class="text-sm leading-7 text-neutral-600 dark:text-neutral-300">
                                        {{ $person->biography }}
                                    </x-ui.text>
                                @else
                                    <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                        <x-ui.empty.media>
                                            <x-ui.icon name="document-text" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                        </x-ui.empty.media>
                                        <x-ui.heading level="h3">No biography has been published yet.</x-ui.heading>
                                    </x-ui.empty>
                                @endif
                            </x-ui.accordion.content>
                        </x-ui.accordion.item>
                    </x-ui.accordion>
                </div>
            </x-ui.card>

            <x-ui.card class="!max-w-none">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <x-ui.heading level="h2" size="lg">Known for</x-ui.heading>
                        <x-ui.badge variant="outline" color="neutral" icon="film">{{ number_format($knownForTitles->count()) }} titles</x-ui.badge>
                    </div>

                    @if ($knownForTitles->isNotEmpty())
                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach ($knownForTitles as $title)
                                <x-catalog.title-card :title="$title" :showSummary="false" />
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.empty.media>
                                <x-ui.icon name="film" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">Known-for titles are still being ranked.</x-ui.heading>
                        </x-ui.empty>
                    @endif
                </div>
            </x-ui.card>
        </section>

        <livewire:contributions.suggestion-form
            contributableType="person"
            :contributableId="$person->id"
            :contributableLabel="$person->name"
            :key="'person-contribution-'.$person->id"
        />

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <x-ui.card class="!max-w-none">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <x-ui.heading level="h2" size="lg">Photo gallery</x-ui.heading>
                        <x-ui.badge variant="outline" color="neutral" icon="photo">{{ number_format($photoGallery->count()) }} assets</x-ui.badge>
                    </div>

                    @if ($photoGallery->isNotEmpty())
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ($photoGallery as $galleryAsset)
                                <div class="overflow-hidden rounded-box border border-black/5 bg-neutral-100 dark:border-white/10 dark:bg-neutral-800">
                                    <img
                                        src="{{ $galleryAsset->url }}"
                                        alt="{{ $galleryAsset->alt_text ?: $person->name }}"
                                        class="aspect-[4/3] w-full object-cover"
                                        loading="lazy"
                                    >
                                    @if (filled($galleryAsset->caption))
                                        <div class="border-t border-black/5 px-3 py-2 text-sm text-neutral-600 dark:border-white/10 dark:text-neutral-300">
                                            {{ $galleryAsset->caption }}
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                            <x-ui.empty.media>
                                <x-ui.icon name="photo" class="size-8 text-neutral-400 dark:text-neutral-500" />
                            </x-ui.empty.media>
                            <x-ui.heading level="h3">No gallery images are published yet.</x-ui.heading>
                        </x-ui.empty>
                    @endif
                </div>
            </x-ui.card>

            <x-ui.card class="!max-w-none">
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-4">
                        <x-ui.heading level="h2" size="lg">Career context</x-ui.heading>
                        <x-ui.badge variant="outline" color="neutral" icon="trophy">{{ number_format($awardHighlights->count()) }} highlights</x-ui.badge>
                    </div>

                    <x-ui.accordion class="rounded-box border border-black/5 dark:border-white/10">
                        <x-ui.accordion.item expanded>
                            <x-ui.accordion.trigger>
                                <span class="inline-flex items-center gap-2">
                                    <x-ui.icon name="trophy" class="size-4" />
                                    Awards
                                </span>
                            </x-ui.accordion.trigger>
                            <x-ui.accordion.content>
                                @if ($awardHighlights->isNotEmpty())
                                    <div class="grid gap-3">
                                        @foreach ($awardHighlights as $awardNomination)
                                            <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                                <div class="flex flex-wrap items-center justify-between gap-3">
                                                    <div>
                                                        <div class="font-medium">
                                                            {{ $awardNomination->awardCategory?->name }}
                                                        </div>
                                                        <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                                            {{ $awardNomination->awardEvent?->award?->name }}
                                                            @if ($awardNomination->awardEvent?->year)
                                                                · {{ $awardNomination->awardEvent->year }}
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="flex flex-wrap gap-2">
                                                        @if ($awardNomination->is_winner)
                                                            <x-ui.badge color="amber" icon="trophy">Winner</x-ui.badge>
                                                        @else
                                                            <x-ui.badge variant="outline" color="neutral" icon="bookmark">Nominee</x-ui.badge>
                                                        @endif

                                                        @if ($awardNomination->title)
                                                            <a href="{{ route('public.titles.show', $awardNomination->title) }}">
                                                                <x-ui.badge variant="outline" color="slate" icon="film">{{ $awardNomination->title->name }}</x-ui.badge>
                                                            </a>
                                                        @elseif ($awardNomination->episode?->title)
                                                            <a href="{{ route('public.titles.show', $awardNomination->episode->title) }}">
                                                                <x-ui.badge variant="outline" color="slate" icon="rectangle-stack">{{ $awardNomination->episode->title->name }}</x-ui.badge>
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                        <x-ui.empty.media>
                                            <x-ui.icon name="trophy" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                        </x-ui.empty.media>
                                        <x-ui.heading level="h3">No awards have been published yet.</x-ui.heading>
                                    </x-ui.empty>
                                @endif
                            </x-ui.accordion.content>
                        </x-ui.accordion.item>
                    </x-ui.accordion>
                </div>
            </x-ui.card>
        </section>

        <livewire:people.filmography-panel :person="$person" :key="'filmography-'.$person->id" />

        <section>
            <x-ui.card class="!max-w-none">
                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-4">
                        <x-ui.heading level="h2" size="lg">Connections</x-ui.heading>
                        <x-ui.badge variant="outline" color="neutral" icon="share">
                            {{ number_format($relatedTitles->count() + $collaborators->count()) }} linked records
                        </x-ui.badge>
                    </div>

                    <x-ui.accordion class="rounded-box border border-black/5 dark:border-white/10">
                        <x-ui.accordion.item expanded>
                            <x-ui.accordion.trigger>
                                <span class="inline-flex items-center gap-2">
                                    <x-ui.icon name="film" class="size-4" />
                                    Related titles
                                </span>
                            </x-ui.accordion.trigger>
                            <x-ui.accordion.content>
                                @if ($relatedTitles->isNotEmpty())
                                    <div class="grid gap-4 md:grid-cols-2">
                                        @foreach ($relatedTitles as $title)
                                            <x-catalog.title-card :title="$title" :showSummary="false" />
                                        @endforeach
                                    </div>
                                @else
                                    <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                        <x-ui.empty.media>
                                            <x-ui.icon name="film" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                        </x-ui.empty.media>
                                        <x-ui.heading level="h3">Additional related titles are still being curated.</x-ui.heading>
                                    </x-ui.empty>
                                @endif
                            </x-ui.accordion.content>
                        </x-ui.accordion.item>

                        <x-ui.accordion.item>
                            <x-ui.accordion.trigger>
                                <span class="inline-flex items-center gap-2">
                                    <x-ui.icon name="users" class="size-4" />
                                    Frequent collaborators
                                </span>
                            </x-ui.accordion.trigger>
                            <x-ui.accordion.content>
                                @if ($collaborators->isNotEmpty())
                                    <div class="grid gap-3">
                                        @foreach ($collaborators as $collaborator)
                                            @php
                                                $collaboratorHeadshot = \App\Models\MediaAsset::preferredFrom(
                                                    $collaborator['person']->mediaAssets,
                                                    \App\Enums\MediaKind::Headshot,
                                                    \App\Enums\MediaKind::Gallery,
                                                    \App\Enums\MediaKind::Still,
                                                );
                                            @endphp

                                            <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                                <div class="flex items-start gap-4">
                                                    <x-ui.avatar
                                                        as="a"
                                                        :href="route('public.people.show', $collaborator['person'])"
                                                        :src="$collaboratorHeadshot?->url"
                                                        :alt="$collaboratorHeadshot?->alt_text ?: $collaborator['person']->name"
                                                        :name="$collaborator['person']->name"
                                                        color="auto"
                                                        class="!size-16 shrink-0 border border-black/5 dark:border-white/10"
                                                    />

                                                    <div class="flex-1 space-y-2">
                                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                                            <div>
                                                                <div class="font-medium">
                                                                    <a href="{{ route('public.people.show', $collaborator['person']) }}" class="hover:opacity-80">
                                                                        {{ $collaborator['person']->name }}
                                                                    </a>
                                                                </div>
                                                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                                                    {{ number_format($collaborator['sharedTitlesCount']) }} shared title{{ $collaborator['sharedTitlesCount'] === 1 ? '' : 's' }}
                                                                </div>
                                                            </div>

                                                            @if ($collaborator['person']->known_for_department)
                                                                <x-ui.badge variant="outline" color="neutral" icon="briefcase">
                                                                    {{ $collaborator['person']->known_for_department }}
                                                                </x-ui.badge>
                                                            @endif
                                                        </div>

                                                        <div class="flex flex-wrap gap-2">
                                                            @foreach ($collaborator['sharedTitles'] as $sharedTitle)
                                                                <a href="{{ route('public.titles.show', $sharedTitle) }}">
                                                                    <x-ui.badge variant="outline" color="slate" icon="film">{{ $sharedTitle->name }}</x-ui.badge>
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                        <x-ui.empty.media>
                                            <x-ui.icon name="users" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                        </x-ui.empty.media>
                                        <x-ui.heading level="h3">No collaborator graph has been published yet.</x-ui.heading>
                                    </x-ui.empty>
                                @endif
                            </x-ui.accordion.content>
                        </x-ui.accordion.item>
                    </x-ui.accordion>
                </div>
            </x-ui.card>
        </section>
    </section>
@endsection
