<?php

namespace Tests\Feature\Feature;

use App\Models\AwardEvent;
use App\Models\AwardNomination;
use App\Models\Title;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class AwardsArchiveExperienceTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_awards_archive_page_renders_live_event_category_and_linked_honorees(): void
    {
        $eventId = AwardNomination::query()
            ->select(['event_imdb_id'])
            ->whereNotNull('event_imdb_id')
            ->distinct()
            ->orderBy('event_imdb_id')
            ->limit(40)
            ->value('event_imdb_id');

        if (! is_string($eventId) || $eventId === '') {
            $this->markTestSkipped('The remote catalog does not currently expose any award events for the public archive.');
        }

        $event = AwardEvent::query()
            ->select(['imdb_id', 'name'])
            ->where('imdb_id', $eventId)
            ->with([
                'nominations' => fn ($nominationQuery) => $nominationQuery
                    ->select([
                        'id',
                        'event_imdb_id',
                        'movie_id',
                        'award_category_id',
                        'award_year',
                        'text',
                        'is_winner',
                        'winner_rank',
                        'position',
                    ])
                    ->with([
                        'awardCategory:id,name',
                        'title' => fn ($titleQuery) => $titleQuery
                            ->select($this->remoteTitleColumns())
                            ->publishedCatalog(),
                        ...(
                            AwardNomination::catalogNomineePeopleAvailable()
                                ? [
                                    'people' => fn ($personQuery) => $personQuery->select($this->remotePersonColumns()),
                                ]
                                : []
                        ),
                    ])
                    ->orderByDesc('is_winner')
                    ->orderBy('position')
                    ->orderBy('id'),
            ])
            ->first();

        if (! $event instanceof AwardEvent) {
            $this->markTestSkipped('The sampled remote award event could not be loaded for archive assertions.');
        }

        $nomination = $event->nominations->first(
            fn (AwardNomination $entry): bool => filled($entry->awardCategory?->name)
                && (
                    $entry->title instanceof Title
                    || $entry->person !== null
                    || filled($entry->text)
                ),
        );

        if (! $nomination instanceof AwardNomination) {
            $this->markTestSkipped('The sampled remote award event does not contain a renderable category entry.');
        }

        $entryLabel = $nomination->title?->name
            ?? $nomination->person?->name
            ?? (string) $nomination->text;

        $this->get(route('public.awards.index'))
            ->assertOk()
            ->assertSee('Awards Archive')
            ->assertSeeHtml('data-slot="awards-archive-hero"')
            ->assertSeeHtml('data-slot="awards-archive-shell"')
            ->assertSeeHtml('data-slot="awards-timeline"')
            ->assertSeeHtml('data-slot="award-event-marker"')
            ->assertSeeHtml('data-slot="award-event-card"')
            ->assertSee($event->name)
            ->assertSee($nomination->awardCategory->name)
            ->assertSee($nomination->is_winner ? 'Winner' : 'Nominee')
            ->assertSee($entryLabel);
    }

    public function test_awards_archive_page_uses_catalog_only_summary_copy(): void
    {
        $this->get(route('public.awards.index'))
            ->assertOk()
            ->assertSee('Archive Highlights')
            ->assertSee('Award events')
            ->assertSee('Named archives')
            ->assertSee('Categories')
            ->assertSee('Honorees')
            ->assertDontSee('Write a review')
            ->assertDontSee('Watchlist')
            ->assertDontSee('Create account');
    }
}
