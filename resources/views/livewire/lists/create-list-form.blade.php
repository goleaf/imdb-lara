<x-ui.card class="!max-w-none">
    <form wire:submit="save" class="space-y-4">
        <div class="space-y-2">
            <x-ui.heading level="h2" size="lg">Create a list</x-ui.heading>
            <x-ui.text class="text-neutral-600 dark:text-neutral-300">
                Create public or private collections that sit alongside your watchlist.
            </x-ui.text>
        </div>

        @if ($statusMessage)
            <x-ui.alerts variant="success" icon="check-circle">
                <x-ui.alerts.heading>{{ $statusMessage }}</x-ui.alerts.heading>
            </x-ui.alerts>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.field class="sm:col-span-2">
                <x-ui.label>Name</x-ui.label>
                <x-ui.input wire:model.live="form.name" name="name" placeholder="Friday Night Picks" />
                <x-ui.error name="form.name" />
            </x-ui.field>

            <x-ui.field class="sm:col-span-2">
                <x-ui.label>Description</x-ui.label>
                <x-ui.textarea wire:model.live="form.description" name="description" rows="4" placeholder="What ties these titles together?" />
                <x-ui.error name="form.description" />
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Visibility</x-ui.label>
                <select
                    wire:model.live="form.visibility"
                    class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
                >
                    <option value="private">Private</option>
                    <option value="public">Public</option>
                </select>
                <x-ui.error name="form.visibility" />
            </x-ui.field>
        </div>

        <div class="flex justify-end">
            <x-ui.button type="submit" icon="plus">
                Create list
            </x-ui.button>
        </div>
    </form>
</x-ui.card>
