@props([
    'backHref',
    'backLabel' => 'Back',
    'description' => 'Catalog write workflows are currently disabled while Screenbase is running in catalog-only mode. This screen remains routed through Livewire, but create, update, upload, reorder, and delete actions stay paused until the local write-side models are reconciled with the remote catalog.',
    'heading' => 'Catalog Writes Paused',
])

<x-ui.card class="!max-w-none">
    <div class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-2">
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.badge variant="outline" color="amber" icon="pause-circle">Catalog-only mode</x-ui.badge>
                    <x-ui.badge variant="outline" color="neutral" icon="bolt">Livewire shell active</x-ui.badge>
                </div>

                <div>
                    <x-ui.heading level="h2" size="lg">{{ $heading }}</x-ui.heading>
                    <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                        {{ $description }}
                    </x-ui.text>
                </div>
            </div>

            <x-ui.button as="a" :href="$backHref" variant="outline" icon="arrow-left">
                {{ $backLabel }}
            </x-ui.button>
        </div>

        @if ($slot->isNotEmpty())
            <div class="rounded-box border border-dashed border-black/10 px-4 py-3 text-sm text-neutral-600 dark:border-white/10 dark:text-neutral-300">
                {{ $slot }}
            </div>
        @endif
    </div>
</x-ui.card>
