<?php

use App\Actions\Lists\EnsureWatchlistAction;
use App\Enums\ListVisibility;
use App\Enums\ProfileVisibility;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component
{
    protected EnsureWatchlistAction $ensureWatchlist;

    public string $name = '';

    public string $bio = '';

    public ?string $avatarPath = null;

    public string $profileVisibility = ProfileVisibility::Public->value;

    public bool $showRatingsOnProfile = true;

    public string $watchlistVisibility = ListVisibility::Private->value;

    public bool $publicProfileIsLive = false;

    public ?string $statusMessage = null;

    public function boot(EnsureWatchlistAction $ensureWatchlist): void
    {
        $this->ensureWatchlist = $ensureWatchlist;
    }

    public function mount(): void
    {
        if (! auth()->check()) {
            return;
        }

        $this->syncState($this->currentUser());
    }

    public function save(): void
    {
        if (! auth()->check()) {
            $this->redirectRoute('login');

            return;
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1200'],
            'avatarPath' => ['nullable', 'string', 'max:2048'],
            'profileVisibility' => ['required', Rule::in([
                ProfileVisibility::Public->value,
                ProfileVisibility::Private->value,
            ])],
            'showRatingsOnProfile' => ['required', 'boolean'],
        ]);

        $user = $this->currentUser();
        $user->fill([
            'name' => $validated['name'],
            'bio' => blank($validated['bio']) ? null : $validated['bio'],
            'avatar_path' => blank($validated['avatarPath']) ? null : $validated['avatarPath'],
            'profile_visibility' => $validated['profileVisibility'],
            'show_ratings_on_profile' => $validated['showRatingsOnProfile'],
        ])->save();

        $this->statusMessage = 'Profile settings updated.';
        $this->syncState($user->fresh());
    }

    private function currentUser(): User
    {
        /** @var User */
        return auth()->user();
    }

    private function syncState(User $user): void
    {
        $watchlist = $this->ensureWatchlist->handle($user);

        $this->name = $user->name;
        $this->bio = $user->bio ?? '';
        $this->avatarPath = $user->avatar_path;
        $this->profileVisibility = $user->profile_visibility->value;
        $this->showRatingsOnProfile = $user->show_ratings_on_profile;
        $this->watchlistVisibility = $watchlist->visibility->value;
        $this->publicProfileIsLive = $user->isProfileVisibleToPublic();
    }
};
?>

<x-ui.card class="!max-w-none">
    <form wire:submit="save" class="space-y-4">
        <div class="space-y-2">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <x-ui.heading level="h2" size="lg" class="inline-flex items-center gap-2">
                        <x-ui.icon name="user-circle" class="size-5 text-neutral-500 dark:text-neutral-400" />
                        <span>Profile settings</span>
                    </x-ui.heading>
                    <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                        Control whether your public profile is reachable while keeping watchlist privacy managed from the watchlist page.
                    </x-ui.text>
                </div>

                @if ($publicProfileIsLive)
                    <x-ui.link :href="route('public.users.show', auth()->user())" variant="ghost" iconAfter="arrow-up-right">
                        Open profile
                    </x-ui.link>
                @endif
            </div>

            <div class="flex flex-wrap gap-2">
                <x-ui.badge variant="outline" color="neutral" :icon="\App\Enums\ProfileVisibility::from($profileVisibility)->icon()">
                    Profile {{ \App\Enums\ProfileVisibility::from($profileVisibility)->label() }}
                </x-ui.badge>
                <x-ui.badge variant="outline" color="neutral" :icon="\App\Enums\ListVisibility::from($watchlistVisibility)->icon()">
                    Watchlist {{ \App\Enums\ListVisibility::from($watchlistVisibility)->label() }}
                </x-ui.badge>
                <x-ui.badge variant="outline" color="neutral" icon="star">
                    Ratings {{ $showRatingsOnProfile ? 'Visible' : 'Hidden' }}
                </x-ui.badge>
            </div>
        </div>

        @if ($statusMessage)
            <x-ui.alerts variant="success" icon="check-circle">
                <x-ui.alerts.description>{{ $statusMessage }}</x-ui.alerts.description>
            </x-ui.alerts>
        @endif

        <div class="grid gap-4">
            <x-ui.field>
                <x-ui.label>Display name</x-ui.label>
                <x-ui.input wire:model.live="name" name="name" placeholder="Ari Lane" left-icon="user" />
                <x-ui.error name="name" />
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Avatar URL or storage path</x-ui.label>
                <x-ui.input wire:model.live="avatarPath" name="avatarPath" placeholder="https://images.example.com/avatar.jpg" left-icon="photo" />
                <x-ui.error name="avatarPath" />
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Bio</x-ui.label>
                <x-ui.textarea wire:model.live="bio" name="bio" rows="4" placeholder="What kind of curator are you?" />
                <x-ui.error name="bio" />
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Profile visibility</x-ui.label>
                <x-ui.combobox
                    wire:model.live="profileVisibility"
                    class="w-full"
                    placeholder="Select visibility"
                    :invalid="$errors->has('profileVisibility')"
                >
                    <x-ui.combobox.option value="public" icon="globe-alt">Public</x-ui.combobox.option>
                    <x-ui.combobox.option value="private" icon="lock-closed">Private</x-ui.combobox.option>
                </x-ui.combobox>
                <x-ui.error name="profileVisibility" />
            </x-ui.field>

            <label class="flex items-start gap-3 rounded-box border border-black/5 px-4 py-3 text-sm dark:border-white/10">
                <input wire:model.live="showRatingsOnProfile" type="checkbox" class="mt-0.5 rounded border-black/20 dark:border-white/20">
                <span class="space-y-1">
                    <span class="block font-medium">Show ratings on your public profile</span>
                    <span class="block text-neutral-500 dark:text-neutral-400">
                        Reviews, lists, and watchlist visibility continue to follow their own privacy rules.
                    </span>
                </span>
            </label>
            <x-ui.error name="showRatingsOnProfile" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <x-ui.text class="text-sm text-neutral-500 dark:text-neutral-400">
                Watchlist visibility is managed on the watchlist page so there is only one source of truth.
            </x-ui.text>

            <div class="flex flex-wrap gap-3">
                <x-ui.link :href="route('account.watchlist')" variant="ghost">Manage watchlist privacy</x-ui.link>
                <x-ui.button type="submit" icon="check">
                    Save settings
                </x-ui.button>
            </div>
        </div>
    </form>
</x-ui.card>
