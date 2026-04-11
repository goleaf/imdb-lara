@extends('layouts.public')

@section('title', $person->meta_title ?: $person->name)
@section('meta_description', $person->meta_description ?: ($biographyIntro ?: 'Browse biography and credits for '.$person->name.'.'))

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.people.index')">People</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>{{ $person->name }}</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-ui.card data-slot="people-detail-hero" class="sb-detail-hero sb-person-detail-hero !max-w-none overflow-hidden p-0">
            <div class="grid gap-6 p-6 xl:grid-cols-[17rem_minmax(0,1fr)] xl:p-7">
                <div class="flex justify-center xl:justify-start">
                    <div class="w-full max-w-[17rem] overflow-hidden rounded-[1.6rem] border border-white/10 bg-black/20 p-2">
                        <x-ui.avatar
                            :src="$headshot?->url"
                            :alt="$headshot?->alt_text ?: $person->name"
                            :name="$person->name"
                            color="auto"
                            class="!h-[26rem] !w-full rounded-[1.2rem] border border-black/10 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800"
                        />
                    </div>
                </div>

                <div class="space-y-6 p-5 sm:p-6 xl:p-7">
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="sb-detail-overline">Catalog profile</span>
                            @foreach ($professionLabels as $professionLabel)
                                <x-ui.badge variant="outline" color="slate" icon="briefcase">{{ $professionLabel }}</x-ui.badge>
                            @endforeach
                            @if ($person->nationality)
                                <x-ui.badge variant="outline" color="neutral" icon="globe-alt">{{ $person->nationality }}</x-ui.badge>
                            @endif
                        </div>

                        <div class="space-y-3">
                            <x-ui.heading level="h1" size="xl" class="sb-detail-title">{{ $person->name }}</x-ui.heading>

                            @if ($alternateNames->isNotEmpty())
                                <x-ui.text class="text-sm text-[#a99f92] dark:text-[#a99f92]">
                                    Alternate names: {{ $alternateNames->implode(' · ') }}
                                </x-ui.text>
                            @endif

                            @if ($biographyIntro)
                                <x-ui.text class="sb-detail-copy max-w-4xl text-base">
                                    {{ $biographyIntro }}
                                </x-ui.text>
                            @endif
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="sb-person-hero-stat">
                            <div class="sb-person-hero-stat-label">Credits</div>
                            <div class="sb-person-hero-stat-value">{{ number_format($publishedCreditCount) }}</div>
                            <div class="sb-person-hero-stat-copy">Imported titles</div>
                        </div>
                        <div class="sb-person-hero-stat">
                            <div class="sb-person-hero-stat-label">Wins</div>
                            <div class="sb-person-hero-stat-value">{{ number_format($awardWins) }}</div>
                            <div class="sb-person-hero-stat-copy">Award wins</div>
                        </div>
                        <div class="sb-person-hero-stat">
                            <div class="sb-person-hero-stat-label">Nominations</div>
                            <div class="sb-person-hero-stat-value">{{ number_format($awardNominationsCount) }}</div>
                            <div class="sb-person-hero-stat-copy">Award mentions</div>
                        </div>
                        <div class="sb-person-hero-stat">
                            <div class="sb-person-hero-stat-label">People meter</div>
                            <div class="sb-person-hero-stat-value">{{ $person->popularity_rank ? '#'.number_format($person->popularity_rank) : '—' }}</div>
                            <div class="sb-person-hero-stat-copy">Catalog rank</div>
                        </div>
                    </div>

                    @if ($heroProfileItems->isNotEmpty())
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($heroProfileItems as $item)
                                <div class="rounded-[1.1rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                                    <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">{{ $item['label'] }}</div>
                                    <div class="mt-2 text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $item['value'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </x-ui.card>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.12fr)_minmax(0,0.88fr)]">
            <div class="space-y-6">
                @if ($biographyParagraphs->isNotEmpty())
                    <x-ui.card class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Biography</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Editorial profile copy imported from the remote catalog where available.
                                </x-ui.text>
                            </div>

                            <div class="space-y-4">
                                @foreach ($biographyParagraphs as $paragraph)
                                    <x-ui.text class="text-sm leading-7 text-neutral-700 dark:text-neutral-200">
                                        {{ $paragraph }}
                                    </x-ui.text>
                                @endforeach
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                @if ($careerProfileItems->isNotEmpty())
                    <x-ui.card data-slot="people-detail-career-profile" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Career profile</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    A compact read-only summary built from imported credits, title formats, and release years in the MySQL catalog.
                                </x-ui.text>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-3">
                                @foreach ($careerProfileItems as $careerProfileItem)
                                    <div class="rounded-[1.1rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                                        <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">{{ $careerProfileItem['label'] }}</div>
                                        <div class="mt-2 text-sm font-medium text-neutral-900 dark:text-neutral-100">{{ $careerProfileItem['value'] }}</div>
                                        <div class="mt-2 text-xs leading-5 text-neutral-500 dark:text-neutral-400">
                                            {{ $careerProfileItem['copy'] }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if ($creditDepartmentHighlights->isNotEmpty() || $titleFormatHighlights->isNotEmpty())
                                <div class="grid gap-3 sm:grid-cols-2">
                                    @if ($creditDepartmentHighlights->isNotEmpty())
                                        <div class="rounded-[1.1rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                                            <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">Department mix</div>
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                @foreach ($creditDepartmentHighlights as $departmentHighlight)
                                                    <x-ui.badge variant="outline" color="neutral" icon="briefcase">
                                                        {{ $departmentHighlight['label'] }} · {{ number_format($departmentHighlight['count']) }}
                                                    </x-ui.badge>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if ($titleFormatHighlights->isNotEmpty())
                                        <div class="rounded-[1.1rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                                            <div class="text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">Format mix</div>
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                @foreach ($titleFormatHighlights as $titleFormatHighlight)
                                                    <x-ui.badge variant="outline" color="slate" icon="film">
                                                        {{ $titleFormatHighlight['label'] }} · {{ number_format($titleFormatHighlight['count']) }}
                                                    </x-ui.badge>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </x-ui.card>
                @endif

                @if ($alternativeNameRows->isNotEmpty())
                    <x-ui.card data-slot="people-detail-alternative-names" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Alternative names</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Raw rows imported from the <code>name_basic_alternative_names</code> catalog table for this person.
                                </x-ui.text>
                            </div>

                            <div class="overflow-hidden rounded-[1.1rem] border border-black/5 dark:border-white/10">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                                        <thead class="bg-black/[0.03] text-left text-xs uppercase tracking-[0.18em] text-neutral-500 dark:bg-white/[0.03] dark:text-neutral-400">
                                            <tr>
                                                <th scope="col" class="px-4 py-3 font-medium">name_basic_id</th>
                                                <th scope="col" class="px-4 py-3 font-medium">alternative_name</th>
                                                <th scope="col" class="px-4 py-3 font-medium">position</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-black/5 bg-white/70 dark:divide-white/10 dark:bg-white/[0.02]">
                                            @foreach ($alternativeNameRows as $alternativeNameRow)
                                                <tr>
                                                    <td class="px-4 py-3 align-top font-medium text-neutral-900 dark:text-neutral-100">
                                                        {{ $alternativeNameRow->name_basic_id }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-700 dark:text-neutral-200">
                                                        {{ $alternativeNameRow->alternative_name }}
                                                    </td>
                                                    <td class="px-4 py-3 align-top text-neutral-500 dark:text-neutral-300">
                                                        {{ $alternativeNameRow->position ?? '—' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                @if ($knownForTitles->isNotEmpty())
                    <section class="space-y-4">
                        <div>
                            <div class="sb-page-kicker">Known for</div>
                            <x-ui.heading level="h2" size="lg">Featured titles</x-ui.heading>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            @foreach ($knownForTitles as $knownForTitle)
                                <x-catalog.title-card :title="$knownForTitle" />
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($publishedCreditCount > 0)
                    <x-ui.card data-slot="people-detail-collaborators" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Frequent collaborators</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    People who recur across the most visible imported titles on this profile.
                                </x-ui.text>
                            </div>

                            @if ($frequentCollaborators->isNotEmpty())
                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach ($frequentCollaborators as $collaborator)
                                        <div class="rounded-[1.15rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                                            <div class="flex items-center gap-3">
                                                <a href="{{ route('public.people.show', $collaborator['person']) }}">
                                                    <x-ui.avatar
                                                        :src="$collaborator['person']->preferredHeadshot()?->url"
                                                        :alt="$collaborator['person']->preferredHeadshot()?->alt_text ?: $collaborator['person']->name"
                                                        :name="$collaborator['person']->name"
                                                        color="auto"
                                                        class="!h-14 !w-14 shrink-0 border border-black/5 dark:border-white/10"
                                                    />
                                                </a>

                                                <div class="min-w-0">
                                                    <a href="{{ route('public.people.show', $collaborator['person']) }}" class="truncate font-medium text-neutral-900 transition hover:opacity-80 dark:text-neutral-100">
                                                        {{ $collaborator['person']->name }}
                                                    </a>
                                                    <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                                        {{ number_format($collaborator['sharedCount']) }} shared titles
                                                    </div>
                                                </div>
                                            </div>

                                            @if ($collaborator['sharedTitles']->isNotEmpty())
                                                <div class="mt-4 flex flex-wrap gap-2">
                                                    @foreach ($collaborator['sharedTitles'] as $sharedTitle)
                                                        <a href="{{ route('public.titles.show', $sharedTitle) }}">
                                                            <x-ui.badge variant="outline" color="neutral" icon="film">{{ $sharedTitle->name }}</x-ui.badge>
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                    <x-ui.empty.media>
                                        <x-ui.icon name="users" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                    </x-ui.empty.media>
                                    <x-ui.heading level="h3">No recurring collaborators are visible in the imported title sample yet.</x-ui.heading>
                                </x-ui.empty>
                            @endif
                        </div>
                    </x-ui.card>
                @endif

                <livewire:people.filmography-panel :person="$person" :wire:key="'person-filmography-'.$person->id" />
            </div>

            <div class="space-y-6">
                <x-ui.card data-slot="people-detail-awards" class="sb-detail-section !max-w-none">
                    <div class="space-y-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Awards summary</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Highlighted nominations and wins connected to this person.
                            </x-ui.text>
                        </div>

                        @if ($awardHighlights->isNotEmpty())
                            <div class="space-y-3">
                                @foreach ($awardHighlights as $awardNomination)
                                    <div class="rounded-[1.1rem] border border-black/5 bg-white/70 p-4 dark:border-white/10 dark:bg-white/[0.02]">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <div class="font-medium text-neutral-900 dark:text-neutral-100">{{ $awardNomination->awardCategory?->name ?: 'Award nomination' }}</div>
                                                <div class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">
                                                    {{ $awardNomination->awardEvent?->name ?: 'Event' }}
                                                    @if ($awardNomination->award_year)
                                                        · {{ $awardNomination->award_year }}
                                                    @endif
                                                    @if ($awardNomination->title)
                                                        · {{ $awardNomination->title->name }}
                                                    @endif
                                                </div>
                                            </div>

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
                                <x-ui.heading level="h3">No award nominations are linked to this profile yet.</x-ui.heading>
                            </x-ui.empty>
                        @endif
                    </div>
                </x-ui.card>

                @if ($photoGallery->isNotEmpty())
                    <x-ui.card data-slot="people-detail-gallery" class="sb-detail-section !max-w-none">
                        <div class="space-y-4">
                            <div>
                                <x-ui.heading level="h2" size="lg">Portrait gallery</x-ui.heading>
                                <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                    Portraits, publicity stills, and linked images from the imported record.
                                </x-ui.text>
                            </div>

                            <div class="grid gap-3 grid-cols-2">
                                @foreach ($photoGallery as $asset)
                                    <a href="{{ $asset->url }}" class="overflow-hidden rounded-[1.1rem] border border-black/5 bg-neutral-100 dark:border-white/10 dark:bg-neutral-800">
                                        <img src="{{ $asset->url }}" alt="{{ $asset->alt_text ?: $person->name }}" class="aspect-[4/5] w-full object-cover">
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </x-ui.card>
                @endif
            </div>
        </div>

        @if ($publishedCreditCount > 0)
            <section data-slot="people-detail-related-titles" class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="sb-page-kicker">Related titles</div>
                        <x-ui.heading level="h2" size="lg">More catalog titles from this profile</x-ui.heading>
                    </div>
                </div>

                @if ($relatedTitles->isNotEmpty())
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($relatedTitles as $relatedTitle)
                            <x-catalog.title-card :title="$relatedTitle" />
                        @endforeach
                    </div>
                @else
                    <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                        <x-ui.empty.media>
                            <x-ui.icon name="film" class="size-8 text-neutral-400 dark:text-neutral-500" />
                        </x-ui.empty.media>
                        <x-ui.heading level="h3">Additional related titles have not been surfaced for this profile yet.</x-ui.heading>
                    </x-ui.empty>
                @endif
            </section>
        @endif
    </section>
@endsection
