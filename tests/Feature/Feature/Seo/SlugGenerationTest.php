<?php

namespace Tests\Feature\Feature\Seo;

use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class SlugGenerationTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_titles_and_people_expose_slugged_route_keys_from_remote_identifiers(): void
    {
        $title = $this->sampleTitle();
        $person = $this->samplePerson();

        $this->assertSame(
            Str::slug($title->name).'-'.($title->tconst ?: $title->imdb_id ?: $title->id),
            $title->slug,
        );
        $this->assertSame(
            Str::slug($person->name).'-'.($person->nconst ?: $person->imdb_id ?: $person->id),
            $person->slug,
        );
    }

    public function test_public_catalog_routes_resolve_remote_records_by_slug(): void
    {
        $title = $this->sampleTitle();
        $person = $this->samplePerson();
        $genre = $this->sampleGenre();

        $this->assertStringEndsWith('-g'.$genre->id, $genre->slug);

        $this->get(route('public.titles.show', ['title' => $title->slug]))
            ->assertOk()
            ->assertSee($title->name);

        $this->get(route('public.people.show', ['person' => $person->slug]))
            ->assertOk()
            ->assertSee($person->name);

        $this->get(route('public.genres.show', ['genre' => $genre->slug]))
            ->assertOk()
            ->assertSee($genre->name);
    }
}
