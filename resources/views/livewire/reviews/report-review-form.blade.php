@php
    $modalId = 'report-review-'.$review->id;
@endphp

<div class="flex items-center justify-between gap-3">
    @if ($statusMessage)
        <span class="text-sm text-green-600 dark:text-green-400">{{ $statusMessage }}</span>
    @else
        <span class="text-sm text-neutral-500 dark:text-neutral-400">See an issue with this review?</span>
    @endif

    <x-ui.modal.trigger :id="$modalId">
        <x-ui.button variant="ghost" size="sm" icon="flag">
            Report
        </x-ui.button>
    </x-ui.modal.trigger>

    <x-ui.modal
        :id="$modalId"
        heading="Report review"
        description="Choose the reason and optionally add context for moderators."
        width="lg"
        sticky-footer
    >
        <form id="review-report-form-{{ $review->id }}" wire:submit="save" class="space-y-4">
            <x-ui.field>
                <x-ui.label>Reason</x-ui.label>
                <select
                    wire:model.live="reason"
                    class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
                >
                    @foreach ($reportReasons as $reportReason)
                        <option value="{{ $reportReason['value'] }}">{{ $reportReason['label'] }}</option>
                    @endforeach
                </select>
                <x-ui.error name="reason" />
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Details</x-ui.label>
                <x-ui.textarea wire:model.live="details" name="details" rows="5" placeholder="Optional context for the moderation team." />
                <x-ui.error name="details" />
            </x-ui.field>
        </form>

        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="$modal.close('{{ $modalId }}')">
                Cancel
            </x-ui.button>
            <x-ui.button type="submit" form="review-report-form-{{ $review->id }}" icon="flag">
                Submit report
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
