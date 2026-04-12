<?php

use App\Actions\Lists\EnsureWatchlistAction;
use App\Enums\ListVisibility;
use App\Enums\ProfileVisibility;
use App\Models\User;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    protected EnsureWatchlistAction $ensureWatchlist;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:1200')]
    public string $bio = '';

    #[Validate('nullable|string|max:2048')]
    public ?string $avatarPath = null;

    #[Validate('required|in:public,private')]
    public string $profileVisibility = ProfileVisibility::Public->value;

    #[Validate('required|boolean')]
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

        $validated = $this->validate();

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

<div>
    @placeholder
        <x-ui.card class="!max-w-none">
            <div class="space-y-4">
                <div class="space-y-2">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <x-ui.heading level="h2" size="lg">Profile settings</x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Loading your profile controls and visibility preferences.
                            </x-ui.text>
                        </div>

                        <x-ui.skeleton class="h-9 w-28 rounded-full" />
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @foreach (range(1, 3) as $index)
                            <x-ui.badge
                                variant="outline"
                                color="neutral"
                                icon="ellipsis-horizontal-circle"
                                wire:key="profile-settings-placeholder-badge-{{ $index }}"
                            >
                                Loading
                            </x-ui.badge>
                        @endforeach
                    </div>
                </div>

                <div class="grid gap-4">
                    @foreach (range(1, 3) as $index)
                        <x-ui.field wire:key="profile-settings-placeholder-field-{{ $index }}">
                            <x-ui.label>Loading</x-ui.label>
                            <x-ui.skeleton class="h-10 w-full rounded-box" />
                        </x-ui.field>
                    @endforeach

                    <x-ui.field>
                        <x-ui.label>Profile visibility</x-ui.label>
                        <x-ui.skeleton class="h-10 w-full rounded-box" />
                    </x-ui.field>

                    <div class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                        <div class="space-y-2">
                            <x-ui.skeleton.text class="w-1/2" />
                            <x-ui.skeleton.text class="w-full" />
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <x-ui.skeleton.text class="w-2/3" />

                    <div class="flex flex-wrap gap-3">
                        <x-ui.skeleton class="h-10 w-40 rounded-full" />
                        <x-ui.skeleton class="h-10 w-32 rounded-full" />
                    </div>
                </div>
            </div>
        </x-ui.card>
    @endplaceholder

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

            <x-ui.alerts wire:show="statusMessage" variant="success" icon="check-circle">
                <x-ui.alerts.description>
                    <span wire:text="statusMessage">{{ $statusMessage }}</span>
                </x-ui.alerts.description>
            </x-ui.alerts>

            <div class="grid gap-4">
                <x-ui.field>
                    <x-ui.label>Display name</x-ui.label>
                    <x-ui.input wire:model.live.blur="name" name="name" placeholder="Ari Lane" left-icon="user" />
                    <x-ui.error name="name" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Avatar URL or storage path</x-ui.label>
                    <x-ui.input wire:model.live.blur="avatarPath" name="avatarPath" placeholder="https://images.example.com/avatar.jpg" left-icon="photo" />
                    <x-ui.error name="avatarPath" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Bio</x-ui.label>
                    <x-ui.textarea wire:model.live.blur="bio" name="bio" rows="4" placeholder="What kind of curator are you?" />
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

                <x-ui.checkbox
                    wire:model.live="showRatingsOnProfile"
                    variant="cards"
                    label="Show ratings on your public profile"
                    description="Reviews, lists, and watchlist visibility continue to follow their own privacy rules."
                />
                <x-ui.error name="showRatingsOnProfile" />
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <x-ui.text class="text-sm text-neutral-500 dark:text-neutral-400">
                    Watchlist visibility is managed on the watchlist page so there is only one source of truth.
                </x-ui.text>

                <div class="flex flex-wrap gap-3">
                    <x-ui.link :href="route('account.watchlist')" variant="ghost">Manage watchlist privacy</x-ui.link>
                    <x-ui.button type="submit" icon="check" wire:target="save">
                        Save settings
                    </x-ui.button>
                </div>
            </div>
        </form>
    </x-ui.card>
</div>
