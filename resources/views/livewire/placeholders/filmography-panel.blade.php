<div id="person-filmography" data-slot="person-filmography-panel" class="space-y-4">
    <x-ui.card class="sb-detail-section sb-person-filmography-shell !max-w-none">
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <x-ui.heading level="h2" size="lg">Filmography</x-ui.heading>
                    <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                        Loading the credit graph for this profile.
                    </x-ui.text>
                </div>

                <x-ui.badge variant="outline" color="neutral" icon="film">Loading</x-ui.badge>
            </div>

            <div class="sb-person-filmography-toolbar">
                <x-ui.field>
                    <x-ui.label>Credit group</x-ui.label>
                    <x-ui.skeleton class="h-10 w-full rounded-box" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Sort</x-ui.label>
                    <x-ui.skeleton class="h-10 w-full rounded-box" />
                </x-ui.field>
            </div>
        </div>
    </x-ui.card>

    <div class="space-y-4">
        @foreach (range(1, 2) as $groupIndex)
            <x-ui.card class="sb-detail-section sb-person-filmography-group !max-w-none" wire:key="filmography-placeholder-group-{{ $groupIndex }}">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <x-ui.skeleton.text class="w-1/4" />
                        <x-ui.skeleton.text class="w-1/6" />
                    </div>

                    <div class="grid gap-3">
                        @foreach (range(1, 3) as $rowIndex)
                            <div class="sb-person-filmography-row" wire:key="filmography-placeholder-row-{{ $groupIndex }}-{{ $rowIndex }}">
                                <x-ui.skeleton class="aspect-[2/3] w-full rounded-[1rem] sm:max-w-[5rem]" />
                                <div class="space-y-3">
                                    <x-ui.skeleton.text class="w-1/3" />
                                    <x-ui.skeleton.text class="w-5/6" />
                                    <x-ui.skeleton.text class="w-2/5" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </x-ui.card>
        @endforeach
    </div>
</div>
