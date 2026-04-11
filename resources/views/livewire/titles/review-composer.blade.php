<x-ui.card class="!max-w-none" :id="$anchorId">
    <form wire:submit="save" class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-2">
                <x-ui.heading level="h3" size="md">{{ $review ? 'Your review' : 'Write a review' }}</x-ui.heading>
                <x-ui.text class="text-neutral-600 dark:text-neutral-300">
                    Reviews are stored against the title and may enter moderation before publication.
                </x-ui.text>
            </div>

            @auth
                <div class="flex flex-wrap items-center gap-2 text-sm text-neutral-500 dark:text-neutral-400">
                    @if ($review)
                        <x-ui.badge variant="outline" :color="$reviewStatusBadge['color']" icon="chat-bubble-left-right">
                            {{ $reviewStatusBadge['label'] }}
                        </x-ui.badge>

                        @if ($reviewSavedAt)
                            <span>Saved {{ $reviewSavedAt->format('M j, Y') }}</span>
                        @endif
                    @else
                        <x-ui.badge variant="outline" color="neutral" icon="pencil-square">
                            No review yet
                        </x-ui.badge>
                    @endif
                </div>
            @endauth
        </div>

        @guest
            <x-ui.alerts variant="info" icon="information-circle">
                <x-ui.alerts.heading>Sign in to write a review.</x-ui.alerts.heading>
                <x-ui.alerts.description>
                    Share your reaction, update it later, and publish it after moderation.
                </x-ui.alerts.description>
            </x-ui.alerts>
        @endguest

        @if ($statusMessage)
            <x-ui.alerts variant="success" icon="check-circle">
                <x-ui.alerts.description>{{ $statusMessage }}</x-ui.alerts.description>
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

        <div class="flex flex-wrap justify-between gap-3">
            <div class="flex flex-wrap gap-2">
                @auth
                    @if ($review)
                        <x-ui.button
                            type="button"
                            variant="ghost"
                            icon="trash"
                            wire:click="delete"
                            wire:target="save,saveDraft,delete"
                        >
                            Delete review
                        </x-ui.button>
                    @endif
                @endauth
            </div>

            <div class="flex flex-wrap gap-2">
                <x-ui.button
                    type="button"
                    variant="outline"
                    color="neutral"
                    icon="pencil-square"
                    wire:click="saveDraft"
                    wire:target="save,saveDraft,delete"
                >
                    Save draft
                </x-ui.button>

                <x-ui.button
                    type="submit"
                    icon="chat-bubble-left-right"
                    wire:target="save,saveDraft,delete"
                >
                    {{ $review ? 'Update review' : 'Submit review' }}
                </x-ui.button>
            </div>
        </div>
    </form>
</x-ui.card>
