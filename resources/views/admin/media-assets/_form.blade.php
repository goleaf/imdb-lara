@if ($mediaAsset->exists)
    <div class="rounded-box border border-black/10 bg-neutral-50 p-4 dark:border-white/10 dark:bg-neutral-900/60">
        <div class="grid gap-4 md:grid-cols-[12rem_minmax(0,1fr)]">
            <div class="overflow-hidden rounded-box border border-black/5 bg-white dark:border-white/10 dark:bg-neutral-950">
                @if ($mediaAsset->url)
                    @if ($mediaAsset->isVideo())
                        <div class="flex aspect-video items-center justify-center text-neutral-500 dark:text-neutral-400">
                            <x-ui.icon name="play-circle" class="size-12" />
                        </div>
                    @else
                        <img
                            src="{{ $mediaAsset->url }}"
                            alt="{{ $mediaAsset->alt_text ?: 'Current media asset' }}"
                            class="aspect-[2/3] w-full object-cover"
                        >
                    @endif
                @else
                    <div class="flex aspect-[2/3] items-center justify-center text-neutral-500 dark:text-neutral-400">
                        <x-ui.icon name="photo" class="size-10" />
                    </div>
                @endif
            </div>

            <div class="space-y-3">
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.badge variant="outline" :icon="$mediaAsset->isVideo() ? 'play-circle' : 'photo'">{{ $mediaAsset->kindLabel() }}</x-ui.badge>
                    @if ($mediaAsset->is_primary)
                        <x-ui.badge color="amber" icon="star">Primary</x-ui.badge>
                    @endif
                    @if ($mediaAsset->provider)
                        <x-ui.badge variant="outline" color="neutral" icon="server">
                            {{ str($mediaAsset->provider)->headline() }}
                        </x-ui.badge>
                    @endif
                </div>

                <dl class="grid gap-2 text-sm text-neutral-600 dark:text-neutral-300">
                    @if ($mediaAsset->mediable)
                        <div>
                            <dt class="font-medium text-neutral-900 dark:text-white">Attached to</dt>
                            <dd>
                                @if ($mediaAsset->adminAttachedEditUrl())
                                    <a href="{{ $mediaAsset->adminAttachedEditUrl() }}" class="hover:opacity-80">
                                        {{ $mediaAsset->adminAttachedLabel() }}
                                    </a>
                                @else
                                    {{ $mediaAsset->adminAttachedLabel() }}
                                @endif
                            </dd>
                        </div>
                    @endif

                    @if ($mediaAsset->isUploadBacked())
                        <div>
                            <dt class="font-medium text-neutral-900 dark:text-white">Stored file</dt>
                            <dd class="break-all">{{ $mediaAsset->storagePath() }}</dd>
                        </div>
                    @endif

                    @if ($mediaAsset->width && $mediaAsset->height)
                        <div>
                            <dt class="font-medium text-neutral-900 dark:text-white">Dimensions</dt>
                            <dd>{{ number_format($mediaAsset->width) }} × {{ number_format($mediaAsset->height) }}</dd>
                        </div>
                    @endif

                    @if ($mediaAsset->duration_seconds)
                        <div>
                            <dt class="font-medium text-neutral-900 dark:text-white">Duration</dt>
                            <dd>{{ number_format($mediaAsset->duration_seconds) }} seconds</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
@endif

<div class="grid gap-4 lg:grid-cols-2">
    <x-ui.field>
        <x-ui.label>Kind</x-ui.label>
        <select wire:model.live="{{ $fieldStatePath('kind') }}" name="{{ $fieldName('kind') }}" class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15">
            @foreach ($allowedMediaKinds as $mediaKind)
                <option value="{{ $mediaKind->value }}" @selected(old('kind', $mediaAsset->kind?->value) === $mediaKind->value)>
                    {{ $mediaKind->label() }}
                </option>
            @endforeach
        </select>
        <x-ui.error name="kind" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Upload image</x-ui.label>
        <x-ui.input wire:model="{{ $fieldStatePath('file') }}" name="{{ $fieldName('file') }}" type="file" accept="image/*" />
        <x-ui.text class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
            Use uploads for posters, backdrops, stills, galleries, and headshots.
        </x-ui.text>
        <x-ui.error name="file" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>URL</x-ui.label>
        <x-ui.input wire:model.defer="{{ $fieldStatePath('url') }}" name="{{ $fieldName('url') }}" type="url" :value="old('url', $mediaAsset->url)" />
        <x-ui.text class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
            @if ($allowedMediaKindsIncludeVideo)
                Required for trailers, clips, and featurettes. Optional for image assets when you upload a file.
            @else
                Optional for externally hosted images. Uploads still populate local storage metadata automatically.
            @endif
        </x-ui.text>
        <x-ui.error name="url" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Alt text</x-ui.label>
        <x-ui.input wire:model.defer="{{ $fieldStatePath('alt_text') }}" name="{{ $fieldName('alt_text') }}" :value="old('alt_text', $mediaAsset->alt_text)" />
        <x-ui.error name="alt_text" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Provider</x-ui.label>
        <x-ui.input wire:model.defer="{{ $fieldStatePath('provider') }}" name="{{ $fieldName('provider') }}" :value="old('provider', $mediaAsset->provider)" />
        <x-ui.text class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
            @if ($allowedMediaKindsIncludeVideo)
                Leave blank for uploads. Use this for external sources like YouTube, Vimeo, or internal video providers.
            @else
                Leave blank for uploads. Use this only when you need to label a remote image source.
            @endif
        </x-ui.text>
        <x-ui.error name="provider" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Provider key</x-ui.label>
        <x-ui.input wire:model.defer="{{ $fieldStatePath('provider_key') }}" name="{{ $fieldName('provider_key') }}" :value="old('provider_key', $mediaAsset->provider_key)" />
        <x-ui.error name="provider_key" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Language</x-ui.label>
        <x-ui.input wire:model.defer="{{ $fieldStatePath('language') }}" name="{{ $fieldName('language') }}" :value="old('language', $mediaAsset->language)" />
        <x-ui.error name="language" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Width</x-ui.label>
        <x-ui.input wire:model.defer="{{ $fieldStatePath('width') }}" name="{{ $fieldName('width') }}" type="number" min="1" :value="old('width', $mediaAsset->width)" />
        <x-ui.error name="width" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Height</x-ui.label>
        <x-ui.input wire:model.defer="{{ $fieldStatePath('height') }}" name="{{ $fieldName('height') }}" type="number" min="1" :value="old('height', $mediaAsset->height)" />
        <x-ui.error name="height" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Duration (seconds)</x-ui.label>
        <x-ui.input wire:model.defer="{{ $fieldStatePath('duration_seconds') }}" name="{{ $fieldName('duration_seconds') }}" type="number" min="1" :value="old('duration_seconds', $mediaAsset->duration_seconds)" />
        @if ($selectedKindIsImage)
            <x-ui.text class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
                Leave empty for still images. Video kinds keep using remote metadata.
            </x-ui.text>
        @endif
        <x-ui.error name="duration_seconds" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Position</x-ui.label>
        <x-ui.input wire:model.defer="{{ $fieldStatePath('position') }}" name="{{ $fieldName('position') }}" type="number" min="0" :value="old('position', $mediaAsset->position)" />
        <x-ui.error name="position" />
    </x-ui.field>
    <x-ui.field>
        <x-ui.label>Published at</x-ui.label>
        <x-ui.input wire:model.defer="{{ $fieldStatePath('published_at') }}" name="{{ $fieldName('published_at') }}" type="datetime-local" :value="old('published_at', $mediaAsset->published_at?->format('Y-m-d\\TH:i'))" />
        <x-ui.error name="published_at" />
    </x-ui.field>
    <div class="flex items-center gap-2 text-sm lg:self-end">
        <x-ui.checkbox wire:model="{{ $fieldStatePath('is_primary') }}" name="{{ $fieldName('is_primary') }}" value="1" label="Primary asset" />
    </div>
</div>

<x-ui.field>
    <x-ui.label>Caption</x-ui.label>
    <x-ui.textarea wire:model.defer="{{ $fieldStatePath('caption') }}" name="{{ $fieldName('caption') }}" rows="3">{{ old('caption', $mediaAsset->caption) }}</x-ui.textarea>
    <x-ui.error name="caption" />
</x-ui.field>

<x-ui.field>
    <x-ui.label>Metadata JSON</x-ui.label>
    <x-ui.textarea wire:model.defer="{{ $fieldStatePath('metadata') }}" name="{{ $fieldName('metadata') }}" rows="5">{{ old('metadata', $mediaAsset->metadata ? json_encode($mediaAsset->metadata, JSON_PRETTY_PRINT) : null) }}</x-ui.textarea>
    <x-ui.text class="mt-1 text-xs text-neutral-500 dark:text-neutral-400">
        Upload-backed assets automatically persist storage metadata here. Custom metadata is merged rather than discarded.
    </x-ui.text>
    <x-ui.error name="metadata" />
</x-ui.field>
