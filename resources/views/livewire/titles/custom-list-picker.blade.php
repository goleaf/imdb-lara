<x-ui.card class="!max-w-none">
    <form wire:submit="save" class="space-y-4">
        <div class="space-y-2">
            <x-ui.heading level="h3" size="md">Custom lists</x-ui.heading>
            <x-ui.text class="text-neutral-600 dark:text-neutral-300">
                Add this title to any custom list you own.
            </x-ui.text>
        </div>

        @guest
            <x-ui.alerts variant="info" icon="information-circle">
                <x-ui.alerts.heading>Sign in to use custom lists.</x-ui.alerts.heading>
            </x-ui.alerts>
        @else
            @if ($statusMessage)
                <x-ui.alerts variant="success" icon="check-circle">
                    <x-ui.alerts.heading>{{ $statusMessage }}</x-ui.alerts.heading>
                </x-ui.alerts>
            @endif

            @if ($lists->isEmpty())
                <x-ui.empty class="rounded-box border border-dashed border-black/10 dark:border-white/10">
                    <x-ui.heading level="h3">No custom lists yet.</x-ui.heading>
                    <x-ui.link :href="route('account.lists.index')" variant="ghost" class="mt-2">
                        Create your first list
                    </x-ui.link>
                </x-ui.empty>
            @else
                <div class="grid gap-3">
                    @foreach ($lists as $list)
                        <label class="flex items-center justify-between gap-3 rounded-box border border-black/5 px-4 py-3 text-sm dark:border-white/10">
                            <div>
                                <div class="font-medium">{{ $list->name }}</div>
                                <div class="text-neutral-500 dark:text-neutral-400">{{ str($list->visibility->value)->headline() }}</div>
                            </div>

                            <input
                                wire:model.live="selectedLists.{{ $list->id }}"
                                type="checkbox"
                                class="rounded border-black/20 dark:border-white/20"
                            >
                        </label>
                    @endforeach
                </div>

                <div class="flex justify-end">
                    <x-ui.button type="submit" icon="queue-list">
                        Update lists
                    </x-ui.button>
                </div>
            @endif
        @endguest
    </form>
</x-ui.card>
