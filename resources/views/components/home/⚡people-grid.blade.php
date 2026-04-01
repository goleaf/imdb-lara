<?php

use App\Actions\Home\GetPopularPeopleAction;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Livewire\Component;

new class extends Component
{
    public EloquentCollection $people;

    public ?string $errorMessage = null;

    public function mount(GetPopularPeopleAction $getPopularPeople): void
    {
        $this->people = new EloquentCollection;

        try {
            $this->people = $getPopularPeople->handle();
        } catch (\Throwable $throwable) {
            report($throwable);

            $this->errorMessage = 'Popular people could not be loaded right now.';
        }
    }
};
?>

<div>
    @php
        $featuredPerson = $people->first();
        $supportingPeople = $people->slice(1)->values();
    @endphp

    @placeholder
        <div class="space-y-4">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <x-ui.heading level="h2" size="lg">Popular People</x-ui.heading>
                    <x-ui.text class="max-w-3xl text-sm text-neutral-600 dark:text-neutral-300">
                        Loading cast and crew profiles from the public catalog.
                    </x-ui.text>
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-[minmax(0,1.08fr)_minmax(0,0.92fr)]">
                <x-ui.card class="!max-w-none h-full overflow-hidden">
                    <div class="grid gap-5 md:grid-cols-[11rem_minmax(0,1fr)]">
                        <x-ui.skeleton class="aspect-[3/4] w-full rounded-box" />
                        <div class="space-y-4">
                            <x-ui.skeleton.text class="w-1/4" />
                            <x-ui.skeleton.text class="w-1/2" />
                            <x-ui.skeleton.text class="w-full" />
                            <x-ui.skeleton.text class="w-4/5" />
                            <div class="grid gap-3 sm:grid-cols-3">
                                @foreach (range(1, 3) as $statIndex)
                                    <x-ui.skeleton class="h-20 w-full rounded-box" wire:key="home-people-stat-placeholder-{{ $statIndex }}" />
                                @endforeach
                            </div>
                        </div>
                    </div>
                </x-ui.card>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                    @foreach (range(1, 4) as $index)
                        <x-ui.card class="!max-w-none h-full overflow-hidden" wire:key="home-people-placeholder-{{ $index }}">
                            <div class="flex items-center gap-4">
                                <x-ui.skeleton class="size-16 rounded-box" />
                                <div class="min-w-0 flex-1 space-y-3">
                                    <x-ui.skeleton.text class="w-1/2" />
                                    <x-ui.skeleton.text class="w-2/3" />
                                </div>
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>
            </div>
        </div>
    @endplaceholder

    <div class="space-y-4">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <x-ui.heading level="h2" size="lg" class="inline-flex items-center gap-2">
                    <x-ui.icon name="users" class="size-5 text-neutral-500 dark:text-neutral-400" />
                    <span>Popular People</span>
                </x-ui.heading>
                <x-ui.text class="max-w-3xl text-sm text-neutral-600 dark:text-neutral-300">
                    Actors, directors, writers, and producers with strong catalog presence right now.
                </x-ui.text>
            </div>

            <x-ui.link :href="route('public.people.index')" variant="ghost">
                See all people
            </x-ui.link>
        </div>

        @if ($errorMessage)
            <x-ui.card class="!max-w-none border-dashed border-red-200/70 dark:border-red-400/40">
                <div class="space-y-2">
                    <x-ui.heading level="h3">Section unavailable</x-ui.heading>
                    <x-ui.text class="text-sm text-neutral-600 dark:text-neutral-300">
                        {{ $errorMessage }}
                    </x-ui.text>
                </div>
            </x-ui.card>
        @elseif ($people->isEmpty())
            <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                <x-ui.empty.media>
                    <x-ui.icon name="users" class="size-10 text-neutral-400 dark:text-neutral-500" />
                </x-ui.empty.media>
                <x-ui.heading level="h3">No public people profiles are available yet.</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                    Once cast and crew pages are published, they will appear here.
                </x-ui.text>
            </x-ui.empty>
        @else
            <div class="grid gap-4 xl:grid-cols-[minmax(0,1.08fr)_minmax(0,0.92fr)]">
                @if ($featuredPerson)
                    @php
                        $featuredHeadshot = $featuredPerson->relationLoaded('mediaAssets')
                            ? \App\Models\MediaAsset::preferredFrom(
                                $featuredPerson->mediaAssets,
                                \App\Enums\MediaKind::Headshot,
                                \App\Enums\MediaKind::Gallery,
                                \App\Enums\MediaKind::Still,
                            )
                            : null;
                        $featuredProfessionLabels = $featuredPerson->relationLoaded('professions')
                            ? $featuredPerson->professions->pluck('profession')->filter()->unique()->take(3)
                            : collect();
                        $featuredSummary = $featuredPerson->short_biography ?: $featuredPerson->biography;
                    @endphp

                    <x-ui.card class="!max-w-none h-full overflow-hidden border-black/5 bg-[radial-gradient(circle_at_top_right,rgba(251,191,36,0.18),transparent_34%),linear-gradient(145deg,rgba(250,250,250,0.98),rgba(245,245,245,0.98))] dark:border-white/10 dark:bg-[radial-gradient(circle_at_top_right,rgba(56,189,248,0.16),transparent_28%),linear-gradient(145deg,rgba(23,23,23,0.98),rgba(10,10,10,0.98))]">
                        <div class="grid h-full gap-5 md:grid-cols-[11rem_minmax(0,1fr)]">
                            <div class="overflow-hidden rounded-box border border-black/5 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800">
                                @if ($featuredHeadshot)
                                    <img
                                        src="{{ $featuredHeadshot->url }}"
                                        alt="{{ $featuredHeadshot->alt_text ?: $featuredPerson->name }}"
                                        class="aspect-[3/4] h-full w-full object-cover"
                                        loading="lazy"
                                    >
                                @else
                                    <div class="flex aspect-[3/4] items-center justify-center text-neutral-500 dark:text-neutral-400">
                                        <x-ui.icon name="users" class="size-12" />
                                    </div>
                                @endif
                            </div>

                            <div class="flex h-full flex-col gap-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-ui.badge color="amber" icon="sparkles">Featured talent</x-ui.badge>

                                    @if ($featuredPerson->known_for_department)
                                        <x-ui.badge variant="outline" icon="briefcase">{{ $featuredPerson->known_for_department }}</x-ui.badge>
                                    @endif

                                    @if ($featuredPerson->nationality)
                                        <x-ui.badge variant="outline" color="slate" icon="globe-alt">{{ $featuredPerson->nationality }}</x-ui.badge>
                                    @endif
                                </div>

                                <div class="space-y-3">
                                    <x-ui.heading level="h3" size="xl">
                                        <a href="{{ route('public.people.show', $featuredPerson) }}" class="hover:opacity-80">
                                            {{ $featuredPerson->name }}
                                        </a>
                                    </x-ui.heading>

                                    @if ($featuredProfessionLabels->isNotEmpty())
                                        <div class="flex flex-wrap gap-2">
                                            @foreach ($featuredProfessionLabels as $professionLabel)
                                                <x-ui.badge variant="outline" color="neutral" icon="sparkles">{{ $professionLabel }}</x-ui.badge>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if (filled($featuredSummary))
                                        <x-ui.text class="max-w-2xl text-sm leading-7 text-neutral-600 dark:text-neutral-300">
                                            {{ str($featuredSummary)->limit(240) }}
                                        </x-ui.text>
                                    @endif
                                </div>

                                <div class="grid gap-3 sm:grid-cols-3">
                                    <div class="rounded-box border border-black/5 bg-white/70 p-3 dark:border-white/10 dark:bg-white/5">
                                        <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">
                                            <x-ui.icon name="film" class="size-4" />
                                            <span>Credits</span>
                                        </div>
                                        <div class="mt-2 text-2xl font-semibold text-neutral-900 dark:text-white">
                                            {{ number_format((int) ($featuredPerson->credits_count ?? 0)) }}
                                        </div>
                                    </div>

                                    <div class="rounded-box border border-black/5 bg-white/70 p-3 dark:border-white/10 dark:bg-white/5">
                                        <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">
                                            <x-ui.icon name="trophy" class="size-4" />
                                            <span>Awards</span>
                                        </div>
                                        <div class="mt-2 text-2xl font-semibold text-neutral-900 dark:text-white">
                                            {{ number_format((int) ($featuredPerson->award_nominations_count ?? 0)) }}
                                        </div>
                                    </div>

                                    <div class="rounded-box border border-black/5 bg-white/70 p-3 dark:border-white/10 dark:bg-white/5">
                                        <div class="inline-flex items-center gap-2 text-xs uppercase tracking-[0.18em] text-neutral-500 dark:text-neutral-400">
                                            <x-ui.icon name="chart-bar" class="size-4" />
                                            <span>Rank</span>
                                        </div>
                                        <div class="mt-2 text-2xl font-semibold text-neutral-900 dark:text-white">
                                            #{{ number_format((int) $featuredPerson->popularity_rank) }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-auto flex flex-wrap items-center justify-between gap-3">
                                    <x-ui.text class="text-sm text-neutral-500 dark:text-neutral-400">
                                        Profile, credits, collaborators, and gallery.
                                    </x-ui.text>

                                    <x-ui.button as="a" :href="route('public.people.show', $featuredPerson)" variant="outline" icon="user">
                                        View profile
                                    </x-ui.button>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                @endif

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                    @foreach ($supportingPeople as $person)
                        @php
                            $headshot = $person->relationLoaded('mediaAssets')
                                ? \App\Models\MediaAsset::preferredFrom(
                                    $person->mediaAssets,
                                    \App\Enums\MediaKind::Headshot,
                                    \App\Enums\MediaKind::Gallery,
                                    \App\Enums\MediaKind::Still,
                                )
                                : null;
                            $professionLabel = $person->relationLoaded('professions')
                                ? $person->professions->pluck('profession')->filter()->first()
                                : null;
                        @endphp

                        <x-ui.card class="!max-w-none h-full overflow-hidden" wire:key="home-people-{{ $person->id }}">
                            <div class="flex h-full items-start gap-4">
                                <div class="overflow-hidden rounded-box border border-black/5 bg-neutral-100 shadow-sm dark:border-white/10 dark:bg-neutral-800">
                                    @if ($headshot)
                                        <img
                                            src="{{ $headshot->url }}"
                                            alt="{{ $headshot->alt_text ?: $person->name }}"
                                            class="size-20 object-cover"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="flex size-20 items-center justify-center text-neutral-500 dark:text-neutral-400">
                                            <x-ui.icon name="user" class="size-8" />
                                        </div>
                                    @endif
                                </div>

                                <div class="flex min-w-0 flex-1 flex-col gap-3">
                                    <div class="space-y-2">
                                        <x-ui.heading level="h3" size="md">
                                            <a href="{{ route('public.people.show', $person) }}" class="hover:opacity-80">
                                                {{ $person->name }}
                                            </a>
                                        </x-ui.heading>

                                        <div class="flex flex-wrap gap-2">
                                            @if ($person->known_for_department)
                                                <x-ui.badge variant="outline" icon="briefcase">{{ $person->known_for_department }}</x-ui.badge>
                                            @endif

                                            @if ($professionLabel)
                                                <x-ui.badge variant="outline" color="neutral" icon="sparkles">{{ $professionLabel }}</x-ui.badge>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2 text-sm text-neutral-500 dark:text-neutral-400">
                                        <span class="inline-flex items-center gap-1.5">
                                            <x-ui.icon name="film" class="size-4" />
                                            <span>{{ number_format((int) ($person->credits_count ?? 0)) }} credits</span>
                                        </span>

                                        @if (($person->award_nominations_count ?? 0) > 0)
                                            <span class="inline-flex items-center gap-1.5">
                                                <x-ui.icon name="trophy" class="size-4" />
                                                <span>{{ number_format((int) $person->award_nominations_count) }} award hits</span>
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-auto">
                                        <x-ui.link :href="route('public.people.show', $person)" variant="ghost">
                                            Open profile
                                        </x-ui.link>
                                    </div>
                                </div>
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
