<div class="grid gap-4 lg:grid-cols-2">
    <x-ui.field>
        <x-ui.label>Title</x-ui.label>
        <x-ui.native-select wire:model.live="title_id" name="title_id">
            @foreach ($titles as $titleOption)
                <option value="{{ $titleOption->id }}" @selected((int) old('title_id', $credit->title_id) === $titleOption->id)>
                    {{ $titleOption->name }}
                </option>
            @endforeach
        </x-ui.native-select>
        <x-ui.error name="title_id" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Person</x-ui.label>
        <x-ui.native-select wire:model.live="person_id" name="person_id">
            @foreach ($people as $personOption)
                <option value="{{ $personOption->id }}" @selected((int) old('person_id', $credit->person_id) === $personOption->id)>
                    {{ $personOption->name }}
                </option>
            @endforeach
        </x-ui.native-select>
        <x-ui.error name="person_id" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Department</x-ui.label>
        <x-ui.input wire:model.defer="department" name="department" :value="old('department', $credit->department)" />
        <x-ui.error name="department" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Job</x-ui.label>
        <x-ui.input wire:model.defer="job" name="job" :value="old('job', $credit->job)" />
        <x-ui.error name="job" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Profession link</x-ui.label>
        <x-ui.native-select wire:model.live="person_profession_id" name="person_profession_id">
            <option value="">None</option>
            @foreach ($professions as $profession)
                <option value="{{ $profession->id }}" @selected((int) old('person_profession_id', $credit->person_profession_id) === $profession->id)>
                    {{ $profession->person->name }} · {{ $profession->profession }}
                </option>
            @endforeach
        </x-ui.native-select>
        <x-ui.error name="person_profession_id" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Episode specificity</x-ui.label>
        <x-ui.native-select wire:model.live="episode_id" name="episode_id">
            <option value="">General title credit</option>
            @foreach ($episodes as $episodeOption)
                <option value="{{ $episodeOption->id }}" @selected((int) old('episode_id', $credit->episode_id) === $episodeOption->id)>
                    {{ $episodeOption->title->name }}
                </option>
            @endforeach
        </x-ui.native-select>
        <x-ui.error name="episode_id" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Character name</x-ui.label>
        <x-ui.input wire:model.defer="character_name" name="character_name" :value="old('character_name', $credit->character_name)" />
        <x-ui.error name="character_name" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Credited as</x-ui.label>
        <x-ui.input wire:model.defer="credited_as" name="credited_as" :value="old('credited_as', $credit->credited_as)" />
        <x-ui.error name="credited_as" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Billing order</x-ui.label>
        <x-ui.input wire:model.defer="billing_order" name="billing_order" type="number" min="1" :value="old('billing_order', $credit->billing_order)" />
        <x-ui.error name="billing_order" />
    </x-ui.field>

    <label class="flex items-center gap-2 text-sm">
        <x-ui.checkbox wire:model="is_principal" name="is_principal" value="1" label="Principal credit" />
    </label>
</div>
