<div class="grid gap-4 lg:grid-cols-2">
    <x-ui.field>
        <x-ui.label>Title</x-ui.label>
        <select
            name="title_id"
            class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
        >
            @foreach ($titles as $titleOption)
                <option value="{{ $titleOption->id }}" @selected((int) old('title_id', $credit->title_id) === $titleOption->id)>
                    {{ $titleOption->name }}
                </option>
            @endforeach
        </select>
        <x-ui.error name="title_id" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Person</x-ui.label>
        <select
            name="person_id"
            class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
        >
            @foreach ($people as $personOption)
                <option value="{{ $personOption->id }}" @selected((int) old('person_id', $credit->person_id) === $personOption->id)>
                    {{ $personOption->name }}
                </option>
            @endforeach
        </select>
        <x-ui.error name="person_id" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Department</x-ui.label>
        <x-ui.input name="department" :value="old('department', $credit->department)" />
        <x-ui.error name="department" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Job</x-ui.label>
        <x-ui.input name="job" :value="old('job', $credit->job)" />
        <x-ui.error name="job" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Profession link</x-ui.label>
        <select
            name="person_profession_id"
            class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
        >
            <option value="">None</option>
            @foreach ($professions as $profession)
                <option value="{{ $profession->id }}" @selected((int) old('person_profession_id', $credit->person_profession_id) === $profession->id)>
                    {{ $profession->person->name }} · {{ $profession->profession }}
                </option>
            @endforeach
        </select>
        <x-ui.error name="person_profession_id" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Episode specificity</x-ui.label>
        <select
            name="episode_id"
            class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"
        >
            <option value="">General title credit</option>
            @foreach ($episodes as $episodeOption)
                <option value="{{ $episodeOption->id }}" @selected((int) old('episode_id', $credit->episode_id) === $episodeOption->id)>
                    {{ $episodeOption->title->name }}
                </option>
            @endforeach
        </select>
        <x-ui.error name="episode_id" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Character name</x-ui.label>
        <x-ui.input name="character_name" :value="old('character_name', $credit->character_name)" />
        <x-ui.error name="character_name" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Credited as</x-ui.label>
        <x-ui.input name="credited_as" :value="old('credited_as', $credit->credited_as)" />
        <x-ui.error name="credited_as" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Billing order</x-ui.label>
        <x-ui.input name="billing_order" type="number" min="1" :value="old('billing_order', $credit->billing_order)" />
        <x-ui.error name="billing_order" />
    </x-ui.field>

    <label class="flex items-center gap-2 text-sm">
        <input type="hidden" name="is_principal" value="0">
        <input type="checkbox" name="is_principal" value="1" class="rounded border-black/20 dark:border-white/20 dark:bg-neutral-900" @checked(old('is_principal', $credit->is_principal))>
        <span>Principal credit</span>
    </label>
</div>
