<x-ui.card class="!max-w-none">
    <div class="space-y-4">
        <div class="space-y-2">
            <x-ui.heading level="h2" size="lg">Create a list</x-ui.heading>
            <x-ui.text class="text-neutral-600 dark:text-neutral-300">
                Loading the list composer.
            </x-ui.text>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.field class="sm:col-span-2">
                <x-ui.label>Name</x-ui.label>
                <x-ui.skeleton class="h-10 w-full rounded-box" />
            </x-ui.field>

            <x-ui.field class="sm:col-span-2">
                <x-ui.label>Description</x-ui.label>
                <x-ui.skeleton class="h-28 w-full rounded-box" />
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Visibility</x-ui.label>
                <x-ui.skeleton class="h-10 w-full rounded-box" />
            </x-ui.field>
        </div>

        <div class="flex justify-end">
            <x-ui.skeleton class="h-10 w-28 rounded-full" />
        </div>
    </div>
</x-ui.card>
