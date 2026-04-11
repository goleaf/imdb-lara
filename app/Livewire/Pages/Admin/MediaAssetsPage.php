<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminMediaAssetsIndexQueryAction;
use App\Actions\Admin\DeleteMediaAssetAction;
use App\Actions\Admin\SaveMediaAssetAction;
use App\Http\Requests\Admin\UpdateMediaAssetRequest;
use App\Livewire\Pages\Admin\Concerns\ResolvesAdminFormState;
use App\Livewire\Pages\Admin\Concerns\ValidatesFormRequests;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\LocalPerson;
use App\Models\LocalTitle;
use App\Models\MediaAsset;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;

class MediaAssetsPage extends Component
{
    use RendersPageView;
    use ResolvesAdminFormState;
    use ValidatesFormRequests;
    use WithFileUploads;

    public ?MediaAsset $mediaAsset = null;

    public string $kind = 'poster';

    public mixed $file = null;

    public ?string $url = null;

    public ?string $alt_text = null;

    public ?string $caption = null;

    public ?int $width = null;

    public ?int $height = null;

    public ?string $provider = null;

    public ?string $provider_key = null;

    public ?string $language = null;

    public ?int $duration_seconds = null;

    public ?string $metadata = null;

    public bool $is_primary = false;

    public ?int $position = null;

    public ?string $published_at = null;

    public function mount(?MediaAsset $mediaAsset = null): void
    {
        $this->mediaAsset = $mediaAsset;
        if ($mediaAsset instanceof MediaAsset) {
            $this->fillMediaAssetForm($mediaAsset);
        }
    }

    protected function renderMediaAssetsIndexPage(BuildAdminMediaAssetsIndexQueryAction $buildAdminMediaAssetsIndexQuery): View
    {
        $mediaAssets = $buildAdminMediaAssetsIndexQuery
            ->handle()
            ->simplePaginate(20)
            ->withQueryString();

        $this->hydrateAdminMediables($mediaAssets);

        return $this->renderPageView('admin.media-assets.index', [
            'mediaAssets' => $mediaAssets,
        ]);
    }

    protected function renderMediaAssetEditPage(): View
    {
        abort_unless($this->mediaAsset instanceof MediaAsset, 404);

        $loadedMediaAsset = $this->mediaAsset->load('mediable');
        $this->hydrateAdminMediableRelations(collect([$loadedMediaAsset]));
        $loadedMediaAsset->fill(Arr::except($this->mediaAssetPayload(), ['file']));

        if ($this->isCatalogOnlyApplication()) {
            return $this->renderPageView('admin.media-assets.edit', [
                'mediaAsset' => $loadedMediaAsset,
            ]);
        }

        return $this->renderPageView('admin.media-assets.edit', [
            'mediaAsset' => $loadedMediaAsset,
            'mediaAssetFormData' => $this->adminMediaAssetFormData($loadedMediaAsset),
        ]);
    }

    public function saveMediaAsset(SaveMediaAssetAction $saveMediaAsset): mixed
    {
        abort_unless($this->mediaAsset instanceof MediaAsset, 404);

        $validated = $this->validateWithFormRequest(UpdateMediaAssetRequest::class, $this->mediaAssetPayload(), [
            'mediaAsset' => $this->mediaAsset,
        ]);

        $savedMediaAsset = $saveMediaAsset->handle(
            $this->mediaAsset,
            $this->mediaAsset->mediable ?? $this->mediaAsset->mediable()->firstOrFail(),
            $validated,
        );

        $this->mediaAsset = $savedMediaAsset->load('mediable');
        $this->fillMediaAssetForm($this->mediaAsset);
        $this->resetValidation();
        session()->flash('status', 'Media asset updated.');

        return $this->redirectRoute('admin.media-assets.edit', $savedMediaAsset);
    }

    public function deleteMediaAsset(DeleteMediaAssetAction $deleteMediaAsset): mixed
    {
        abort_unless($this->mediaAsset instanceof MediaAsset, 404);

        $this->authorize('delete', $this->mediaAsset);
        $redirectUrl = $this->mediaAsset->adminAttachedEditUrl() ?? route('admin.media-assets.index');
        $deleteMediaAsset->handle($this->mediaAsset);
        session()->flash('status', 'Media asset deleted.');

        return $this->redirect($redirectUrl);
    }

    private function fillMediaAssetForm(MediaAsset $mediaAsset): void
    {
        $this->kind = (string) ($mediaAsset->kind?->value ?? 'poster');
        $this->file = null;
        $this->url = $mediaAsset->isUploadBacked() ? null : $mediaAsset->url;
        $this->alt_text = $mediaAsset->alt_text;
        $this->caption = $mediaAsset->caption;
        $this->width = $mediaAsset->width;
        $this->height = $mediaAsset->height;
        $this->provider = $mediaAsset->provider;
        $this->provider_key = $mediaAsset->provider_key;
        $this->language = $mediaAsset->language;
        $this->duration_seconds = $mediaAsset->duration_seconds;
        $this->metadata = $mediaAsset->metadata ? json_encode($mediaAsset->metadata, JSON_PRETTY_PRINT) : null;
        $this->is_primary = (bool) $mediaAsset->is_primary;
        $this->position = $mediaAsset->position;
        $this->published_at = $mediaAsset->published_at?->format('Y-m-d\TH:i');
    }

    /**
     * @return array<string, mixed>
     */
    private function mediaAssetPayload(): array
    {
        return [
            'kind' => $this->kind,
            'file' => $this->file,
            'url' => $this->url,
            'alt_text' => $this->alt_text,
            'caption' => $this->caption,
            'width' => $this->width,
            'height' => $this->height,
            'provider' => $this->provider,
            'provider_key' => $this->provider_key,
            'language' => $this->language,
            'duration_seconds' => $this->duration_seconds,
            'metadata' => $this->metadata,
            'is_primary' => $this->is_primary,
            'position' => $this->position,
            'published_at' => $this->published_at,
        ];
    }

    private function hydrateAdminMediables(AbstractPaginator $mediaAssets): void
    {
        /** @var Collection<int, MediaAsset> $items */
        $items = $mediaAssets->getCollection();

        $this->hydrateAdminMediableRelations($items);
    }

    /**
     * @param  Collection<int, MediaAsset>  $mediaAssets
     */
    private function hydrateAdminMediableRelations(Collection $mediaAssets): void
    {
        $titleAssets = $mediaAssets->where('mediable_type', Title::class);
        $personAssets = $mediaAssets->where('mediable_type', Person::class);

        $titles = LocalTitle::query()
            ->select(['id', 'name', 'slug'])
            ->whereKey($titleAssets->pluck('mediable_id')->filter()->all())
            ->get()
            ->keyBy('id');

        $people = LocalPerson::query()
            ->select(['id', 'name', 'slug'])
            ->whereKey($personAssets->pluck('mediable_id')->filter()->all())
            ->get()
            ->keyBy('id');

        $mediaAssets->each(function (MediaAsset $mediaAsset) use ($titles, $people): void {
            $resolvedMediable = match ($mediaAsset->mediable_type) {
                Title::class => $titles->get($mediaAsset->mediable_id),
                Person::class => $people->get($mediaAsset->mediable_id),
                default => $mediaAsset->mediable instanceof Model ? $mediaAsset->mediable : null,
            };

            $mediaAsset->setRelation('adminMediable', $resolvedMediable);
        });
    }
}
