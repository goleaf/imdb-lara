<form wire:submit="login" class="space-y-5">
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

    <x-ui.field>
        <x-ui.label class="sb-auth-label">Password</x-ui.label>
        <x-ui.input
            wire:model.live="form.password"
            name="password"
            type="password"
            autocomplete="current-password"
            left-icon="lock-closed"
            revealable
            class="sb-auth-input"
        />
        <x-ui.error name="form.password" />
    </x-ui.field>

    <x-ui.checkbox
        wire:model.live="form.remember"
        name="remember"
        label="Remember this device"
        class="sb-auth-remember"
        x-on:click="toggle()"
    />

    <div class="space-y-3 pt-1">
        <x-ui.button
            type="submit"
            color="amber"
            icon="arrow-right-end-on-rectangle"
            class="sb-auth-primary-action w-full justify-center rounded-[1rem] text-sm font-semibold"
        >
            Sign in
        </x-ui.button>

        <p class="text-center text-sm text-[#9c9284]">
            New to Screenbase?
            <x-ui.link
                :href="route('register')"
                variant="soft"
                :primary="false"
                class="sb-auth-inline-link font-medium"
            >
                Create account
            </x-ui.link>
        </p>

        <p class="sb-auth-note text-center">
            Trusted access for private watchlists, ratings, and moderated reviews.
        </p>
    </div>
</form>
