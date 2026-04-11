<div class="contents">
    <x-ui.card @class([
        '!max-w-none',
        'border-red-200/70 dark:border-red-500/40' => $review->open_reports_count > 0,
    ])>
        <div class="space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-2">
                    <x-ui.heading level="h3" size="md">{{ $review->headline ?: 'Untitled review' }}</x-ui.heading>
                    <div class="text-sm text-neutral-500 dark:text-neutral-400">
                        {{ $review->author->name }} on
                        <a href="{{ route('public.titles.show', $review->title) }}" class="hover:opacity-80">
                            {{ $review->title->name }}
                        </a>
                    </div>
                    <div class="text-sm text-neutral-500 dark:text-neutral-400">
                        {{ number_format($review->helpful_votes_count) }} helpful votes · {{ number_format($review->open_reports_count) }} open reports
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-ui.badge variant="outline" color="neutral" icon="chat-bubble-left-right">{{ str($review->status->value)->headline() }}</x-ui.badge>
                    @if ($review->open_reports_count > 0)
                        <x-ui.badge variant="outline" color="red" icon="flag">
                            {{ number_format($review->open_reports_count) }} open reports
                        </x-ui.badge>
                    @endif
                    @if ($review->published_at)
                        <x-ui.badge variant="outline" color="slate" icon="calendar-days">{{ $review->published_at->format('M j, Y') }}</x-ui.badge>
                    @endif
                </div>
            </div>

            @if ($statusMessage)
                <x-ui.alerts variant="success" icon="check-circle">
                    <x-ui.alerts.description>{{ $statusMessage }}</x-ui.alerts.description>
                </x-ui.alerts>
            @endif

            <form wire:submit="save" class="grid gap-4 md:grid-cols-[220px,minmax(0,1fr),auto] md:items-end">
                <x-ui.field>
                    <x-ui.label>Review status</x-ui.label>
                    <x-ui.native-select wire:model.live="status">
                        @foreach ($reviewStatuses as $reviewStatus)
                            <option value="{{ $reviewStatus->value }}">
                                {{ str($reviewStatus->value)->headline() }}
                            </option>
                        @endforeach
                    </x-ui.native-select>
                    <x-ui.error name="status" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Moderation notes</x-ui.label>
                    <x-ui.input wire:model="moderationNotes" placeholder="Moderation notes" />
                    <x-ui.error name="moderationNotes" />
                </x-ui.field>

                <div class="flex items-end">
                    <x-ui.button type="submit" wire:target="save" icon="check-circle">
                        Update
                    </x-ui.button>
                </div>
            </form>
        </div>
    </x-ui.card>
</div>
