@php
    $currentYear = now()->year;
    $footerData = $footerData ?? [];
@endphp

<footer {{ $attributes->class('mt-10 w-full border-t border-white/8 bg-[radial-gradient(circle_at_top_right,rgba(251,191,36,0.12),transparent_20%),radial-gradient(circle_at_bottom_left,rgba(14,165,233,0.12),transparent_24%),linear-gradient(180deg,rgba(18,18,18,0.98),rgba(6,6,6,1))]') }} data-slot="site-footer">
    <div class="mx-auto w-full max-w-7xl px-4 py-8 md:px-6 md:py-10">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($footerData['sections'] as $section)
                <section class="rounded-[1.45rem] border border-white/10 bg-white/[0.03] p-5">
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <div class="inline-flex items-center gap-2 text-[0.62rem] font-semibold uppercase tracking-[0.22em] text-[#b7aa96]">
                                <x-ui.icon name="chevron-right" class="size-4 text-[#d6b574]" />
                                <span>{{ $section['heading'] }}</span>
                            </div>

                            <x-ui.text class="text-sm leading-6 text-[#bfb3a4]">
                                {{ $section['description'] }}
                            </x-ui.text>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-1">
                            @foreach ($section['links'] as $link)
                                <x-ui.link
                                    :href="$link['href']"
                                    variant="soft"
                                    :primary="false"
                                    :icon="$link['icon']"
                                    class="rounded-full border border-white/8 bg-white/[0.02] px-3 py-2 text-sm !text-[#f1ebdf] transition hover:!text-white hover:border-white/16 hover:bg-white/[0.05] no-underline"
                                >
                                    {{ $link['label'] }}
                                </x-ui.link>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endforeach
        </div>

        <div class="mt-5 border-t border-white/8 pt-4 text-sm text-neutral-400">
            <x-ui.text class="w-full text-sm leading-6 text-[#b2a796]">
                © {{ $currentYear }} Screenbase. {{ $footerData['legalCopy'] }}
            </x-ui.text>
        </div>
    </div>
</footer>
