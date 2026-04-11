<?php

namespace App\Livewire\Pages\Admin;

use App\Actions\Admin\BuildAdminTitlesIndexQueryAction;
use App\Actions\Admin\DeleteTitleAction;
use App\Actions\Admin\SaveMediaAssetAction;
use App\Actions\Admin\SaveSeasonAction;
use App\Actions\Admin\StoreTitleAction;
use App\Actions\Admin\UpdateTitleAction;
use App\Enums\MediaKind;
use App\Enums\TitleType;
use App\Http\Requests\Admin\StoreMediaAssetRequest;
use App\Http\Requests\Admin\StoreSeasonRequest;
use App\Http\Requests\Admin\StoreTitleRequest;
use App\Http\Requests\Admin\UpdateTitleRequest;
use App\Livewire\Pages\Admin\Concerns\InteractsWithCatalogTitleState;
use App\Livewire\Pages\Admin\Concerns\ResolvesAdminFormState;
use App\Livewire\Pages\Admin\Concerns\ValidatesFormRequests;
use App\Livewire\Pages\Concerns\RendersPageView;
use App\Models\Genre;
use App\Models\MediaAsset;
use App\Models\Season;
use App\Models\Title;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Livewire\Component;
use Livewire\WithFileUploads;

class TitlesPage extends Component
{
    use InteractsWithCatalogTitleState;
    use RendersPageView;
    use ResolvesAdminFormState;
    use ValidatesFormRequests;
    use WithFileUploads;

    public ?Title $title = null;

    public string $name = '';

    public ?string $original_name = null;

    public string $slug = '';

    public string $title_type = 'movie';

    public ?int $release_year = null;

    public ?int $end_year = null;

    public ?string $release_date = null;

    public ?int $runtime_minutes = null;

    public ?string $age_rating = null;

    public ?string $origin_country = null;

    public ?string $original_language = null;

    public bool $is_published = true;

    /**
     * @var list<int>
     */
    public array $genre_ids = [];

    public ?string $plot_outline = null;

    public ?string $synopsis = null;

    public ?string $tagline = null;

    public ?string $meta_title = null;

    public ?string $meta_description = null;

    public ?string $search_keywords = null;

    /**
     * @var array<string, mixed>
     */
    public array $season = [];

    /**
     * @var array<string, mixed>
     */
    public array $draftMediaAsset = [];

    public function mount(?Title $title = null): void
    {
        $this->title = $title;
        $this->fillTitleForm($title ?? new Title(['is_published' => true, 'title_type' => TitleType::Movie]));
        $this->initializeDraftSeason();
        $this->initializeDraftMediaAsset();
    }

    protected function renderTitlesIndexPage(BuildAdminTitlesIndexQueryAction $buildAdminTitlesIndexQuery): View
    {
        return $this->renderPageView('admin.titles.index', [
            'titles' => $buildAdminTitlesIndexQuery
                ->handle()
                ->simplePaginate(20)
                ->withQueryString(),
        ]);
    }

    protected function renderTitleCreatePage(): View
    {
        if ($this->isCatalogOnlyApplication()) {
            return $this->renderPageView('admin.titles.create', [
                'title' => new Title($this->titlePreviewPayload()),
            ]);
        }

        return $this->renderPageView('admin.titles.create', [
            'title' => new Title($this->titlePreviewPayload()),
            'selectedGenreIds' => $this->selectedGenreIds(),
            ...$this->formOptions(),
        ]);
    }

    protected function renderTitleEditPage(): View
    {
        abort_unless($this->title instanceof Title, 404);

        if ($this->isCatalogOnlyApplication()) {
            return $this->renderPageView('admin.titles.edit', [
                'title' => tap($this->title)->fill($this->titlePreviewPayload()),
            ]);
        }

        $loadedTitle = $this->title->load([
            'genres:id,name',
            'credits' => fn ($creditQuery) => $creditQuery
                ->select([
                    'id',
                    'title_id',
                    'person_id',
                    'department',
                    'job',
                    'character_name',
                    'billing_order',
                    'credited_as',
                    'is_principal',
                    'person_profession_id',
                    'episode_id',
                ])
                ->with(['person:id,name,slug', 'profession:id,profession', 'episode.title:id,name'])
                ->limit(50),
            'seasons' => fn ($seasonQuery) => $seasonQuery
                ->select([
                    'id',
                    'series_id',
                    'name',
                    'slug',
                    'season_number',
                    'release_year',
                ])
                ->withCount('episodes'),
            'mediaAssets' => fn ($mediaQuery) => $mediaQuery->select([
                'id',
                'mediable_type',
                'mediable_id',
                'kind',
                'url',
                'alt_text',
                'caption',
                'is_primary',
                'position',
                'published_at',
            ]),
        ]);
        $loadedTitle->fill($this->titlePreviewPayload());
        $draftMediaAsset = tap(
            new MediaAsset(Arr::except($this->draftMediaAssetPayload(), ['file'])),
            fn (MediaAsset $mediaAsset) => $mediaAsset->setRelation('mediable', $loadedTitle),
        );

        return $this->renderPageView('admin.titles.edit', [
            'title' => $loadedTitle,
            'draftSeason' => new Season($this->season),
            'draftSeasonFormData' => $this->adminFormBindingData('season'),
            'draftMediaAsset' => $draftMediaAsset,
            'draftMediaAssetFormData' => $this->adminMediaAssetFormData($draftMediaAsset, 'draftMediaAsset'),
            'selectedGenreIds' => $this->selectedGenreIds(),
            ...$this->formOptions(),
        ]);
    }

    /**
     * @return array{
     *     titleTypes: list<TitleType>,
     *     genres: Collection<int, Genre>
     * }
     */
    private function formOptions(): array
    {
        return [
            'titleTypes' => TitleType::cases(),
            'genres' => Genre::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
        ];
    }

    public function saveTitle(StoreTitleAction $storeTitle, UpdateTitleAction $updateTitle): mixed
    {
        $validated = $this->title instanceof Title
            ? $this->validateWithFormRequest(UpdateTitleRequest::class, $this->titlePayload(), [
                'title' => $this->title,
            ])
            : $this->validateWithFormRequest(StoreTitleRequest::class, $this->titlePayload());

        $savedTitle = $this->title instanceof Title
            ? $updateTitle->handle($this->title, $validated)
            : $storeTitle->handle($validated);

        $this->title = $savedTitle;
        $this->fillTitleForm($savedTitle);
        $this->initializeDraftSeason();
        $this->initializeDraftMediaAsset();
        $this->resetValidation();
        session()->flash('status', $savedTitle->wasRecentlyCreated ? 'Title created.' : 'Title updated.');

        return $this->redirectRoute('admin.titles.edit', $savedTitle);
    }

    public function saveSeason(SaveSeasonAction $saveSeason): void
    {
        abort_unless($this->title instanceof Title, 404);

        $validated = $this->validateWithFormRequest(
            StoreSeasonRequest::class,
            ['season' => $this->season],
            ['title' => $this->title],
        );

        $saveSeason->handle(new Season, $this->title, $validated['season']);

        $this->title->refresh();
        $this->initializeDraftSeason();
        $this->resetValidation();
        session()->flash('status', 'Season added.');
    }

    public function saveDraftMediaAsset(SaveMediaAssetAction $saveMediaAsset): void
    {
        abort_unless($this->title instanceof Title, 404);

        $validated = $this->validateWithFormRequest(
            StoreMediaAssetRequest::class,
            $this->draftMediaAssetPayload(),
            ['title' => $this->title],
        );

        $saveMediaAsset->handle(new MediaAsset, $this->title, $validated);

        $this->title->refresh();
        $this->initializeDraftMediaAsset();
        $this->resetValidation();
        session()->flash('status', 'Media asset added.');
    }

    public function deleteTitle(DeleteTitleAction $deleteTitle): mixed
    {
        abort_unless($this->title instanceof Title, 404);

        $this->authorize('delete', $this->title);
        $deleteTitle->handle($this->title);
        session()->flash('status', 'Title deleted.');

        return $this->redirectRoute('admin.titles.index');
    }

    private function fillTitleForm(Title $title): void
    {
        $this->name = (string) $title->name;
        $this->original_name = $title->original_name;
        $this->slug = (string) $title->slug;
        $this->title_type = (string) ($title->title_type?->value ?? $title->title_type ?? TitleType::Movie->value);
        $this->release_year = $title->release_year;
        $this->end_year = $title->end_year;
        $this->release_date = $this->optionalTitleDateString($title, 'release_date');
        $this->runtime_minutes = $title->runtime_minutes;
        $this->age_rating = $this->optionalTitleAttribute($title, 'age_rating');
        $this->origin_country = $this->optionalTitleAttribute($title, 'origin_country');
        $this->original_language = $this->optionalTitleAttribute($title, 'original_language');
        $this->is_published = (bool) $this->optionalTitleAttribute($title, 'is_published', true);
        $this->genre_ids = $title->exists
            ? $title->genres()->pluck('genres.id')->map(fn (int $genreId): int => $genreId)->all()
            : [];
        $this->plot_outline = $this->optionalTitleAttribute($title, 'plot_outline');
        $this->synopsis = $this->optionalTitleAttribute($title, 'synopsis');
        $this->tagline = $this->optionalTitleAttribute($title, 'tagline');
        $this->meta_title = $this->optionalTitleAttribute($title, 'meta_title');
        $this->meta_description = $this->optionalTitleAttribute($title, 'meta_description');
        $this->search_keywords = $this->optionalTitleAttribute($title, 'search_keywords');
    }

    private function initializeDraftSeason(): void
    {
        $nextSeasonNumber = 1;

        if (! $this->isCatalogOnlyApplication() && $this->title instanceof Title) {
            $nextSeasonNumber = ((int) ($this->title->seasons()->max('season_number') ?? 0)) + 1;
        }

        $this->season = [
            'name' => null,
            'slug' => null,
            'season_number' => $nextSeasonNumber,
            'summary' => null,
            'release_year' => $this->release_year,
            'meta_title' => null,
            'meta_description' => null,
        ];
    }

    private function initializeDraftMediaAsset(): void
    {
        $this->draftMediaAsset = [
            'kind' => MediaKind::Poster->value,
            'file' => null,
            'url' => null,
            'alt_text' => null,
            'caption' => null,
            'width' => null,
            'height' => null,
            'provider' => null,
            'provider_key' => null,
            'language' => null,
            'duration_seconds' => null,
            'metadata' => null,
            'is_primary' => true,
            'position' => 0,
            'published_at' => null,
            'mediable_type' => Title::class,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function titlePayload(): array
    {
        return [
            'name' => $this->name,
            'original_name' => $this->original_name,
            'slug' => $this->slug,
            'title_type' => $this->title_type,
            'release_year' => $this->release_year,
            'end_year' => $this->end_year,
            'release_date' => $this->release_date,
            'runtime_minutes' => $this->runtime_minutes,
            'age_rating' => $this->age_rating,
            'origin_country' => $this->origin_country,
            'original_language' => $this->original_language,
            'is_published' => $this->is_published,
            'genre_ids' => $this->genre_ids,
            'plot_outline' => $this->plot_outline,
            'synopsis' => $this->synopsis,
            'tagline' => $this->tagline,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'search_keywords' => $this->search_keywords,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function titlePreviewPayload(): array
    {
        return Arr::only($this->titlePayload(), (new Title)->getFillable());
    }

    /**
     * @return list<int>
     */
    private function selectedGenreIds(): array
    {
        return collect(old('genre_ids', $this->genre_ids))
            ->map(fn (mixed $genreId): int => (int) $genreId)
            ->filter(fn (int $genreId): bool => $genreId > 0)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function draftMediaAssetPayload(): array
    {
        return $this->draftMediaAsset;
    }
}
