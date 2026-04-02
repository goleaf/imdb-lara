@extends('layouts.public')

@section('title', $title->name.' Full Cast')
@section('meta_description', 'Browse the full cast and crew list for '.$title->name.'.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.index')">Titles</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('public.titles.show', $title)">{{ $title->name }}</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Full Cast</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <x-ui.card class="sb-detail-hero sb-cast-hero !max-w-none overflow-hidden p-0" data-slot="title-cast-hero">
            <div class="relative">
                @if ($backdrop)
                    <img
                        src="{{ $backdrop->url }}"
                        alt="{{ $backdrop->alt_text ?: $title->name }}"
                        class="absolute inset-0 h-full w-full object-cover opacity-24"
                    >
                    <div class="absolute inset-0 bg-[linear-gradient(112deg,rgba(10,10,9,0.95),rgba(10,10,9,0.84),rgba(10,10,9,0.46))]"></div>
                @else
                    <div class="absolute inset-0 bg-[linear-gradient(135deg,rgba(12,11,10,0.98),rgba(10,10,9,0.96))]"></div>
                @endif

                <div class="relative grid gap-6 p-6 xl:grid-cols-[13rem_minmax(0,1fr)]">
                    <div class="sb-cast-poster-shell overflow-hidden rounded-[1.3rem] border border-black/5 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800">
                        @if ($poster)
                            <img
                                src="{{ $poster->url }}"
                                alt="{{ $poster->alt_text ?: $title->name }}"
                                class="aspect-[2/3] w-full object-cover"
                            >
                        @else
                            <div class="flex aspect-[2/3] items-center justify-center text-neutral-500 dark:text-neutral-400">
                                <x-ui.icon name="film" class="size-12" />
                            </div>
                        @endif
                    </div>

                    <div class="sb-cast-hero-panel space-y-5 p-5 sm:p-6">
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                                <span class="sb-cast-kicker">Archive record</span>
                                <span class="sb-cast-meta-item">{{ str($title->title_type->value)->headline() }}</span>
                                @if ($title->release_year)
                                    <a href="{{ route('public.years.show', ['year' => $title->release_year]) }}" class="sb-cast-meta-item">
                                        {{ $title->release_year }}
                                    </a>
                                @endif
                                @if ($title->runtime_minutes)
                                    <span class="sb-cast-meta-item">{{ $title->runtime_minutes }} min</span>
                                @endif
                                @if ($title->statistic?->average_rating)
                                    <span class="sb-cast-meta-item sb-cast-meta-item--rating">
                                        <x-ui.icon name="star" class="size-4" />
                                        {{ number_format((float) $title->statistic->average_rating, 1) }}
                                    </span>
                                @endif
                            </div>

                            <div class="space-y-2">
                                <x-ui.heading level="h1" size="xl" class="sb-detail-title">{{ $title->name }} Full Cast & Crew</x-ui.heading>
                                <x-ui.text class="sb-detail-copy max-w-4xl text-base">
                                    A structured public archive of cast billing, crew departments, role names, job credits, and credited-as details for this title.
                                </x-ui.text>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-3">
                                <div class="sb-cast-summary-card">
                                    <div class="sb-cast-summary-label">Cast credits</div>
                                    <div class="sb-cast-summary-value">{{ number_format($castCount) }}</div>
                                    <div class="sb-cast-summary-copy">{{ number_format($castPageCredits->count()) }} on this page</div>
                                </div>
                                <div class="sb-cast-summary-card">
                                    <div class="sb-cast-summary-label">Crew credits</div>
                                    <div class="sb-cast-summary-value">{{ number_format($crewCount) }}</div>
                                    <div class="sb-cast-summary-copy">{{ number_format($crewPageCredits->count()) }} on this page</div>
                                </div>
                                <div class="sb-cast-summary-card">
                                    <div class="sb-cast-summary-label">Crew groups</div>
                                    <div class="sb-cast-summary-value">{{ number_format($crewGroups->count()) }}</div>
                                    <div class="sb-cast-summary-copy">Department buckets</div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <x-ui.button as="a" :href="route('public.titles.show', $title)" variant="outline" color="amber" icon="arrow-left">
                                Back to title
                            </x-ui.button>
                            <x-ui.button as="a" href="#title-cast-section" variant="outline" icon="users">
                                Jump to cast
                            </x-ui.button>
                            <x-ui.button as="a" href="#title-crew-section" variant="ghost" icon="briefcase">
                                Jump to crew
                            </x-ui.button>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card id="title-cast-section" class="sb-detail-section sb-cast-section-shell !max-w-none" data-slot="title-cast-cast-section">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Cast</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Billing order, performer names, role names, and credited-as notes presented as a readable billing board.
                            </x-ui.text>
                        </div>
                        <x-ui.badge variant="outline" color="neutral" icon="users">{{ number_format($castCount) }} total</x-ui.badge>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="sb-cast-overview-card">
                            <div class="sb-cast-summary-label">Principal cast</div>
                            <div class="sb-cast-summary-value">{{ number_format($castPageCredits->where('is_principal', true)->count()) }}</div>
                            <div class="sb-cast-summary-copy">Top-billed performers on this page.</div>
                        </div>
                        <div class="sb-cast-overview-card">
                            <div class="sb-cast-summary-label">Supporting & guest</div>
                            <div class="sb-cast-summary-value">{{ number_format($castPageCredits->where('is_principal', false)->count()) }}</div>
                            <div class="sb-cast-summary-copy">Secondary and guest-billed performers.</div>
                        </div>
                    </div>

                    <div class="sb-cast-column-guide" aria-hidden="true">
                        <span class="sb-cast-column-guide-label">Billing</span>
                        <span class="sb-cast-column-guide-label">Performer & role</span>
                        <span class="sb-cast-column-guide-label text-right">Credit notes</span>
                    </div>

                    <div class="grid gap-5">
                        @forelse ($castBillingGroups as $groupLabel => $groupCredits)
                            <div class="space-y-3">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div class="sb-cast-section-label">{{ $groupLabel }}</div>
                                    <x-ui.badge variant="outline" color="neutral" icon="users">
                                        {{ number_format($groupCredits->count()) }} on this page
                                    </x-ui.badge>
                                </div>

                                <div class="grid gap-3">
                                    @foreach ($groupCredits as $credit)
                                        <div class="sb-cast-credit-row">
                                            <div class="sb-cast-credit-rank">
                                                {{ $credit->billing_order ? '#'.$credit->billing_order : '—' }}
                                            </div>

                                            <div class="min-w-0 space-y-1">
                                                <div class="sb-cast-credit-name">
                                                    <a href="{{ route('public.people.show', $credit->person) }}" class="hover:opacity-80">
                                                        {{ $credit->person->name }}
                                                    </a>
                                                </div>
                                                <div class="sb-cast-credit-role">
                                                    {{ $credit->character_name ?: 'Role not published yet' }}
                                                </div>
                                                @if ($credit->credited_as)
                                                    <div class="sb-cast-credit-note">
                                                        Credited as {{ $credit->credited_as }}
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="space-y-1 text-right">
                                                <div class="sb-cast-credit-note">{{ $credit->job ?: 'Cast credit' }}</div>
                                                @if ($credit->is_principal)
                                                    <div class="sb-cast-credit-note">Principal billing</div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                                <x-ui.empty.media>
                                    <x-ui.icon name="users" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                </x-ui.empty.media>
                                <x-ui.heading level="h3">No cast credits are published yet.</x-ui.heading>
                            </x-ui.empty>
                        @endforelse
                    </div>

                    <div>
                        {{ $castCredits->links() }}
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card id="title-crew-section" class="sb-detail-section sb-crew-section-shell !max-w-none" data-slot="title-cast-crew-section">
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Crew</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Department groupings with job names, billing order, episode-specific links, and credited-as detail where supplied.
                            </x-ui.text>
                        </div>
                        <x-ui.badge variant="outline" color="neutral" icon="briefcase">{{ number_format($crewCount) }} total</x-ui.badge>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="sb-crew-overview-card">
                            <div class="sb-cast-summary-label">Creative leads</div>
                            <div class="sb-cast-summary-value">{{ number_format($leadCrewCount) }}</div>
                            <div class="sb-cast-summary-copy">Director, writer, and producer credits surfaced first.</div>
                        </div>
                        <div class="sb-crew-overview-card">
                            <div class="sb-cast-summary-label">Technical departments</div>
                            <div class="sb-cast-summary-value">{{ number_format($technicalCrewCount) }}</div>
                            <div class="sb-cast-summary-copy">{{ number_format($technicalCrewGroups->count()) }} craft groups on this page.</div>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div class="sb-crew-band">
                            <div class="flex flex-wrap items-end justify-between gap-4">
                                <div class="space-y-2">
                                    <div class="sb-cast-section-label">Creative leads</div>
                                    <x-ui.heading level="h3" size="md" class="sb-crew-band-title">Director, writer, and producer credits</x-ui.heading>
                                    <x-ui.text class="sb-crew-band-copy">
                                        Above-the-line crew is separated from technical craft departments so lead creative credits read first.
                                    </x-ui.text>
                                </div>
                                <x-ui.badge variant="outline" color="amber" icon="sparkles">
                                    {{ number_format($leadCrewCount) }} creative credits
                                </x-ui.badge>
                            </div>

                            @if ($leadCrewGroups->isNotEmpty())
                                <div class="mt-4 grid gap-4 xl:grid-cols-3">
                                    @foreach ($leadCrewGroups as $department => $departmentCredits)
                                        <div class="sb-crew-group sb-crew-group--lead">
                                            <div class="flex flex-wrap items-center justify-between gap-3">
                                                <div class="sb-crew-group-title">{{ $department }}</div>
                                                <x-ui.badge variant="outline" color="amber" icon="briefcase">
                                                    {{ number_format($departmentCredits->count()) }} on this page
                                                </x-ui.badge>
                                            </div>

                                            <div class="mt-4 grid gap-3">
                                                @foreach ($departmentCredits as $credit)
                                                    <div class="sb-crew-credit-row">
                                                        <div class="min-w-0 space-y-1">
                                                            <div class="sb-crew-credit-name">
                                                                <a href="{{ route('public.people.show', $credit->person) }}" class="hover:opacity-80">
                                                                    {{ $credit->person->name }}
                                                                </a>
                                                            </div>
                                                            <div class="sb-crew-credit-job">
                                                                {{ $credit->job ?: 'Crew credit' }}
                                                            </div>

                                                            @if ($credit->credited_as)
                                                                <div class="sb-crew-credit-note">
                                                                    Credited as {{ $credit->credited_as }}
                                                                </div>
                                                            @endif

                                                            @if ($credit->episode?->title && $credit->episode?->series && $credit->episode?->season)
                                                                <div class="sb-crew-credit-note">
                                                                    Episode-specific credit:
                                                                    <a
                                                                        href="{{ route('public.episodes.show', ['series' => $credit->episode->series, 'season' => $credit->episode->season, 'episode' => $credit->episode->title]) }}"
                                                                        class="font-medium hover:opacity-80"
                                                                    >
                                                                        {{ $credit->episode->title->name }}
                                                                    </a>
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <div class="space-y-1 text-right">
                                                            @if ($credit->billing_order)
                                                                <div class="sb-crew-credit-note">#{{ $credit->billing_order }}</div>
                                                            @endif
                                                            @if ($credit->is_principal)
                                                                <div class="sb-crew-credit-note">Principal</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <x-ui.empty class="mt-4 rounded-box border border-dashed border-black/10 dark:border-white/10">
                                    <x-ui.empty.media>
                                        <x-ui.icon name="sparkles" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                    </x-ui.empty.media>
                                    <x-ui.heading level="h3">No director, writer, or producer credits are published yet.</x-ui.heading>
                                </x-ui.empty>
                            @endif
                        </div>

                        <div class="sb-crew-band sb-crew-band--technical">
                            <div class="flex flex-wrap items-end justify-between gap-4">
                                <div class="space-y-2">
                                    <div class="sb-cast-section-label">Technical departments</div>
                                    <x-ui.heading level="h3" size="md" class="sb-crew-band-title">Craft teams and department units</x-ui.heading>
                                    <x-ui.text class="sb-crew-band-copy">
                                        Camera, editorial, sound, design, and other technical departments are grouped separately for faster scanning.
                                    </x-ui.text>
                                </div>
                                <x-ui.badge variant="outline" color="slate" icon="briefcase">
                                    {{ number_format($technicalCrewCount) }} technical credits
                                </x-ui.badge>
                            </div>

                            @if ($technicalCrewGroups->isNotEmpty())
                                <div class="mt-4 grid gap-4 xl:grid-cols-2">
                                    @foreach ($technicalCrewGroups as $department => $departmentCredits)
                                        <div class="sb-crew-group sb-crew-group--technical">
                                            <div class="flex flex-wrap items-center justify-between gap-3">
                                                <div class="sb-crew-group-title">{{ $department }}</div>
                                                <x-ui.badge variant="outline" color="slate" icon="briefcase">
                                                    {{ number_format($departmentCredits->count()) }} on this page
                                                </x-ui.badge>
                                            </div>

                                            <div class="mt-4 grid gap-3">
                                                @foreach ($departmentCredits as $credit)
                                                    <div class="sb-crew-credit-row">
                                                        <div class="min-w-0 space-y-1">
                                                            <div class="sb-crew-credit-name">
                                                                <a href="{{ route('public.people.show', $credit->person) }}" class="hover:opacity-80">
                                                                    {{ $credit->person->name }}
                                                                </a>
                                                            </div>
                                                            <div class="sb-crew-credit-job">
                                                                {{ $credit->job ?: 'Crew credit' }}
                                                            </div>

                                                            @if ($credit->credited_as)
                                                                <div class="sb-crew-credit-note">
                                                                    Credited as {{ $credit->credited_as }}
                                                                </div>
                                                            @endif

                                                            @if ($credit->episode?->title && $credit->episode?->series && $credit->episode?->season)
                                                                <div class="sb-crew-credit-note">
                                                                    Episode-specific credit:
                                                                    <a
                                                                        href="{{ route('public.episodes.show', ['series' => $credit->episode->series, 'season' => $credit->episode->season, 'episode' => $credit->episode->title]) }}"
                                                                        class="font-medium hover:opacity-80"
                                                                    >
                                                                        {{ $credit->episode->title->name }}
                                                                    </a>
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <div class="space-y-1 text-right">
                                                            @if ($credit->billing_order)
                                                                <div class="sb-crew-credit-note">#{{ $credit->billing_order }}</div>
                                                            @endif
                                                            @if ($credit->is_principal)
                                                                <div class="sb-crew-credit-note">Principal</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <x-ui.empty class="mt-4 rounded-box border border-dashed border-black/10 dark:border-white/10">
                                    <x-ui.empty.media>
                                        <x-ui.icon name="briefcase" class="size-8 text-neutral-400 dark:text-neutral-500" />
                                    </x-ui.empty.media>
                                    <x-ui.heading level="h3">Technical departments are not published yet.</x-ui.heading>
                                    <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                                        The archive keeps the technical slot visible so craft-team credits can drop in without changing the page structure.
                                    </x-ui.text>
                                </x-ui.empty>
                            @endif
                        </div>
                    </div>

                    <div>
                        {{ $crewCredits->links() }}
                    </div>
                </div>
            </x-ui.card>
        </div>
    </section>
@endsection
