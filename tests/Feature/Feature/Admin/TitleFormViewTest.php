<?php

namespace Tests\Feature\Feature\Admin;

use App\Enums\TitleType;
use App\Models\Genre;
use App\Models\Title;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class TitleFormViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_title_form_partial_uses_shared_sheaf_form_controls(): void
    {
        $title = Title::factory()->movie()->make([
            'title_type' => TitleType::Series,
            'is_published' => false,
        ]);

        $genres = Genre::factory()->count(3)->create();

        $title->setRelation('genres', $genres->take(2));

        view()->share('errors', new ViewErrorBag);

        $response = $this->view('admin.titles._form', [
            'title' => $title,
            'titleTypes' => TitleType::cases(),
            'genres' => $genres,
            'selectedGenreIds' => $title->genres->pluck('id')->all(),
        ]);

        $response
            ->assertSeeHtml('data-slot="checkbox-wrapper"')
            ->assertSee('Type')
            ->assertSee('Publish status')
            ->assertSee('Genres')
            ->assertDontSee(
                'class="min-h-10 rounded-box border border-black/10 bg-white px-3 text-sm text-neutral-800 shadow-xs transition focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15 dark:border-white/15 dark:bg-neutral-900 dark:text-neutral-200 dark:focus:border-white/20 dark:focus:ring-neutral-100/15"',
                false,
            )
            ->assertDontSee(
                'class="rounded border-black/20 text-neutral-900 focus:ring-neutral-900/20 dark:border-white/20 dark:bg-neutral-900 dark:text-neutral-100 dark:focus:ring-neutral-100/20"',
                false,
            );
    }
}
