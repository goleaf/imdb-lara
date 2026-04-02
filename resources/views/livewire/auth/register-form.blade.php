<form wire:submit="register" class="space-y-5">
    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.field>
            <x-ui.label class="sb-auth-label">Name</x-ui.label>
            <x-ui.input
                wire:model.live="form.name"
                name="name"
                autocomplete="name"
                left-icon="user"
                class="sb-auth-input"
            />
            <x-ui.error name="form.name" />
        </x-ui.field>

        <x-ui.field>
            <x-ui.label class="sb-auth-label">Username</x-ui.label>
            <x-ui.input
                wire:model.live="form.username"
                name="username"
                autocomplete="username"
                left-icon="at-symbol"
                class="sb-auth-input"
            />
            <x-ui.error name="form.username" />
        </x-ui.field>
    </div>

    <x-ui.field>
        <x-ui.label class="sb-auth-label">Email</x-ui.label>
        <x-ui.input
            wire:model.live="form.email"
            name="email"
            type="email"
            autocomplete="email"
            left-icon="envelope"
            class="sb-auth-input"
        />
        <x-ui.error name="form.email" />
    </x-ui.field>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-ui.field>
            <x-ui.label class="sb-auth-label">Password</x-ui.label>
            <x-ui.input
                wire:model.live="form.password"
                name="password"
                type="password"
                autocomplete="new-password"
                left-icon="lock-closed"
                revealable
                class="sb-auth-input"
            />
            <x-ui.error name="form.password" />
        </x-ui.field>

        <x-ui.field>
            <x-ui.label class="sb-auth-label">Confirm password</x-ui.label>
            <x-ui.input
                wire:model.live="form.password_confirmation"
                name="password_confirmation"
                type="password"
                autocomplete="new-password"
                left-icon="shield-check"
                revealable
                class="sb-auth-input"
            />
            <x-ui.error name="form.password_confirmation" />
        </x-ui.field>
    </div>

    <div class="space-y-3 pt-1">
        <x-ui.button
            type="submit"
            color="amber"
            icon="user-plus"
            class="sb-auth-primary-action w-full justify-center rounded-[1rem] text-sm font-semibold"
        >
            Create account
        </x-ui.button>

        <p class="text-center text-sm text-[#9c9284]">
            Already have a Screenbase profile?
            <a href="{{ route('login') }}" class="sb-auth-inline-link font-medium">Sign in</a>
        </p>

        <p class="sb-auth-note text-center">
            Build a clean public identity for ratings, curated lists, and deeper title discovery.
        </p>
    </div>
</form>
