<?php

namespace Tests\Feature\Feature\Seo;

use App\Actions\Seo\GetSitemapDataAction;
use App\Models\Genre;
use App\Models\InterestCategory;
use App\Models\Person;
use App\Models\Title;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Mockery;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class SitemapAndRobotsTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_sitemap_and_robots_include_public_catalog_endpoints(): void
    {
        $title = $this->makeTitle([
            'id' => 1,
            'tconst' => 'tt0133093',
            'imdb_id' => 'tt0133093',
            'primarytitle' => 'The Matrix',
            'isadult' => 0,
            'startyear' => 1999,
        ]);
        $genre = $this->makeGenre([
            'id' => 1,
            'name' => 'Science Fiction',
        ]);
        $interestCategory = $this->makeInterestCategory([
            'id' => 1,
            'name' => 'Cyberpunk',
        ]);
        $person = $this->makePerson([
            'id' => 1,
            'nconst' => 'nm0000206',
            'primaryname' => 'Keanu Reeves',
            'displayName' => 'Keanu Reeves',
        ]);

        $getSitemapData = Mockery::mock(GetSitemapDataAction::class);
        $getSitemapData
            ->shouldReceive('handle')
            ->once()
            ->andReturn([
                'staticRoutes' => [
                    route('public.home'),
                    route('public.awards.index'),
                    route('public.trailers.latest'),
                    route('public.interest-categories.index'),
                ],
                'genres' => new EloquentCollection([$genre]),
                'interestCategories' => new EloquentCollection([$interestCategory]),
                'years' => collect([1999]),
                'titles' => new EloquentCollection([$title]),
                'titleArchiveUrls' => collect([
                    route('public.titles.cast', $title),
                    route('public.titles.media', $title),
                    route('public.titles.box-office', $title),
                    route('public.titles.parents-guide', $title),
                    route('public.titles.trivia', $title),
                    route('public.titles.metadata', $title),
                ]),
                'episodes' => new EloquentCollection,
                'seasons' => new EloquentCollection,
                'people' => new EloquentCollection([$person]),
            ]);

        $this->app->instance(GetSitemapDataAction::class, $getSitemapData);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(route('public.home'), false)
            ->assertSee(route('public.awards.index'), false)
            ->assertSee(route('public.trailers.latest'), false)
            ->assertSee(route('public.interest-categories.index'), false)
            ->assertSee(route('public.interest-categories.show', $interestCategory), false)
            ->assertSee(route('public.genres.show', $genre), false)
            ->assertSee(route('public.years.show', ['year' => 1999]), false)
            ->assertSee(route('public.titles.cast', $title), false)
            ->assertSee(route('public.titles.media', $title), false)
            ->assertSee(route('public.titles.box-office', $title), false)
            ->assertSee(route('public.titles.parents-guide', $title), false)
            ->assertSee(route('public.titles.trivia', $title), false)
            ->assertSee(route('public.titles.metadata', $title), false)
            ->assertSee(route('public.titles.show', $title), false)
            ->assertSee(route('public.people.show', $person), false)
            ->assertDontSee('/lists/', false)
            ->assertDontSee('/users/', false)
            ->assertDontSee(route('public.search'), false);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: '.route('sitemap'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeTitle(array $attributes): Title
    {
        $title = new Title;
        $title->forceFill($attributes);
        $title->exists = true;

        return $title;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeGenre(array $attributes): Genre
    {
        $genre = new Genre;
        $genre->forceFill($attributes);
        $genre->exists = true;

        return $genre;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeInterestCategory(array $attributes): InterestCategory
    {
        $interestCategory = new InterestCategory;
        $interestCategory->forceFill($attributes);
        $interestCategory->exists = true;

        return $interestCategory;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makePerson(array $attributes): Person
    {
        $person = new Person;
        $person->forceFill($attributes);
        $person->exists = true;

        return $person;
    }
}
