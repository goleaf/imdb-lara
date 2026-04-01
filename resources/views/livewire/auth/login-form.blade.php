<form wire:submit="login" class="space-y-4">
    <x-ui.field>
        <x-ui.label>Email</x-ui.label>
        <x-ui.input wire:model.live="form.email" name="email" type="email" autocomplete="email" />
        <x-ui.error name="form.email" />
    </x-ui.field>

    <x-ui.field>
        <x-ui.label>Password</x-ui.label>
        <x-ui.input
            wire:model.live="form.password"
            name="password"
            type="password"
            autocomplete="current-password"
            revealable
        />
        <x-ui.error name="form.password" />
    </x-ui.field>

    <label class="flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-300">
        <input wire:model.live="form.remember" type="checkbox" class="rounded border-black/20 dark:border-white/20">
        <span>Remember this device</span>
    </label>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-ui.button type="submit" icon="arrow-right-end-on-rectangle">
            Sign in
        </x-ui.button>

        <x-ui.link :href="route('register')" variant="ghost">
            Need an account?
        </x-ui.link>
    </div>
</form>
