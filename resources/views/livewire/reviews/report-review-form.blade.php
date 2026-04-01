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
                <x-ui.combobox
                    wire:model.live="form.reason"
                    class="w-full"
                    placeholder="Choose a reason"
                    :invalid="$errors->has('form.reason')"
                >
                    @foreach ($reportReasons as $reportReason)
                        <x-ui.combobox.option
                            wire:key="report-reason-{{ $review->id }}-{{ $reportReason['value'] }}"
                            value="{{ $reportReason['value'] }}"
                        >
                            {{ $reportReason['label'] }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
                <x-ui.error name="form.reason" />
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Details</x-ui.label>
                <x-ui.textarea wire:model.live="form.details" name="details" rows="5" placeholder="Optional context for the moderation team." />
                <x-ui.error name="form.details" />
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
