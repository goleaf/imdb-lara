<x-ui.card class="!max-w-none">
    <form wire:submit="save" class="space-y-4">
        <div class="space-y-2">
            <x-ui.heading level="h3" size="md">Write a review</x-ui.heading>
            <x-ui.text class="text-neutral-600 dark:text-neutral-300">
                Reviews are stored against the title and may enter moderation before publication.
            </x-ui.text>
        </div>

        @if ($statusMessage)
            <x-ui.alerts variant="success" icon="check-circle">
                <x-ui.alerts.heading>{{ $statusMessage }}</x-ui.alerts.heading>
            </x-ui.alerts>
        @endif

        <x-ui.field>
            <x-ui.label>Headline</x-ui.label>
            <x-ui.input wire:model.live="form.headline" name="headline" maxlength="160" placeholder="Optional review headline" />
            <x-ui.error name="form.headline" />
        </x-ui.field>

        <x-ui.field>
            <x-ui.label>Review</x-ui.label>
            <x-ui.textarea wire:model.live="form.body" name="body" rows="6" placeholder="Share what worked, what did not, and who this title is for." />
            <x-ui.error name="form.body" />
        </x-ui.field>

        <label class="flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-300">
            <input wire:model.live="form.containsSpoilers" type="checkbox" class="rounded border-black/20 dark:border-white/20">
            <span>This review contains spoilers.</span>
        </label>

        <div class="flex justify-end">
            <x-ui.button type="submit" icon="chat-bubble-left-right">
                Submit review
            </x-ui.button>
        </div>
    </form>
</x-ui.card>
