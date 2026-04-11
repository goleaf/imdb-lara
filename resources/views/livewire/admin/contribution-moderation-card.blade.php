<div class="contents">
    <x-ui.card class="!max-w-none">
        <div class="space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-2">
                    <x-ui.heading level="h3" size="md">{{ str($contribution->action->value)->headline() }} contribution</x-ui.heading>
                    <div class="text-sm text-neutral-500 dark:text-neutral-400">
                        {{ $contribution->user->name }} · {{ class_basename($contribution->contributable_type) }} suggestion
                    </div>
                    @if ($contribution->created_at)
                        <div class="text-sm text-neutral-500 dark:text-neutral-400">
                            Submitted {{ $contribution->created_at->format('M j, Y') }}
                        </div>
                    @endif
                    @if ($contribution->contributableAdminUrl() && $contribution->contributableLabel())
                        <div class="text-sm text-neutral-500 dark:text-neutral-400">
                            <a href="{{ $contribution->contributableAdminUrl() }}" class="hover:opacity-80">
                                {{ $contribution->contributableLabel() }}
                            </a>
                        </div>
                    @endif
                </div>

                <x-ui.badge variant="outline" icon="clipboard-document-check">
                    {{ str($contribution->status->value)->headline() }}
                </x-ui.badge>
            </div>

            <div class="grid gap-3">
                <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                    <div class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Field</div>
                    <div class="mt-1 text-sm text-neutral-800 dark:text-neutral-100">
                        {{ $contribution->proposed_field_label ?: 'General catalog correction' }}
                    </div>
                </div>

                @if ($contribution->proposed_value)
                    <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                        <div class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Proposed update</div>
                        <div class="mt-1 text-sm text-neutral-800 dark:text-neutral-100">
                            {{ $contribution->proposed_value }}
                        </div>
                    </div>
                @endif

                @if ($contribution->submission_notes)
                    <div class="rounded-box border border-dashed border-black/10 px-4 py-3 text-sm text-neutral-600 dark:border-white/10 dark:text-neutral-300">
                        {{ $contribution->submission_notes }}
                    </div>
                @endif
            </div>

            @if ($statusMessage)
                <x-ui.alerts variant="success" icon="check-circle">
                    <x-ui.alerts.description>{{ $statusMessage }}</x-ui.alerts.description>
                </x-ui.alerts>
            @endif

            <form wire:submit="save" class="grid gap-4 md:grid-cols-[220px,1fr,auto]">
                <x-ui.field>
                    <x-ui.label>Status</x-ui.label>
                    <x-ui.native-select wire:model.live="status">
                        @foreach ($contributionStatuses as $contributionStatus)
                            <option wire:key="contribution-moderation-status-{{ $contributionStatus->value }}" value="{{ $contributionStatus->value }}">
                                {{ str($contributionStatus->value)->headline() }}
                            </option>
                        @endforeach
                    </x-ui.native-select>
                    <x-ui.error name="status" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Review notes</x-ui.label>
                    <x-ui.input wire:model.live.blur="notes" placeholder="Review notes" />
                    <x-ui.error name="notes" />
                </x-ui.field>

                <div class="flex items-end">
                    <x-ui.button type="submit" wire:target="save" icon="check-circle">
                        Update
                    </x-ui.button>
                </div>
            </form>

            @if ($contribution->review_notes)
                <div class="rounded-box border border-black/5 px-4 py-3 text-sm text-neutral-600 dark:border-white/10 dark:text-neutral-300">
                    {{ $contribution->review_notes }}
                </div>
            @endif

            @if ($contribution->reviewer)
                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                    Reviewed by {{ $contribution->reviewer->name }}
                    @if ($contribution->reviewed_at)
                        · {{ $contribution->reviewed_at->format('M j, Y') }}
                    @endif
                </div>
            @endif
        </div>
    </x-ui.card>
</div>
