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
        <x-ui.card class="sb-detail-hero sb-person-detail-hero !max-w-none overflow-hidden p-0" data-slot="people-detail-hero">
            <div class="grid gap-6 p-6 xl:grid-cols-[17rem_minmax(0,1fr)] xl:p-7">
                <div class="flex justify-center xl:justify-start">
                    <div class="sb-person-portrait-shell w-full max-w-[17rem] overflow-hidden rounded-[1.6rem] border border-white/10 bg-black/20 p-2">
                        <x-ui.avatar
                            :src="$headshot?->url"
                            :alt="$headshot?->alt_text ?: $person->name"
                            :name="$person->name"
                            color="auto"
                            class="!h-[26rem] !w-full rounded-[1.2rem] border border-black/10 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800"
                        />
                    </div>
                </div>

                <div class="sb-detail-panel sb-person-hero-panel space-y-6 p-5 sm:p-6 xl:p-7">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="sb-detail-overline">Screenbase profile</span>

                            @if ($person->known_for_department)
                                <x-ui.badge variant="outline" color="amber" icon="briefcase">{{ $person->known_for_department }}</x-ui.badge>
                            @endif

                            @if ($person->nationality)
                                <x-ui.badge variant="outline" color="slate" icon="globe-alt">{{ $person->nationality }}</x-ui.badge>
                            @endif
                        </div>

                        <div class="space-y-3">
                            <x-ui.heading level="h1" size="xl" class="sb-detail-title">{{ $person->name }}</x-ui.heading>

                            @if ($professionLabels->isNotEmpty())
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($professionLabels as $professionLabel)
                                        <x-ui.badge variant="outline" color="slate" icon="sparkles">{{ $professionLabel }}</x-ui.badge>
                                    @endforeach
                                </div>
                            @endif

                            @if ($alternateNames->isNotEmpty())
                                <div class="sb-person-alt-names">
                                    <span class="sb-person-alt-label">Alternate names</span>
                                    <span>{{ $alternateNames->implode(' · ') }}</span>
                                </div>
                            @endif

                            @if ($biographyIntro)
                                <x-ui.text class="sb-detail-copy max-w-4xl text-base">
                                    {{ $biographyIntro }}
                                </x-ui.text>
                            @endif
                        </div>
                    </div>

                    <div class="sb-person-hero-stats">
                        <div class="sb-person-hero-stat">
                            <div class="sb-person-hero-stat-label">Credits</div>
                            <div class="sb-person-hero-stat-value">{{ number_format($publishedCreditCount) }}</div>
                            <div class="sb-person-hero-stat-copy">Published titles</div>
                        </div>
                        <div class="sb-person-hero-stat">
                            <div class="sb-person-hero-stat-label">Awards won</div>
                            <div class="sb-person-hero-stat-value">{{ number_format($awardWins) }}</div>
                            <div class="sb-person-hero-stat-copy">Career wins</div>
                        </div>
                        <div class="sb-person-hero-stat">
                            <div class="sb-person-hero-stat-label">Nominations</div>
                            <div class="sb-person-hero-stat-value">{{ number_format($awardNominationsCount) }}</div>
                            <div class="sb-person-hero-stat-copy">Award mentions</div>
                        </div>
                        <div class="sb-person-hero-stat">
                            <div class="sb-person-hero-stat-label">People meter</div>
                            <div class="sb-person-hero-stat-value">{{ $person->popularity_rank ? '#'.number_format($person->popularity_rank) : '—' }}</div>
                            <div class="sb-person-hero-stat-copy">Public rank</div>
                        </div>
                    </div>

                    @if ($heroProfileItems->isNotEmpty())
                        <div class="sb-person-fact-list">
                            @foreach ($heroProfileItems as $item)
                                <div class="sb-person-fact-card sb-person-fact-card--quiet">
                                    <div class="sb-person-fact-label">{{ $item['label'] }}</div>
                                    <div class="sb-person-fact-value">{{ $item['value'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex flex-col gap-3">
                        <div class="flex flex-wrap gap-3">
                            <x-ui.button as="a" href="#person-filmography" icon="film" color="amber" class="sb-detail-primary-action">
                                Explore filmography
                            </x-ui.button>
                            <x-ui.button as="a" href="#person-awards" variant="outline" color="amber" icon="trophy" class="sb-detail-secondary-action">
                                View awards
                            </x-ui.button>
                        </div>

                        <div class="flex flex-wrap gap-x-4 gap-y-2">
                            <a href="#person-biography" class="sb-detail-utility-link">Biography</a>
                            <a href="#person-known-for" class="sb-detail-utility-link">Known for</a>
                            <a href="#person-trademarks" class="sb-detail-utility-link">Trademarks</a>
                            <a href="#person-collaborators" class="sb-detail-utility-link">Collaborators</a>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card class="sb-detail-section sb-title-directory-shell sb-person-directory-shell !max-w-none">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="space-y-2">
                    <div class="sb-title-directory-kicker">Person dossier</div>
                    <x-ui.heading level="h2" size="lg" class="sb-title-directory-title">Prestige profile map</x-ui.heading>
                    <x-ui.text class="max-w-3xl text-sm text-[#b8ad9d] dark:text-[#b8ad9d]">
                        Biography, known-for work, awards summary, trademarks, filmography, gallery, collaborators, and related titles in one structured record.
                    </x-ui.text>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach ($personDirectory as $directoryItem)
                        <a href="{{ $directoryItem['href'] }}" class="sb-title-directory-link">
                            {{ $directoryItem['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>
        </x-ui.card>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.12fr)_minmax(0,0.88fr)]">
            <div class="space-y-6">
                <x-ui.card id="person-biography" class="sb-detail-section sb-person-biography-shell !max-w-none">
                    <div class="space-y-5">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Biography</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Editorial profile copy with personal context, professional arc, and the public record that frames the work.
                                    @if (blank($person->biography) && filled($person->short_biography))
                                        This profile currently includes the short public biography.
                                    @endif
                                </x-ui.text>
                            </div>

                            <x-ui.badge variant="outline" color="neutral" icon="document-text">
                                {{ $biographyParagraphs->isNotEmpty() ? number_format($biographyParagraphs->count()) : 0 }} passages
                            </x-ui.badge>
                        </div>

                        @if ($biographyParagraphs->isNotEmpty())
                            <div class="sb-person-biography-copy">
                                @foreach ($biographyParagraphs as $paragraphIndex => $paragraph)
                                    <x-ui.text class="sb-person-biography-paragraph{{ $paragraphIndex === 0 ? ' sb-person-biography-paragraph--lead' : '' }}">
                                        {{ $paragraph }}
                                    </x-ui.text>
                                @endforeach
                            </div>
                        @else
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="document-text" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">No biography has been published yet.</x-ui.heading>
                            </x-ui.empty>
                        @endif
                    </div>
                </x-ui.card>

                <x-ui.card id="person-awards" class="sb-detail-section sb-person-awards-shell !max-w-none">
                    <div class="space-y-5">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Awards summary</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Career wins, nominations, and the most relevant public award citations attached to this person record.
                                </x-ui.text>
                            </div>

                            <x-ui.badge variant="outline" color="amber" icon="trophy">{{ number_format($awardNominationsCount) }} entries</x-ui.badge>
                        </div>

                        <div class="sb-person-award-summary-grid">
                            <div class="sb-person-award-summary-card">
                                <div class="sb-person-award-summary-label">Wins</div>
                                <div class="sb-person-award-summary-value">{{ number_format($awardWins) }}</div>
                                <div class="sb-person-award-summary-copy">Confirmed victories</div>
                            </div>
                            <div class="sb-person-award-summary-card">
                                <div class="sb-person-award-summary-label">Nominations</div>
                                <div class="sb-person-award-summary-value">{{ number_format($awardNominationsCount) }}</div>
                                <div class="sb-person-award-summary-copy">Career mentions</div>
                            </div>
                            <div class="sb-person-award-summary-card">
                                <div class="sb-person-award-summary-label">Award bodies</div>
                                <div class="sb-person-award-summary-value">{{ number_format($awardBodiesCount) }}</div>
                                <div class="sb-person-award-summary-copy">Highlighted ceremonies</div>
                            </div>
                        </div>

                        @if ($awardHighlights->isNotEmpty())
                            <div class="grid gap-3">
                                @foreach ($awardHighlights as $awardNomination)
                                    <div class="sb-person-award-row">
                                        <div>
                                            <div class="sb-person-award-row-title">
                                                {{ $awardNomination->awardCategory?->name }}
                                            </div>
                                            <div class="sb-person-award-row-meta">
                                                {{ $awardNomination->awardEvent?->award?->name }}
                                                @if ($awardNomination->awardEvent?->year)
                                                    · {{ $awardNomination->awardEvent->year }}
                                                @endif
                                                @if ($awardNomination->title)
                                                    · {{ $awardNomination->title->name }}
                                                @elseif ($awardNomination->episode?->title)
                                                    · {{ $awardNomination->episode->title->name }}
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                            @if ($awardNomination->is_winner)
                                                <x-ui.badge color="amber" icon="trophy">Winner</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="outline" color="neutral" icon="bookmark">Nominee</x-ui.badge>
                                            @endif
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
                    </div>
                </x-ui.card>

                <x-ui.card id="person-trademarks" class="sb-detail-section !max-w-none">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Trademarks</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Signature traits, recurring screen presence, and other recognizable notes attached to the imported profile payload.
                                </x-ui.text>
                            </div>

                            <x-ui.badge variant="outline" color="neutral" icon="sparkles">{{ number_format($trademarkItems->count()) }} notes</x-ui.badge>
                        </div>

                        @if ($trademarkItems->isNotEmpty())
                            <div class="grid gap-3">
                                @foreach ($trademarkItems as $trademarkItem)
                                    <div class="sb-person-trademark-item">
                                        <x-ui.icon name="sparkles" class="size-4 text-[#d6b574]" />
                                        <span>{{ $trademarkItem }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="sparkles" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">Signature trademarks have not been published yet.</x-ui.heading>
                            </x-ui.empty>
                        @endif
                    </div>
                </x-ui.card>
            </div>

            <div class="space-y-6">
                <x-ui.card id="person-known-for" class="sb-detail-section !max-w-none">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Known for</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    The titles that define the public profile and anchor this person’s page.
                                </x-ui.text>
                            </div>
                            <x-ui.badge variant="outline" color="neutral" icon="film">{{ number_format($knownForTitles->count()) }} titles</x-ui.badge>
                        </div>

                        @if ($knownForTitles->isNotEmpty())
                            <div class="space-y-4">
                                @if ($featuredKnownFor)
                                    <div class="sb-person-known-for-lead">
                                        <a href="{{ route('public.titles.show', $featuredKnownFor) }}" class="sb-person-known-for-lead-media">
                                            @if ($featuredKnownFor->preferredPoster())
                                                <img
                                                    src="{{ $featuredKnownFor->preferredPoster()->url }}"
                                                    alt="{{ $featuredKnownFor->preferredPoster()->alt_text ?: $featuredKnownFor->name }}"
                                                    class="aspect-[2/3] w-full object-cover"
                                                    loading="lazy"
                                                >
                                            @else
                                                <div class="flex aspect-[2/3] items-center justify-center bg-neutral-200 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">
                                                    <x-ui.icon name="film" class="size-10" />
                                                </div>
                                            @endif
                                        </a>

                                        <div class="min-w-0 space-y-4">
                                            <div class="space-y-3">
                                                <div class="sb-cast-section-label">Signature title</div>
                                                <x-ui.heading level="h3" size="lg" class="sb-title-directory-title">
                                                    <a href="{{ route('public.titles.show', $featuredKnownFor) }}" class="hover:opacity-80">
                                                        {{ $featuredKnownFor->name }}
                                                    </a>
                                                </x-ui.heading>

                                                <div class="flex flex-wrap gap-2">
                                                    <x-ui.badge variant="outline" :icon="$featuredKnownFor->typeIcon()">{{ $featuredKnownFor->typeLabel() }}</x-ui.badge>
                                                    @if ($featuredKnownFor->release_year)
                                                        <x-ui.badge variant="outline" color="slate" icon="calendar-days">{{ $featuredKnownFor->release_year }}</x-ui.badge>
                                                    @endif
                                                    @if ($featuredKnownFor->statistic?->average_rating)
                                                        <x-ui.badge icon="star" color="amber">{{ number_format((float) $featuredKnownFor->statistic->average_rating, 1) }}</x-ui.badge>
                                                    @endif
                                                </div>

                                                @if (filled($featuredKnownFor->plot_outline))
                                                    <x-ui.text class="sb-person-known-for-copy">
                                                        {{ str($featuredKnownFor->plot_outline)->limit(220) }}
                                                    </x-ui.text>
                                                @endif
                                            </div>

                                            <div class="flex flex-wrap gap-3 text-sm text-[#aa9f90] dark:text-[#aa9f90]">
                                                <span>{{ number_format((int) ($featuredKnownFor->statistic?->review_count ?? 0)) }} reviews</span>
                                                @if ($featuredKnownFor->statistic?->rating_count)
                                                    <span>{{ number_format((int) $featuredKnownFor->statistic->rating_count) }} ratings</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if ($secondaryKnownFor->isNotEmpty())
                                    <div class="sb-person-known-for-grid">
                                        @foreach ($secondaryKnownFor as $title)
                                            <a href="{{ route('public.titles.show', $title) }}" class="sb-person-known-for-card">
                                                <div class="sb-person-known-for-card-media">
                                                    @if ($title->preferredPoster())
                                                        <img
                                                            src="{{ $title->preferredPoster()->url }}"
                                                            alt="{{ $title->preferredPoster()->alt_text ?: $title->name }}"
                                                            class="aspect-[2/3] w-full object-cover"
                                                            loading="lazy"
                                                        >
                                                    @else
                                                        <div class="flex aspect-[2/3] items-center justify-center bg-neutral-200 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">
                                                            <x-ui.icon name="film" class="size-8" />
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="space-y-2">
                                                    <div class="sb-person-known-for-card-title">{{ $title->name }}</div>
                                                    <div class="flex flex-wrap gap-2">
                                                        @if ($title->release_year)
                                                            <x-ui.badge variant="outline" color="slate" icon="calendar-days">{{ $title->release_year }}</x-ui.badge>
                                                        @endif
                                                        @if ($title->statistic?->average_rating)
                                                            <x-ui.badge icon="star" color="amber">{{ number_format((float) $title->statistic->average_rating, 1) }}</x-ui.badge>
                                                        @endif
                                                    </div>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
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

                <x-ui.card class="sb-detail-section !max-w-none">
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-4">
                            <x-ui.heading level="h2" size="lg">Profile details</x-ui.heading>
                            <x-ui.badge variant="outline" color="neutral" icon="identification">{{ number_format($profileItems->count()) }} facts</x-ui.badge>
                        </div>

                        @if ($profileItems->isNotEmpty())
                            <div class="space-y-2">
                                @foreach ($profileItems as $item)
                                    <div class="sb-person-profile-row">
                                        <div class="sb-person-profile-row-label">{{ $item['label'] }}</div>
                                        <div class="sb-person-profile-row-value">{{ $item['value'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="identification" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">Public profile details are still being curated.</x-ui.heading>
                            </x-ui.empty>
                        @endif
                    </div>
                </x-ui.card>
            </div>
        </section>

        <livewire:people.filmography-panel :person="$person" :key="'filmography-'.$person->id" />

        <livewire:contributions.suggestion-form
            contributableType="person"
            :contributableId="$person->id"
            :contributableLabel="$person->name"
            :key="'person-contribution-'.$person->id"
        />

        <section class="grid gap-6 xl:grid-cols-[minmax(0,0.92fr)_minmax(0,1.08fr)]">
            <x-ui.card id="person-gallery" class="sb-detail-section !max-w-none">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Portrait gallery</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                A compact gallery of stills and publicity images attached to the public record.
                            </x-ui.text>
                        </div>
                        <x-ui.badge variant="outline" color="neutral" icon="photo">{{ number_format($photoGallery->count()) }} assets</x-ui.badge>
                    </div>

                    @if ($photoGallery->isNotEmpty())
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach ($photoGallery as $galleryAsset)
                                <div class="sb-person-gallery-card overflow-hidden rounded-[1.2rem]">
                                    <img
                                        src="{{ $galleryAsset->url }}"
                                        alt="{{ $galleryAsset->alt_text ?: $person->name }}"
                                        class="aspect-[4/5] w-full object-cover"
                                        loading="lazy"
                                    >
                                    @if (filled($galleryAsset->caption))
                                        <div class="sb-person-gallery-caption">{{ $galleryAsset->caption }}</div>
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

            <x-ui.card id="person-collaborators" class="sb-detail-section !max-w-none">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Frequent collaborators</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Repeat creative partnerships surfaced from shared title credits across the published catalog.
                            </x-ui.text>
                        </div>
                        <x-ui.badge variant="outline" color="neutral" icon="users">{{ number_format($collaborators->count()) }} people</x-ui.badge>
                    </div>

                    @if ($collaborators->isNotEmpty())
                        <div class="grid gap-3">
                            @foreach ($collaborators as $collaborator)
                                <div class="sb-person-collaborator-row">
                                    <x-ui.avatar
                                        as="a"
                                        :href="route('public.people.show', $collaborator['person'])"
                                        :src="$collaborator['person']->preferredHeadshot()?->url"
                                        :alt="$collaborator['person']->preferredHeadshot()?->alt_text ?: $collaborator['person']->name"
                                        :name="$collaborator['person']->name"
                                        color="auto"
                                        class="!size-16 shrink-0 border border-black/5 dark:border-white/10"
                                    />

                                    <div class="min-w-0 flex-1 space-y-2">
                                        <div class="flex flex-wrap items-start justify-between gap-3">
                                            <div>
                                                <div class="font-medium text-[#f4eee5]">
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
                </div>
            </x-ui.card>
        </section>

        <x-ui.card id="person-related-titles" class="sb-detail-section !max-w-none">
            <div class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <x-ui.heading level="h2" size="lg">Related titles</x-ui.heading>
                        <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                            Additional published titles connected to this person beyond the primary known-for surface.
                        </x-ui.text>
                    </div>
                    <x-ui.badge variant="outline" color="neutral" icon="share">{{ number_format($relatedTitles->count()) }} titles</x-ui.badge>
                </div>

                @if ($relatedTitles->isNotEmpty())
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
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
            </div>
        </x-ui.card>
    </section>
@endsection
