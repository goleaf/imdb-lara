<div class="contents">
    <x-ui.card class="!max-w-none">
        <div class="space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-2">
                    <x-ui.heading level="h3" size="md">
                        {{ str($report->reason->value)->headline() }} report
                    </x-ui.heading>
                    <div class="text-sm text-neutral-500 dark:text-neutral-400">
                        Reported by {{ $report->reporter->name }} · Target: {{ str(class_basename($report->reportable_type))->headline() }}
                    </div>
                    @if ($report->created_at)
                        <div class="text-sm text-neutral-500 dark:text-neutral-400">
                            Submitted {{ $report->created_at->format('M j, Y') }}
                        </div>
                    @endif
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-ui.badge variant="outline" color="neutral" icon="flag">{{ str($report->status->value)->headline() }}</x-ui.badge>
                    @if ($report->reviewed_at)
                        <x-ui.badge variant="outline" color="slate" icon="calendar-days">{{ $report->reviewed_at->format('M j, Y') }}</x-ui.badge>
                    @endif
                </div>
            </div>

            <div class="grid gap-3">
                @if ($report->reportable instanceof \App\Models\Review)
                    <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="space-y-1">
                                <div class="font-medium">
                                    @if ($reportedReviewTitle)
                                        <a href="{{ route('public.titles.show', $reportedReviewTitle) }}" class="hover:opacity-80">
                                            {{ $report->reportable->headline ?: 'Untitled review' }}
                                        </a>
                                    @else
                                        {{ $report->reportable->headline ?: 'Untitled review' }}
                                    @endif
                                </div>
                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $report->reportable->author->name }} on {{ $reportedReviewTitle?->name ?? 'Unknown title' }}
                                </div>
                            </div>

                            <x-ui.badge variant="outline" color="neutral" icon="chat-bubble-left-right">
                                {{ $report->reportable->status->label() }}
                            </x-ui.badge>
                        </div>

                        <x-ui.text class="mt-3 text-sm text-neutral-600 dark:text-neutral-300">
                            {{ str($report->reportable->body)->limit(220) }}
                        </x-ui.text>
                    </div>
                @elseif ($report->reportable instanceof \App\Models\UserList)
                    <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="space-y-1">
                                <div class="font-medium">
                                    @if ($report->reportable->isShareable())
                                        <a href="{{ route('public.lists.show', ['user' => $report->reportable->user, 'list' => $report->reportable]) }}" class="hover:opacity-80">
                                            {{ $report->reportable->name }}
                                        </a>
                                    @else
                                        {{ $report->reportable->name }}
                                    @endif
                                </div>
                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                    {{ $report->reportable->user->name }} · {{ $report->reportable->visibility->label() }} visibility
                                </div>
                            </div>

                            <x-ui.badge variant="outline" color="neutral" icon="queue-list">
                                {{ number_format((int) ($report->reportable->items_count ?? 0)) }} titles
                            </x-ui.badge>
                        </div>

                        @if (filled($report->reportable->description))
                            <x-ui.text class="mt-3 text-sm text-neutral-600 dark:text-neutral-300">
                                {{ str($report->reportable->description)->limit(220) }}
                            </x-ui.text>
                        @endif
                    </div>
                @endif

                @if (filled($report->details))
                    <div class="rounded-box border border-dashed border-black/10 px-4 py-3 text-sm text-neutral-600 dark:border-white/10 dark:text-neutral-300">
                        {{ $report->details }}
                    </div>
                @endif
            </div>

            @if ($statusMessage)
                <x-ui.alerts variant="success" icon="check-circle">
                    <x-ui.alerts.description>{{ $statusMessage }}</x-ui.alerts.description>
                </x-ui.alerts>
            @endif

            <form wire:submit="save" class="grid gap-4 xl:grid-cols-[220px,220px,minmax(0,1fr),auto] xl:items-end">
                <x-ui.field>
                    <x-ui.label>Report status</x-ui.label>
                    <x-ui.native-select wire:model.live="status">
                        @foreach ($reportStatuses as $reportStatus)
                            <option wire:key="report-moderation-status-{{ $reportStatus->value }}" value="{{ $reportStatus->value }}">
                                {{ str($reportStatus->value)->headline() }}
                            </option>
                        @endforeach
                    </x-ui.native-select>
                    <x-ui.error name="status" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Content action</x-ui.label>
                    <x-ui.native-select wire:model.live="contentAction">
                        <option value="none">No content action</option>
                        <option value="hide_content">Hide reported content</option>
                    </x-ui.native-select>
                    <x-ui.error name="contentAction" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Resolution notes</x-ui.label>
                    <x-ui.input wire:model.live.blur="resolutionNotes" placeholder="Resolution notes" />
                    <x-ui.error name="resolutionNotes" />
                </x-ui.field>

                <div class="flex items-end">
                    <x-ui.button type="submit" wire:target="save" icon="check-circle">
                        Update
                    </x-ui.button>
                </div>

                @if ($report->reportableOwner() && ! $report->reportableOwner()->canAccessAdminPanel())
                    <div class="xl:col-span-4 rounded-box border border-dashed border-black/10 px-4 py-3 dark:border-white/10">
                        <x-ui.checkbox
                            wire:model.live="suspendOwner"
                            value="1"
                            :label="'Suspend '.$report->reportableOwner()->name.' from public activity.'"
                        />
                    </div>
                @endif
            </form>

            @if ($report->reviewer)
                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                    Reviewed by {{ $report->reviewer->name }}
                    @if ($report->reviewed_at)
                        · {{ $report->reviewed_at->format('M j, Y') }}
                    @endif
                </div>
            @endif
        </div>
    </x-ui.card>
</div>
