@extends('layouts.admin')

@section('title', 'Manage Media Assets')

@section('breadcrumbs')
    <x-ui.breadcrumbs.item :href="route('admin.dashboard')">Admin</x-ui.breadcrumbs.item>
    <x-ui.breadcrumbs.item>Manage Media Assets</x-ui.breadcrumbs.item>
@endsection

@section('content')
    <section class="space-y-4">
        <div>
            <x-ui.heading level="h1" size="xl">Manage Media Assets</x-ui.heading>
            <x-ui.text class="mt-1 text-neutral-600 dark:text-neutral-300">
                Review posters, backdrops, gallery images, stills, and videos across the catalog.
            </x-ui.text>
        </div>

        <x-admin.catalog-write-disabled-panel
            :back-href="route('admin.dashboard')"
            back-label="Back to admin"
            heading="Media Asset Mutations Paused"
            description="Asset browsing stays available, but edit, upload, and delete mutations are paused while Screenbase is in catalog-only mode."
        >
            Open an asset to inspect its metadata, then use the upstream catalog synchronization workflow to make any changes.
        </x-admin.catalog-write-disabled-panel>

        <div class="grid gap-4">
            @forelse ($mediaAssets as $mediaAsset)
                <x-ui.card class="!max-w-none">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="overflow-hidden rounded-box border border-black/5 bg-neutral-100 dark:border-white/10 dark:bg-neutral-800">
                                @if ($mediaAsset->url && ! $mediaAsset->isVideo())
                                    <img
                                        src="{{ $mediaAsset->url }}"
                                        alt="{{ $mediaAsset->alt_text ?: $mediaAsset->adminAttachedLabel() }}"
                                        class="size-20 object-cover"
                                    >
                                @else
                                    <div class="flex size-20 items-center justify-center text-neutral-500 dark:text-neutral-400">
                                        <x-ui.icon :name="$mediaAsset->isVideo() ? 'play-circle' : 'photo'" class="size-8" />
                                    </div>
                                @endif
                            </div>

                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-ui.heading level="h3" size="md">{{ str($mediaAsset->kind->value)->headline() }}</x-ui.heading>
                                    @if ($mediaAsset->is_primary)
                                        <x-ui.badge color="amber" icon="star">Primary</x-ui.badge>
                                    @endif
                                </div>

                                <div class="text-sm text-neutral-500 dark:text-neutral-400">
                                    @if ($mediaAsset->adminAttachedEditUrl())
                                        <a href="{{ $mediaAsset->adminAttachedEditUrl() }}" class="font-medium hover:opacity-80">{{ $mediaAsset->adminAttachedLabel() }}</a>
                                    @else
                                        {{ $mediaAsset->adminAttachedLabel() }}
                                    @endif
                                    · {{ $mediaAsset->provider ?: 'Direct URL' }}
                                    @if ($mediaAsset->position !== null)
                                        · Position {{ $mediaAsset->position }}
                                    @endif
                                </div>

                                <div class="flex flex-wrap gap-2 text-xs text-neutral-500 dark:text-neutral-400">
                                    @if ($mediaAsset->width && $mediaAsset->height)
                                        <span>{{ number_format($mediaAsset->width) }} × {{ number_format($mediaAsset->height) }}</span>
                                    @endif
                                    @if ($mediaAsset->duration_seconds)
                                        <span>{{ number_format($mediaAsset->duration_seconds) }} sec</span>
                                    @endif
                                    @if ($mediaAsset->isUploadBacked())
                                        <span class="break-all">{{ $mediaAsset->storagePath() }}</span>
                                    @elseif ($mediaAsset->provider_key)
                                        <span class="break-all">{{ $mediaAsset->provider_key }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <x-ui.button as="a" :href="route('admin.media-assets.edit', $mediaAsset)" size="sm" variant="outline" icon="pencil-square">
                                Inspect
                            </x-ui.button>
                            <x-ui.badge variant="outline" color="neutral" icon="eye">
                                Read only
                            </x-ui.badge>
                        </div>
                    </div>
                </x-ui.card>
            @empty
                <x-ui.empty class="rounded-box border border-dashed border-black/10 bg-white dark:border-white/10 dark:bg-neutral-900">
                    <x-ui.empty.media>
                        <x-ui.icon name="photo" class="size-8 text-neutral-400 dark:text-neutral-500" />
                    </x-ui.empty.media>
                    <x-ui.heading level="h3">No media assets are available.</x-ui.heading>
                </x-ui.empty>
            @endforelse
        </div>

        <div>
            {{ $mediaAssets->links() }}
        </div>
    </section>
@endsection
