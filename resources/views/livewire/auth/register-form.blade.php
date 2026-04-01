<form wire:submit="register" class="space-y-4">
    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.field>
            <x-ui.label>Name</x-ui.label>
            <x-ui.input wire:model.live="form.name" name="name" autocomplete="name" />
            <x-ui.error name="form.name" />
        </x-ui.field>

        <x-ui.field>
            <x-ui.label>Username</x-ui.label>
            <x-ui.input wire:model.live="form.username" name="username" autocomplete="username" />
            <x-ui.error name="form.username" />
        </x-ui.field>
    </div>

    <x-ui.field>
        <x-ui.label>Email</x-ui.label>
        <x-ui.input wire:model.live="form.email" name="email" type="email" autocomplete="email" />
        <x-ui.error name="form.email" />
    </x-ui.field>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.field>
            <x-ui.label>Password</x-ui.label>
            <x-ui.input
                wire:model.live="form.password"
                name="password"
                type="password"
                autocomplete="new-password"
                revealable
            />
            <x-ui.error name="form.password" />
        </x-ui.field>

        <x-ui.field>
            <x-ui.label>Confirm password</x-ui.label>
            <x-ui.input
                wire:model.live="form.password_confirmation"
                name="password_confirmation"
                type="password"
                autocomplete="new-password"
                revealable
            />
            <x-ui.error name="form.password_confirmation" />
        </x-ui.field>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-ui.button type="submit" icon="user-plus">
            Create account
        </x-ui.button>

        <x-ui.link :href="route('login')" variant="ghost">
            Already registered?
        </x-ui.link>
    </div>
</form>
