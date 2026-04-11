@props([
    'groups' => [],
    'modalId' => 'title-media-lightbox',
])

<section
    {{ $attributes }}
    x-data="{
        lightboxModalId: @js($modalId),
        lightboxGroups: @js($groups),
        activeGroupKey: null,
        activeIndex: 0,
        activeGroup() {
            return this.activeGroupKey ? (this.lightboxGroups[this.activeGroupKey] ?? null) : null;
        },
        activeItems() {
            return this.activeGroup()?.items ?? [];
        },
        activeItem() {
            return this.activeItems()[this.activeIndex] ?? null;
        },
        activeItemOrientation() {
            const item = this.activeItem();

            if (!item) {
                return 'landscape';
            }

            return (item.height ?? 0) > (item.width ?? 0) ? 'portrait' : 'landscape';
        },
        openLightbox(groupKey, index = 0) {
            const group = this.lightboxGroups[groupKey];

            if (!group || !Array.isArray(group.items) || group.items.length === 0) {
                return;
            }

            this.activeGroupKey = groupKey;
            this.activeIndex = Math.max(0, Math.min(index, group.items.length - 1));
            this.$modal.open(this.lightboxModalId);
            this.syncActiveThumb();
        },
        openLightboxByUrl(url) {
            if (!url) {
                return;
            }

            for (const [groupKey, group] of Object.entries(this.lightboxGroups)) {
                const index = group.items.findIndex((item) => item.url === url);

                if (index !== -1) {
                    this.openLightbox(groupKey, index);

                    return;
                }
            }
        },
        selectLightbox(index) {
            if (index < 0 || index >= this.activeItems().length) {
                return;
            }

            this.activeIndex = index;
            this.syncActiveThumb();
        },
        previousLightbox() {
            if (this.activeIndex <= 0) {
                return;
            }

            this.activeIndex -= 1;
            this.syncActiveThumb();
        },
        nextLightbox() {
            if (this.activeIndex >= this.activeItems().length - 1) {
                return;
            }

            this.activeIndex += 1;
            this.syncActiveThumb();
        },
        closeLightbox() {
            this.$modal.close(this.lightboxModalId);
            this.resetLightbox();
        },
        resetLightbox() {
            this.activeGroupKey = null;
            this.activeIndex = 0;
        },
        syncActiveThumb() {
            this.$nextTick(() => {
                const thumb = this.$refs.lightboxThumbRail?.querySelector(`[data-lightbox-thumb-index='${this.activeIndex}']`);

                thumb?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            });
        },
    }"
    x-on:keydown.left.window.prevent="if ($modal.isOpen(lightboxModalId)) { previousLightbox(); }"
    x-on:keydown.right.window.prevent="if ($modal.isOpen(lightboxModalId)) { nextLightbox(); }"
    x-on:modal-closed.window="if ($event.detail?.id === lightboxModalId) { resetLightbox(); }"
    x-on:modal-opened.window="if ($event.detail?.id === lightboxModalId) { syncActiveThumb(); }"
>
    {{ $slot }}

    <x-ui.modal
        :id="$modalId"
        bare
        width="screen"
        backdrop="dark"
        animation="fade"
    >
        <div class="sb-media-lightbox-shell" data-slot="title-media-lightbox">
            <div class="sb-media-lightbox-topbar">
                <div class="min-w-0 space-y-2">
                    <div class="sb-media-kicker" x-text="activeGroup()?.label ?? 'Image lightbox'"></div>
                    <div class="sb-media-lightbox-title" x-show="activeItem()?.caption" x-text="activeItem()?.caption ?? ''"></div>
                    <div class="sb-media-lightbox-meta">
                        <span x-text="`${activeIndex + 1} / ${activeItems().length}`"></span>
                        <template x-for="(metaItem, metaIndex) in (activeItem()?.meta ?? [])" :key="`${activeItem()?.id ?? 'media'}-${metaIndex}`">
                            <span x-text="metaItem"></span>
                        </template>
                    </div>
                </div>
            </div>

            <div class="sb-media-lightbox-stage">
                <button
                    type="button"
                    class="sb-media-lightbox-close sb-media-lightbox-close--corner"
                    x-on:click="closeLightbox()"
                >
                    <x-ui.icon name="x-mark" class="size-5" />
                    <span class="sr-only">Close lightbox</span>
                </button>

                <button
                    type="button"
                    class="sb-media-lightbox-nav"
                    x-bind:disabled="activeIndex === 0"
                    x-on:click="previousLightbox()"
                >
                    <x-ui.icon name="chevron-left" class="size-5" />
                    <span class="sr-only">Previous image</span>
                </button>

                <div class="sb-media-lightbox-frame">
                    <template x-if="activeItem()">
                        <img
                            x-bind:src="activeItem().url"
                            x-bind:alt="activeItem().altText"
                            class="sb-media-lightbox-image"
                            x-bind:class="activeItemOrientation() === 'portrait' ? 'sb-media-lightbox-image--portrait' : 'sb-media-lightbox-image--landscape'"
                        >
                    </template>
                </div>

                <button
                    type="button"
                    class="sb-media-lightbox-nav"
                    x-bind:disabled="activeIndex >= activeItems().length - 1"
                    x-on:click="nextLightbox()"
                >
                    <x-ui.icon name="chevron-right" class="size-5" />
                    <span class="sr-only">Next image</span>
                </button>
            </div>

            <div class="sb-media-lightbox-caption" x-show="activeItem()?.caption" x-text="activeItem()?.caption ?? ''"></div>

            <div class="sb-media-lightbox-thumbs" x-ref="lightboxThumbRail">
                <template x-for="(item, index) in activeItems()" :key="item.id">
                    <button
                        type="button"
                        class="sb-media-lightbox-thumb"
                        x-bind:class="{ 'sb-media-lightbox-thumb--active': index === activeIndex }"
                        x-bind:data-lightbox-thumb-index="index"
                        x-on:click="selectLightbox(index)"
                    >
                        <img x-bind:src="item.url" x-bind:alt="item.altText" class="sb-media-lightbox-thumb-image">
                    </button>
                </template>
            </div>
        </div>
    </x-ui.modal>
</section>
