<x-ui.card class="!max-w-none" id="title-lists">
    <form wire:submit="save" class="space-y-4">
        <div class="space-y-2">
            <x-ui.heading level="h3" size="md">Custom lists</x-ui.heading>
            <x-ui.text class="text-neutral-600 dark:text-neutral-300">
                Add this title to any custom list you own.
            </x-ui.text>
        </div>

        @guest
            <x-ui.alerts variant="info" icon="information-circle">
                <x-ui.alerts.heading>Sign in to use custom lists.</x-ui.alerts.heading>
                <x-ui.alerts.description>
                    Create collections and attach this title to them once you are signed in.
                </x-ui.alerts.description>
            </x-ui.alerts>
        @else
            @if ($statusMessage)
                <x-ui.alerts variant="success" icon="check-circle">
                    <x-ui.alerts.description>{{ $statusMessage }}</x-ui.alerts.description>
                </x-ui.alerts>
            @endif

            @if ($this->selectedLists->isNotEmpty())
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <x-ui.text class="text-sm font-medium text-neutral-700 dark:text-neutral-200">
                            Already saved in your lists
                        </x-ui.text>
                        <x-ui.badge variant="outline" color="neutral" icon="queue-list">
                            {{ number_format($this->selectedLists->count()) }} {{ str('list')->plural($this->selectedLists->count()) }}
                        </x-ui.badge>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @foreach ($this->selectedLists as $selectedList)
                            <x-ui.badge
                                wire:key="selected-title-list-{{ $selectedList->id }}"
                                variant="outline"
                                color="amber"
                                :icon="$selectedList->visibility->icon()"
                            >
                                {{ $selectedList->name }}
                            </x-ui.badge>
                        @endforeach
                    </div>
                </div>
            @endif

            <x-ui.field>
                <x-ui.label>Your lists</x-ui.label>
                <x-ui.combobox
                    wire:model="selectedListIds"
                    class="w-full"
                    multiple
                    clearable
                    placeholder="Search or choose lists"
                    :prevent-loading="filled($listQuery) && $this->lists->isEmpty()"
                >
                    <x-slot:search>
                        <x-ui.combobox.input
                            wire:model.live.debounce.300ms="listQuery"
                            placeholder="Search or create a list"
                        />
                    </x-slot:search>

                    @forelse ($this->lists as $list)
                        <x-ui.combobox.option
                            wire:key="title-list-{{ $list->id }}"
                            value="{{ $list->id }}"
                            searchLabel="{{ $list->name.' '.str($list->visibility->value)->headline() }}"
                        >
                            {{ $list->name }}
                        </x-ui.combobox.option>
                    @empty
                        @if (filled($listQuery) && ! $showCreateListForm)
                            <x-ui.combobox.option.create wire:click="startCreatingList">
                                Create "{{ $listQuery }}"
                            </x-ui.combobox.option.create>
                        @else
                            <x-ui.combobox.option.empty>
                                {{ $showCreateListForm ? 'Finish creating the new list below.' : 'No matching lists yet.' }}
                            </x-ui.combobox.option.empty>
                        @endif
                    @endforelse
                </x-ui.combobox>
                <x-ui.text class="mt-2 text-neutral-500 dark:text-neutral-400">
                    Search your existing lists, or type a new name to create one inline.
                </x-ui.text>
            </x-ui.field>

            @if ($showCreateListForm)
                <div class="rounded-box border border-black/10 p-4 dark:border-white/10">
                    <div class="space-y-1">
                        <x-ui.heading level="h4" size="sm">Create a new list</x-ui.heading>
                        <x-ui.text class="text-neutral-500 dark:text-neutral-400">
                            Finish the list details here, then update lists to save this title into it.
                        </x-ui.text>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <x-ui.field class="sm:col-span-2">
                            <x-ui.label>Name</x-ui.label>
                            <x-ui.input
                                wire:model.live.blur="createListForm.name"
                                name="create_list_name"
                                placeholder="Friday Night Picks"
                                x-on:keydown.enter.prevent="$wire.createList()"
                            />
                            <x-ui.error name="createListForm.name" />
                        </x-ui.field>

                        <x-ui.field class="sm:col-span-2">
                            <x-ui.label>Description</x-ui.label>
                            <x-ui.textarea wire:model.live.blur="createListForm.description" name="create_list_description" rows="3" placeholder="What ties these titles together?" />
                            <x-ui.error name="createListForm.description" />
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Visibility</x-ui.label>
                            <x-ui.combobox
                                wire:model.live="createListForm.visibility"
                                class="w-full"
                                placeholder="Select visibility"
                                :invalid="$errors->has('createListForm.visibility')"
                            >
                                @foreach ($this->visibilityOptions as $visibilityOption)
                                    <x-ui.combobox.option
                                        wire:key="inline-list-visibility-{{ $visibilityOption['value'] }}"
                                        value="{{ $visibilityOption['value'] }}"
                                        :icon="$visibilityOption['icon']"
                                    >
                                        {{ $visibilityOption['label'] }}
                                    </x-ui.combobox.option>
                                @endforeach
                            </x-ui.combobox>
                            <x-ui.error name="createListForm.visibility" />
                        </x-ui.field>
                    </div>

                    <div class="mt-4 flex justify-end gap-3">
                        <x-ui.button type="button" variant="ghost" icon="x-mark" wire:click="cancelCreatingList">
                            Cancel
                        </x-ui.button>
                        <x-ui.button type="button" icon="plus" wire:click="createList" wire:target="createList">
                            Create list
                        </x-ui.button>
                    </div>
                </div>
            @endif

            <div class="flex justify-end">
                <x-ui.button type="submit" icon="queue-list" wire:target="save">
                    Update lists
                </x-ui.button>
            </div>
        @endguest
    </form>
</x-ui.card>
