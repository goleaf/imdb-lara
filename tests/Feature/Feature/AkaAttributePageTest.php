<?php

namespace Tests\Feature\Feature;

use App\Models\MovieAkaAttribute;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class AkaAttributePageTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_aka_attribute_page_renders_related_titles_and_filter_state(): void
    {
        $movieAkaAttribute = MovieAkaAttribute::query()
            ->select(['movie_aka_id', 'aka_attribute_id', 'position'])
            ->with([
                'akaAttribute:id,name',
                'movieAka:id,movie_id,text,country_code,language_code,position',
                'movieAka.country:code,name',
                'movieAka.language:code,name',
                'movieAka.title' => fn ($titleQuery) => $titleQuery
                    ->select($this->remoteTitleColumns())
                    ->publishedCatalog(),
                'movieAka.movieAkaAttributes' => fn ($movieAkaAttributeQuery) => $movieAkaAttributeQuery
                    ->select(['movie_aka_id', 'aka_attribute_id', 'position'])
                    ->with([
                        'akaAttribute:id,name',
                    ])
                    ->orderBy('position'),
            ])
            ->whereHas('akaAttribute', fn ($attributeQuery) => $attributeQuery->whereNotNull('name'))
            ->whereHas('movieAka.title', fn ($titleQuery) => $titleQuery->publishedCatalog())
            ->whereHas('movieAka', fn ($movieAkaQuery) => $movieAkaQuery->whereNotNull('text'))
            ->orderBy('movie_aka_id')
            ->orderBy('position')
            ->first();

        if (! $movieAkaAttribute instanceof MovieAkaAttribute
            || ! $movieAkaAttribute->akaAttribute
            || ! $movieAkaAttribute->movieAka
            || ! $movieAkaAttribute->movieAka->title) {
            $this->markTestSkipped('The remote catalog does not currently expose a renderable AKA attribute archive sample.');
        }

        $response = $this->get(route('public.aka-attributes.show', $movieAkaAttribute->akaAttribute));

        $response
            ->assertOk()
            ->assertSeeHtml('data-slot="aka-attribute-detail-hero"')
            ->assertSeeHtml('data-slot="aka-attribute-detail-records"')
            ->assertSee($movieAkaAttribute->akaAttribute->resolvedLabel())
            ->assertSee($movieAkaAttribute->movieAka->title->name)
            ->assertSee($movieAkaAttribute->movieAka->text)
            ->assertSee(route('public.titles.show', $movieAkaAttribute->movieAka->title), false);

        $filteredResponse = $this->get(route('public.aka-attributes.show', [
            'akaAttribute' => $movieAkaAttribute->akaAttribute,
            'q' => $movieAkaAttribute->movieAka->title->name,
            'type' => $movieAkaAttribute->movieAka->title->title_type->value,
        ]));

        $filteredResponse
            ->assertOk()
            ->assertSeeHtml('data-slot="aka-attribute-detail-filters"')
            ->assertSee($movieAkaAttribute->movieAka->title->name)
            ->assertSee($movieAkaAttribute->akaAttribute->resolvedLabel());
    }
}
