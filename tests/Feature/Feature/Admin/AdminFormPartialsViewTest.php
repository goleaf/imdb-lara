<?php

namespace Tests\Feature\Feature\Admin;

use App\Enums\MediaKind;
use App\Models\Credit;
use App\Models\Episode;
use App\Models\MediaAsset;
use App\Models\Person;
use App\Models\PersonProfession;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class AdminFormPartialsViewTest extends TestCase
{
    use RefreshDatabase;

    private const string LEGACY_SELECT_CLASSES = 'class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"';

    public function test_person_form_partial_uses_shared_sheaf_select_controls(): void
    {
        $person = Person::factory()->make([
            'is_published' => false,
        ]);

        view()->share('errors', new ViewErrorBag);

        $response = $this->view('admin.people._form', [
            'person' => $person,
        ]);

        $response
            ->assertSee('Publish status')
            ->assertDontSee(self::LEGACY_SELECT_CLASSES, false);
    }

    public function test_episode_form_partial_uses_shared_sheaf_select_controls(): void
    {
        $episodeTitle = Title::factory()->episode()->make([
            'is_published' => false,
        ]);
        $episode = Episode::factory()->make();
        $episode->setRelation('title', $episodeTitle);

        view()->share('errors', new ViewErrorBag);

        $response = $this->view('admin.episodes._form', [
            'episode' => $episode,
            'fieldName' => $this->fieldNameResolver('episode'),
            'fieldOldInputKey' => $this->fieldStateResolver('episode'),
            'fieldStatePath' => $this->fieldStateResolver('episode'),
        ]);

        $response
            ->assertSee('Publish status')
            ->assertDontSee(self::LEGACY_SELECT_CLASSES, false);
    }

    public function test_media_asset_form_partial_uses_shared_sheaf_select_controls(): void
    {
        $person = Person::factory()->make();
        $mediaAsset = MediaAsset::factory()->headshot()->make([
            'kind' => MediaKind::Headshot,
            'mediable_type' => Person::class,
            'mediable_id' => 1,
        ]);
        $mediaAsset->setRelation('mediable', $person);

        view()->share('errors', new ViewErrorBag);

        $response = $this->view('admin.media-assets._form', [
            'mediaAsset' => $mediaAsset,
            'allowedMediaKinds' => MediaKind::allowedForMediable($person),
            'allowedMediaKindsIncludeVideo' => false,
            'fieldName' => $this->fieldNameResolver('draftMediaAsset'),
            'fieldOldInputKey' => $this->fieldStateResolver('draftMediaAsset'),
            'fieldStatePath' => $this->fieldStateResolver('draftMediaAsset'),
            'selectedKindIsImage' => true,
        ]);

        $response
            ->assertSee('Kind')
            ->assertDontSee(self::LEGACY_SELECT_CLASSES, false);
    }

    public function test_credit_form_partial_uses_shared_sheaf_select_controls(): void
    {
        $title = Title::factory()->make(['id' => 101]);
        $person = Person::factory()->make(['id' => 202]);
        $episodeTitle = Title::factory()->episode()->make();
        $episode = Episode::factory()->make(['id' => 303]);
        $episode->setRelation('title', $episodeTitle);
        $profession = PersonProfession::factory()->make(['id' => 404]);
        $profession->setRelation('person', $person);
        $credit = Credit::factory()->make([
            'title_id' => $title->id,
            'person_id' => $person->id,
            'person_profession_id' => $profession->id,
            'episode_id' => $episode->id,
        ]);

        view()->share('errors', new ViewErrorBag);

        $response = $this->view('admin.credits._form', [
            'credit' => $credit,
            'titles' => collect([$title]),
            'people' => collect([$person]),
            'professions' => collect([$profession]),
            'episodes' => collect([$episode]),
        ]);

        $response
            ->assertSee('Profession link')
            ->assertSee('Episode specificity')
            ->assertDontSee(self::LEGACY_SELECT_CLASSES, false);
    }

    /**
     * @return \Closure(string): string
     */
    private function fieldNameResolver(string $prefix): \Closure
    {
        return static fn (string $field): string => sprintf('%s[%s]', $prefix, $field);
    }

    /**
     * @return \Closure(string): string
     */
    private function fieldStateResolver(string $prefix): \Closure
    {
        return static fn (string $field): string => sprintf('%s.%s', $prefix, $field);
    }
}
