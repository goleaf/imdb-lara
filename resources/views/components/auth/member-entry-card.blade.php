@props([
    'activeTab' => 'login',
    'copy',
    'dividerLabel',
    'kicker',
    'note' => null,
    'sectionTitle' => 'Screenbase',
    'title',
])

<section class="w-full max-w-[32rem]">
    <div class="sb-auth-card rounded-[1.75rem] px-5 py-6 sm:px-7 sm:py-8">
        <div class="space-y-7">
            <div class="space-y-5">
                <x-ui.brand
                    :href="route('public.home')"
                    :name="$sectionTitle"
                    :description="$kicker"
                    nameClass="sb-auth-section-title"
                    descriptionClass="sb-auth-kicker"
                    class="justify-start text-[#f7f1e8]"
                >
                    <x-slot:logo>
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-full border border-[#d6b574]/25 bg-white/4 text-[0.72rem] font-semibold uppercase tracking-[0.3em] text-[#d6b574]">
                            SB
                        </span>
                    </x-slot:logo>
                </x-ui.brand>

                <div class="space-y-3">
                    <h1 class="sb-auth-title">{{ $title }}</h1>
                    <p class="sb-auth-copy">{{ $copy }}</p>
                </div>
            </div>

            <nav class="sb-auth-toggle" aria-label="Authentication pages">
                <x-ui.button
                    as="a"
                    :href="route('login')"
                    variant="none"
                    class="sb-auth-toggle-link"
                    :data-active="$activeTab === 'login' ? 'true' : 'false'"
                >
                    Sign in
                </x-ui.button>

                <x-ui.button
                    as="a"
                    :href="route('register')"
                    variant="none"
                    class="sb-auth-toggle-link"
                    :data-active="$activeTab === 'register' ? 'true' : 'false'"
                >
                    Sign up
                </x-ui.button>
            </nav>

            <div class="space-y-3">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-ui.button
                        type="button"
                        variant="none"
                        disabled
                        class="sb-auth-social-option !h-auto !w-full !justify-start !rounded-[1rem] !px-4 !py-4 text-left"
                    >
                        <span class="sb-auth-social-button-badge">A</span>
                        <span>
                            <span class="block text-sm font-semibold text-[#f4eee5]">Continue with Apple</span>
                            <span class="mt-0.5 block text-xs text-[#8f877a]">Available soon</span>
                        </span>
                    </x-ui.button>

                    <x-ui.button
                        type="button"
                        variant="none"
                        disabled
                        class="sb-auth-social-option !h-auto !w-full !justify-start !rounded-[1rem] !px-4 !py-4 text-left"
                    >
                        <span class="sb-auth-social-button-badge">G</span>
                        <span>
                            <span class="block text-sm font-semibold text-[#f4eee5]">Continue with Google</span>
                            <span class="mt-0.5 block text-xs text-[#8f877a]">Available soon</span>
                        </span>
                    </x-ui.button>
                </div>

                @if (filled($note))
                    <p class="sb-auth-note">{{ $note }}</p>
                @endif
            </div>

            <div class="sb-auth-divider">
                <span>{{ $dividerLabel }}</span>
            </div>

            {{ $slot }}
        </div>
    </div>
</section>
