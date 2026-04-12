<x-ui.card class="!max-w-none">
    <form wire:submit="save" class="space-y-4">
        <div class="space-y-2">
            <x-ui.heading level="h2" size="lg">Suggest an edit</x-ui.heading>
            <x-ui.text class="text-neutral-600 dark:text-neutral-300">
                Submit a structured correction for the {{ $entityLabel }}. Suggestions enter the editorial queue before they are merged.
            </x-ui.text>
        </div>

        <x-ui.alerts wire:show="statusMessage" variant="success" icon="check-circle">
            <x-ui.alerts.description>
                <span wire:text="statusMessage">{{ $statusMessage }}</span>
            </x-ui.alerts.description>
        </x-ui.alerts>

        @guest
            <x-ui.alerts variant="info" icon="information-circle">
                <x-ui.alerts.heading>Sign in to suggest catalog edits.</x-ui.alerts.heading>
                <x-ui.alerts.description>
                    Contributor and staff accounts can send structured changes for {{ $contributableLabel }}.
                </x-ui.alerts.description>
            </x-ui.alerts>
        @else
            @unless ($canSubmitContribution)
                <x-ui.alerts variant="info" icon="shield-check">
                    <x-ui.alerts.heading>Contribution access is limited.</x-ui.alerts.heading>
                    <x-ui.alerts.description>
                        Contributor, moderator, editor, and admin roles can submit catalog fixes for editorial review.
                    </x-ui.alerts.description>
                </x-ui.alerts>
            @endunless
        @endguest

        <div class="grid gap-4 sm:grid-cols-2">
            <x-ui.field class="sm:col-span-2">
                <x-ui.label>Field</x-ui.label>
                <x-ui.combobox
                    wire:model.live="field"
                    class="w-full"
                    placeholder="Select a field"
                    :invalid="$errors->has('field')"
                    :disabled="! $canSubmitContribution"
                >
                    @foreach ($fieldOptions as $fieldOption)
                        <x-ui.combobox.option
                            wire:key="contribution-field-{{ $contributableType }}-{{ $fieldOption['value'] }}"
                            value="{{ $fieldOption['value'] }}"
                            :icon="$fieldOption['icon']"
                        >
                            {{ $fieldOption['label'] }}
                        </x-ui.combobox.option>
                    @endforeach
                </x-ui.combobox>
                <x-ui.error name="field" />
            </x-ui.field>

            <x-ui.field class="sm:col-span-2">
                <x-ui.label>Proposed update</x-ui.label>
                <x-ui.textarea
                    wire:model.live.debounce.500ms="value"
                    name="value"
                    rows="5"
                    placeholder="Describe the corrected value or the exact change that should be made."
                    :disabled="! $canSubmitContribution"
                />
                <x-ui.error name="value" />
            </x-ui.field>

            <x-ui.field class="sm:col-span-2">
                <x-ui.label>Editorial notes</x-ui.label>
                <x-ui.textarea
                    wire:model.live.debounce.500ms="notes"
                    name="notes"
                    rows="4"
                    placeholder="Optional context for the editorial team, including evidence or source notes."
                    :disabled="! $canSubmitContribution"
                />
                <x-ui.error name="notes" />
            </x-ui.field>
        </div>

        <div class="flex justify-end">
            <x-ui.button type="submit" icon="paper-airplane" :disabled="! $canSubmitContribution" wire:target="save">
                Submit suggestion
            </x-ui.button>
        </div>
    </form>
</x-ui.card>
