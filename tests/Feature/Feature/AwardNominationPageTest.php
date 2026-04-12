<?php

namespace Tests\Feature\Feature;

use App\Models\AwardNomination;
use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class AwardNominationPageTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_award_nomination_page_renders_current_relations_and_same_category_archive(): void
    {
        $awardNomination = AwardNomination::query()
            ->select([
                'id',
                'movie_id',
                'event_imdb_id',
                'award_category_id',
                'award_year',
                'text',
                'is_winner',
                'winner_rank',
                'position',
            ])
            ->whereNotNull('event_imdb_id')
            ->whereNotNull('award_category_id')
            ->where(function (Builder $query): void {
                $query->whereHas('title', fn (Builder $titleQuery): Builder => $titleQuery->publishedCatalog());

                if (AwardNomination::catalogNomineePeopleAvailable()) {
                    $query->orWhereHas('people');
                }
            })
            ->with([
                'awardEvent:imdb_id,name',
                'awardCategory:id,name',
                'title' => fn ($titleQuery) => $titleQuery
                    ->select($this->remoteTitleColumns())
                    ->publishedCatalog(),
                'movieAwardNominationNominees' => fn ($nomineeQuery) => $nomineeQuery
                    ->select(['movie_award_nomination_id', 'name_basic_id', 'position'])
                    ->with(
                        AwardNomination::catalogNomineePeopleAvailable()
                            ? [
                                'person' => fn ($personQuery) => $personQuery->select($this->remotePersonColumns()),
                            ]
                            : []
                    )
                    ->orderBy('position'),
                'movieAwardNominationTitles' => fn ($nominationTitleQuery) => $nominationTitleQuery
                    ->select(['movie_award_nomination_id', 'nominated_movie_id', 'position'])
                    ->with([
                        'title' => fn ($titleQuery) => $titleQuery
                            ->select($this->remoteTitleColumns())
                            ->publishedCatalog(),
                    ])
                    ->orderBy('position'),
            ])
            ->orderByDesc('award_year')
            ->orderBy('id')
            ->first();

        if (! $awardNomination instanceof AwardNomination || ! $awardNomination->awardEvent || ! $awardNomination->awardCategory) {
            $this->markTestSkipped('The remote catalog does not currently expose a renderable award nomination page sample.');
        }

        $response = $this->get(route('public.awards.nominations.show', $awardNomination));

        $response
            ->assertOk()
            ->assertSeeHtml('data-slot="award-nomination-detail-hero"')
            ->assertSeeHtml('data-slot="award-nomination-cohort"')
            ->assertSee($awardNomination->awardEvent->name)
            ->assertSee($awardNomination->awardCategory->name);

        if ($awardNomination->award_year !== null) {
            $response->assertSee((string) $awardNomination->award_year);
        }

        if ($awardNomination->winner_rank !== null) {
            $response
                ->assertSee('Rank #'.number_format($awardNomination->winner_rank))
                ->assertSeeHtml('data-slot="winner-rank-badge"');
        }

        $linkedNominee = $awardNomination->movieAwardNominationNominees
            ->map(fn ($nomineeRow) => $nomineeRow->person)
            ->first();

        if ($linkedNominee !== null) {
            $response
                ->assertSeeHtml('data-slot="award-nomination-linked-nominees"')
                ->assertSee($linkedNominee->name)
                ->assertSee(route('public.people.show', $linkedNominee), false);
        }

        $linkedTitle = collect([$awardNomination->title])
            ->concat($awardNomination->movieAwardNominationTitles->map(fn ($nominationTitleRow) => $nominationTitleRow->title))
            ->first(fn ($title): bool => $title instanceof Title);

        if ($linkedTitle instanceof Title) {
            $response
                ->assertSeeHtml('data-slot="award-nomination-linked-titles"')
                ->assertSee($linkedTitle->name)
                ->assertSee(route('public.titles.show', $linkedTitle), false);
        }
    }
}
