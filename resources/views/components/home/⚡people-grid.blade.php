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

            <div class="grid gap-4 grid-cols-2 md:grid-cols-3 xl:grid-cols-6">
                @foreach (range(1, 6) as $index)
                    <x-ui.card class="!max-w-none h-full overflow-hidden" wire:key="home-people-placeholder-{{ $index }}">
                        <div class="space-y-4">
                            <x-ui.skeleton class="aspect-[3/4] w-full rounded-box" />
                            <x-ui.skeleton.text class="w-2/3" />
                            <x-ui.skeleton.text class="w-1/2" />
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
        </div>
    @endplaceholder

    <div class="space-y-4">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <x-ui.heading level="h2" size="lg">Popular People</x-ui.heading>
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
                <x-ui.heading level="h3">No public people profiles are available yet.</x-ui.heading>
                <x-ui.text class="mt-1 text-neutral-500 dark:text-neutral-400">
                    Once cast and crew pages are published, they will appear here.
                </x-ui.text>
            </x-ui.empty>
        @else
            <div class="grid gap-4 grid-cols-2 md:grid-cols-3 xl:grid-cols-6">
                @foreach ($people as $person)
                    <div wire:key="home-people-{{ $person->id }}">
                        <x-catalog.person-card :person="$person" />
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
