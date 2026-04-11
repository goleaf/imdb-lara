<?php

namespace App\Actions\Catalog;

use App\Models\CatalogMediaAsset;
use Illuminate\Support\Collection;

class BuildCatalogMediaLightboxGroupAction
{
    /**
     * @param  Collection<int, CatalogMediaAsset>  $assets
     * @return array{
     *     label: string,
     *     items: list<array{
     *         id: string,
     *         url: string,
     *         altText: string,
     *         caption: string,
     *         meta: list<string>,
     *         width: int|null,
     *         height: int|null
     *     }>
     * }
     */
    public function handle(string $label, Collection $assets): array
    {
        return [
            'label' => $label,
            'items' => $assets
                ->map(fn (CatalogMediaAsset $asset): array => $this->serializeAsset($asset))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{
     *     id: string,
     *     url: string,
     *     altText: string,
     *     caption: string,
     *     meta: list<string>,
     *     width: int|null,
     *     height: int|null
     * }
     */
    private function serializeAsset(CatalogMediaAsset $asset): array
    {
        $meta = array_values(array_filter([
            $asset->is_primary ? 'Primary' : null,
            $asset->width && $asset->height ? sprintf('%d × %d', $asset->width, $asset->height) : null,
        ]));

        return [
            'id' => $asset->stableIdentifier(),
            'url' => (string) $asset->url,
            'altText' => $asset->accessibleAltText(),
            'caption' => $asset->meaningfulCaption(),
            'meta' => $meta,
            'width' => $asset->width,
            'height' => $asset->height,
        ];
    }
}
