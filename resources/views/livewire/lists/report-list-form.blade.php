@php
    $modalId = 'report-list-'.$list->id;
    $reasonIcons = [
        'spoiler' => 'exclamation-triangle',
        'spam' => 'no-symbol',
        'abuse' => 'shield-exclamation',
        'harassment' => 'shield-exclamation',
        'inaccurate' => 'information-circle',
    ];
@endphp

<div class="space-y-3">
    <x-ui.alerts :variant="$statusMessage ? 'success' : 'info'" :icon="$statusMessage ? 'check-circle' : 'information-circle'">
        <x-ui.alerts.description>
            {{ $statusMessage ?: 'Need to flag an issue with this list?' }}
        </x-ui.alerts.description>

        <x-slot:controls class="self-center">
            <x-ui.modal.trigger :id="$modalId">
                <x-ui.button variant="ghost" size="sm" icon="flag">
                    Report list
                </x-ui.button>
            </x-ui.modal.trigger>
        </x-slot:controls>
    </x-ui.alerts>

    <x-ui.modal
        :id="$modalId"
        heading="Report list"
        description="Choose the reason and optionally add context for moderators."
        width="lg"
        sticky-footer
    >
        <form id="list-report-form-{{ $list->id }}" wire:submit="save" class="space-y-4">
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
                            wire:key="report-list-reason-{{ $list->id }}-{{ $reportReason['value'] }}"
                            value="{{ $reportReason['value'] }}"
                            :icon="$reasonIcons[$reportReason['value']] ?? 'flag'"
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
            <x-ui.button variant="ghost" icon="x-mark" x-on:click="$modal.close('{{ $modalId }}')">
                Cancel
            </x-ui.button>
            <x-ui.button type="submit" form="list-report-form-{{ $list->id }}" icon="flag">
                Submit report
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
