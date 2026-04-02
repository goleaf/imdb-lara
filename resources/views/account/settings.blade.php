@extends('layouts.account')

@section('title', 'Profile Settings')
@section('meta_description', 'Update your Screenbase profile, public visibility, and curator identity settings.')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('public.home')">Home</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item :href="route('account.dashboard')">Dashboard</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Profile Settings</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-6">
        <div class="space-y-2">
            <x-ui.heading level="h1" size="xl">Profile Settings</x-ui.heading>
            <x-ui.text class="max-w-3xl text-neutral-600 dark:text-neutral-300">
                Manage your public identity, curator bio, and rating visibility without leaving the account area.
            </x-ui.text>
        </div>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
            <livewire:account.profile-settings-panel />

            <div class="space-y-4">
                <x-ui.card class="!max-w-none">
                    <div class="space-y-4">
                        <div>
                            <x-ui.heading level="h2" size="lg" class="inline-flex items-center gap-2">
                                <x-ui.icon name="identification" class="size-5 text-neutral-500 dark:text-neutral-400" />
                                <span>Current identity</span>
                            </x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Your public profile becomes reachable whenever profile visibility is set to public.
                            </x-ui.text>
                        </div>

                        <div class="space-y-3">
                            <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                                <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Username</div>
                                <div class="mt-2 text-lg font-semibold">{{ '@'.$user->username }}</div>
                            </div>

                            <div class="rounded-box border border-black/5 p-4 dark:border-white/10">
                                <div class="text-xs uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">Member since</div>
                                <div class="mt-2 text-lg font-semibold">{{ $user->created_at->format('F Y') }}</div>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <x-ui.link :href="route('account.dashboard')" variant="ghost" iconAfter="arrow-right">
                                Back to dashboard
                            </x-ui.link>
                            <x-ui.link :href="route('account.watchlist')" variant="ghost" iconAfter="arrow-right">
                                Watchlist privacy
                            </x-ui.link>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card class="!max-w-none">
                    <div class="space-y-3">
                        <div>
                            <x-ui.heading level="h2" size="lg" class="inline-flex items-center gap-2">
                                <x-ui.icon name="information-circle" class="size-5 text-neutral-500 dark:text-neutral-400" />
                                <span>Visibility rules</span>
                            </x-ui.heading>
                            <x-ui.text class="mt-1 text-sm text-neutral-600 dark:text-neutral-300">
                                Ratings visibility, watchlist privacy, and public list visibility are controlled separately.
                            </x-ui.text>
                        </div>

                        <ul class="space-y-3 text-sm text-neutral-600 dark:text-neutral-300">
                            <li class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                Public profile visibility controls whether your profile can be visited directly.
                            </li>
                            <li class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                Ratings can stay private even if your profile is public.
                            </li>
                            <li class="rounded-box border border-black/5 px-4 py-3 dark:border-white/10">
                                Watchlist sharing remains managed on the watchlist page so the privacy state stays in one place.
                            </li>
                        </ul>
                    </div>
                </x-ui.card>
            </div>
        </section>
    </section>
@endsection
