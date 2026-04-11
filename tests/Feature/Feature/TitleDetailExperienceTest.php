<?php

namespace Tests\Feature\Feature;

use App\Enums\CountryCode;
use App\Enums\LanguageCode;
use App\Models\AwardNomination;
use App\Models\MovieAka;
use App\Models\MovieAkaAttribute;
use App\Models\MovieAwardNominationNominee;
use App\Models\MovieAwardNominationSummary;
use App\Models\MovieAwardNominationTitle;
use App\Models\MovieBoxOffice;
use App\Models\MovieCertificate;
use App\Models\MovieCertificateAttribute;
use App\Models\MovieCertificateSummary;
use App\Models\MovieCompanyCredit;
use App\Models\MovieCompanyCreditAttribute;
use App\Models\MovieCompanyCreditCountry;
use App\Models\MovieCompanyCreditSummary;
use App\Models\MovieDirector;
use App\Models\MovieEpisode;
use App\Models\MovieEpisodeSummary;
use App\Models\MovieGenre;
use App\Models\MovieImageSummary;
use App\Models\Title;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithRemoteCatalog;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class TitleDetailExperienceTest extends TestCase
{
    use InteractsWithRemoteCatalog;
    use UsesCatalogOnlyApplication;

    public function test_title_page_renders_the_current_catalog_detail_surface(): void
    {
        Livewire::withoutLazyLoading();

        $title = $this->sampleTitleWithMedia();

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-hero"')
            ->assertSee($title->name)
            ->assertSee('Overview')
            ->assertSee('Gallery')
            ->assertSee('Archive Views')
            ->assertSee('Media Gallery')
            ->assertSee('Keywords & Connections')
            ->assertSee('Trivia & Goofs');
    }

    public function test_title_page_exposes_a_clickable_poster_lightbox_when_a_poster_is_available(): void
    {
        Livewire::withoutLazyLoading();

        $title = $this->sampleTitleWithMedia();

        $title->loadMissing([
            'titleImages:id,movie_id,position,url,width,height,type',
            'primaryImageRecord:movie_id,url,width,height,type',
        ]);

        $poster = $title->preferredPoster();

        if ($poster === null) {
            $this->markTestSkipped('The sampled catalog title does not currently expose a poster image.');
        }

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-poster-trigger"')
            ->assertSeeHtml('data-slot="title-detail-poster-lightbox"')
            ->assertSee('Close poster lightbox')
            ->assertSee($poster->url);
    }

    public function test_title_page_exposes_the_catalog_credit_and_related_title_surface(): void
    {
        Livewire::withoutLazyLoading();

        $title = $this->sampleTitleWithCredits();

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee('Featured cast')
            ->assertSeeHtml('data-slot="featured-cast-card"')
            ->assertSee('View person')
            ->assertSee('Key crew')
            ->assertSee('Full Cast & Crew');
    }

    public function test_title_page_stays_catalog_only_without_write_side_actions(): void
    {
        Livewire::withoutLazyLoading();

        $title = $this->sampleTitle();

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertDontSee('Write a review')
            ->assertDontSee('Your rating')
            ->assertDontSee('Watchlist')
            ->assertDontSee('Edit title');
    }

    public function test_title_page_surfaces_the_imported_discovery_profile_when_interest_tags_exist(): void
    {
        Livewire::withoutLazyLoading();

        $title = $this->sampleTitleWithInterests();

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSee('Discovery profile')
            ->assertDontSee('Imported interest tags and subgenre signals that shape related-title discovery across the MySQL catalog.')
            ->assertSeeHtml('data-slot="title-discovery-profile"')
            ->assertSee('/interest-categories/', false)
            ->assertSee('Open the full keywords and connections view');
    }

    public function test_title_page_surfaces_raw_aka_attribute_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieAkaAttribute = MovieAkaAttribute::query()
            ->select(['movie_aka_id', 'aka_attribute_id', 'position'])
            ->with([
                'akaAttribute:id,name',
                'movieAka:id,movie_id,text,position',
            ])
            ->whereHas('akaAttribute', fn ($query) => $query->whereNotNull('name'))
            ->orderBy('movie_aka_id')
            ->orderBy('position')
            ->first();

        if (! $movieAkaAttribute instanceof MovieAkaAttribute || ! $movieAkaAttribute->movieAka || ! $movieAkaAttribute->akaAttribute) {
            $this->markTestSkipped('The remote catalog does not currently expose movie aka attribute rows with linked attributes.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieAkaAttribute->movieAka->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie aka attribute row is not linked to a published title page.');
        }

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-aka-attributes"')
            ->assertSee('Attribute')
            ->assertSee('Meaning')
            ->assertSee('Used on this movie')
            ->assertSee('Archive')
            ->assertSee($movieAkaAttribute->akaAttribute->resolvedLabel())
            ->assertSee($movieAkaAttribute->akaAttribute->shortDescription())
            ->assertSee(route('public.aka-attributes.show', $movieAkaAttribute->akaAttribute), false)
            ->assertDontSee('Raw rows imported from the <code>aka_attributes</code> table and linked to this movie through its AKA records.', false);
    }

    public function test_title_page_surfaces_raw_movie_aka_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieAka = MovieAka::query()
            ->select(['id', 'movie_id', 'text', 'country_code', 'language_code', 'position'])
            ->with(['language:code,name'])
            ->orderBy('movie_id')
            ->orderBy('position')
            ->first();

        if (! $movieAka instanceof MovieAka) {
            $this->markTestSkipped('The remote catalog does not currently expose movie aka rows.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieAka->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie aka row is not linked to a published title page.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-movie-akas"')
            ->assertSee($movieAka->text);

        $content = $response->getContent();
        $this->assertIsString($content);

        preg_match('/data-slot="title-detail-movie-akas".*?<\/table>/s', $content, $matches);
        $movieAkaTableMarkup = $matches[0] ?? '';

        $this->assertNotSame('', $movieAkaTableMarkup);
        $this->assertStringContainsString('>Title</th>', $movieAkaTableMarkup);
        $this->assertStringContainsString('>Country</th>', $movieAkaTableMarkup);
        $this->assertStringContainsString('>Language</th>', $movieAkaTableMarkup);
        $this->assertStringNotContainsString('>id</th>', $movieAkaTableMarkup);
        $this->assertStringNotContainsString('>movie_id</th>', $movieAkaTableMarkup);
        $this->assertStringNotContainsString('>position</th>', $movieAkaTableMarkup);

        if ($movieAka->country_code !== null) {
            $expectedCountryLabel = CountryCode::labelFor($movieAka->country_code) ?? $movieAka->country_code;

            $response
                ->assertSee($expectedCountryLabel)
                ->assertSeeHtml('data-flag-code="'.strtolower($movieAka->country_code).'"');

            $this->assertStringContainsString($expectedCountryLabel, $movieAkaTableMarkup);
        }

        if ($movieAka->language_code !== null) {
            $response->assertSee(
                $movieAka->language?->name
                    ?? LanguageCode::labelFor($movieAka->language_code)
                    ?? $movieAka->language_code,
            );
        }
    }

    public function test_title_page_does_not_surface_the_removed_movie_aka_attribute_table(): void
    {
        Livewire::withoutLazyLoading();

        $movieAkaAttribute = MovieAkaAttribute::query()
            ->select(['movie_aka_id', 'aka_attribute_id', 'position'])
            ->with([
                'movieAka:id,movie_id,text,position',
            ])
            ->orderBy('movie_aka_id')
            ->orderBy('position')
            ->first();

        if (! $movieAkaAttribute instanceof MovieAkaAttribute || ! $movieAkaAttribute->movieAka) {
            $this->markTestSkipped('The remote catalog does not currently expose movie aka attribute rows.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieAkaAttribute->movieAka->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie aka attribute row is not linked to a published title page.');
        }

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertDontSeeHtml('data-slot="title-detail-movie-aka-attributes"');
    }

    public function test_title_page_does_not_surface_the_removed_legacy_aka_type_table(): void
    {
        Livewire::withoutLazyLoading();

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereHas('movieAkas')
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The remote catalog does not currently expose a published title with movie AKA rows.');
        }

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertDontSeeHtml('data-slot="title-detail-aka-types"');
    }

    public function test_title_page_surfaces_raw_award_category_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereHas('awardNominations', fn ($query) => $query->whereHas('awardCategory'))
            ->orderBy('movies.id')
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The remote catalog does not currently expose a published title with award nominations.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-award-categories"')
            ->assertSee('Category');

        $content = $response->getContent();
        $this->assertIsString($content);

        preg_match('/data-slot="title-detail-award-categories".*?<\/table>/s', $content, $matches);
        $awardCategoryTableMarkup = $matches[0] ?? '';

        $this->assertNotSame('', $awardCategoryTableMarkup);
        $this->assertStringContainsString('>Category</th>', $awardCategoryTableMarkup);
        $this->assertStringNotContainsString('>id</th>', $awardCategoryTableMarkup);
        $this->assertStringNotContainsString('>name</th>', $awardCategoryTableMarkup);

        $category = $title->load([
            'awardNominations' => fn ($query) => $query
                ->select(['id', 'movie_id', 'award_category_id'])
                ->with([
                    'awardCategory:id,name',
                ]),
        ])->awardNominations
            ->map(fn ($nomination) => $nomination->awardCategory)
            ->filter()
            ->first();

        if ($category !== null) {
            $response
                ->assertSee($category->name);
        }
    }

    public function test_title_page_surfaces_raw_award_event_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereHas('awardNominations', fn ($query) => $query->whereHas('awardEvent'))
            ->orderBy('movies.id')
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The remote catalog does not currently expose a published title with award event rows.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-award-events"')
            ->assertSee('Event');

        $content = $response->getContent();
        $this->assertIsString($content);

        preg_match('/data-slot="title-detail-award-events".*?<\/table>/s', $content, $matches);
        $awardEventTableMarkup = $matches[0] ?? '';

        $this->assertNotSame('', $awardEventTableMarkup);
        $this->assertStringContainsString('>Event</th>', $awardEventTableMarkup);
        $this->assertStringNotContainsString('>name</th>', $awardEventTableMarkup);
        $this->assertStringNotContainsString('>imdb_id</th>', $awardEventTableMarkup);

        $awardEvent = $title->load([
            'awardNominations' => fn ($query) => $query
                ->select(['id', 'movie_id', 'event_imdb_id'])
                ->with([
                    'awardEvent:imdb_id,name',
                ]),
        ])->awardNominations
            ->map(fn ($nomination) => $nomination->awardEvent)
            ->filter()
            ->first();

        if ($awardEvent !== null) {
            $response->assertSee($awardEvent->name);
        }
    }

    public function test_title_page_surfaces_raw_movie_award_nomination_nominee_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieAwardNominationNominee = MovieAwardNominationNominee::query()
            ->select(['movie_award_nomination_id', 'name_basic_id', 'position'])
            ->with([
                'movieAwardNomination:id,movie_id,position',
                'person:id,nconst,imdb_id,primaryname,displayName,primaryImage_url,primaryImage_width,primaryImage_height',
                'awardNomination:id,event_imdb_id,award_category_id,award_year',
                'awardNomination.awardEvent:imdb_id,name',
                'awardNomination.awardCategory:id,name',
            ])
            ->orderBy('movie_award_nomination_id')
            ->orderBy('position')
            ->first();

        if (! $movieAwardNominationNominee instanceof MovieAwardNominationNominee || ! $movieAwardNominationNominee->movieAwardNomination) {
            $this->markTestSkipped('The remote catalog does not currently expose movie award nomination nominee rows.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieAwardNominationNominee->movieAwardNomination->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie award nomination nominee row is not linked to a published title page.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-movie-award-nomination-nominees"')
            ->assertSeeHtml('data-slot="title-detail-award-nominee-link"')
            ->assertSee('Nomination')
            ->assertSee('Nominee');

        $content = $response->getContent();
        $this->assertIsString($content);

        preg_match('/data-slot="title-detail-movie-award-nomination-nominees".*?<\/table>/s', $content, $matches);
        $movieAwardNominationNomineeTableMarkup = $matches[0] ?? '';

        $this->assertNotSame('', $movieAwardNominationNomineeTableMarkup);
        $this->assertStringNotContainsString('>movie_award_nomination_id</th>', $movieAwardNominationNomineeTableMarkup);
        $this->assertStringNotContainsString('>name_basic_id</th>', $movieAwardNominationNomineeTableMarkup);
        $this->assertStringNotContainsString('>position</th>', $movieAwardNominationNomineeTableMarkup);

        if ($movieAwardNominationNominee->awardNomination?->awardCategory?->name !== null) {
            $response->assertSee($movieAwardNominationNominee->awardNomination->awardCategory->name);
        }

        if ($movieAwardNominationNominee->awardNomination?->awardEvent?->name !== null) {
            $response->assertSee($movieAwardNominationNominee->awardNomination->awardEvent->name);
        }

        if ($movieAwardNominationNominee->awardNomination?->award_year !== null) {
            $response->assertSee((string) $movieAwardNominationNominee->awardNomination->award_year);
        }

        $nomineeName = $movieAwardNominationNominee->person?->displayName ?: $movieAwardNominationNominee->person?->primaryname;

        if ($nomineeName !== null) {
            $response->assertSee($nomineeName);
        }

        if ($movieAwardNominationNominee->person !== null) {
            $response->assertSee(route('public.people.show', $movieAwardNominationNominee->person), false);
        }

        if ($movieAwardNominationNominee->person?->primaryImage_url !== null) {
            $response->assertSee($movieAwardNominationNominee->person->primaryImage_url);
        }

        if ($movieAwardNominationNominee->awardNomination !== null) {
            $response->assertSee(route('public.awards.nominations.show', $movieAwardNominationNominee->awardNomination), false);
        }
    }

    public function test_title_page_surfaces_raw_movie_award_nomination_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieAwardNomination = AwardNomination::query()
            ->select(['id', 'movie_id', 'event_imdb_id', 'award_category_id', 'award_year', 'text', 'is_winner', 'winner_rank', 'position'])
            ->with([
                'awardEvent:imdb_id,name',
                'awardCategory:id,name',
            ])
            ->orderBy('movie_id')
            ->orderBy('position')
            ->first();

        if (! $movieAwardNomination instanceof AwardNomination) {
            $this->markTestSkipped('The remote catalog does not currently expose movie award nomination rows.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieAwardNomination->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie award nomination row is not linked to a published title page.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-movie-award-nominations"')
            ->assertSee('Event')
            ->assertSee('Category')
            ->assertSee('Year')
            ->assertSee('Note')
            ->assertSee('Winner')
            ->assertSee('Winner rank');

        $content = $response->getContent();
        $this->assertIsString($content);

        preg_match('/data-slot="title-detail-movie-award-nominations".*?<\/table>/s', $content, $matches);
        $movieAwardNominationTableMarkup = $matches[0] ?? '';

        $this->assertNotSame('', $movieAwardNominationTableMarkup);
        $this->assertStringNotContainsString('>id</th>', $movieAwardNominationTableMarkup);
        $this->assertStringNotContainsString('>movie_id</th>', $movieAwardNominationTableMarkup);
        $this->assertStringNotContainsString('>event_imdb_id</th>', $movieAwardNominationTableMarkup);
        $this->assertStringNotContainsString('>award_category_id</th>', $movieAwardNominationTableMarkup);
        $this->assertStringNotContainsString('>Order</th>', $movieAwardNominationTableMarkup);

        if ($movieAwardNomination->awardEvent?->name !== null) {
            $response->assertSee($movieAwardNomination->awardEvent->name);
        }

        if ($movieAwardNomination->awardCategory?->name !== null) {
            $response->assertSee($movieAwardNomination->awardCategory->name);
        }

        if ($movieAwardNomination->award_year !== null) {
            $response->assertSee((string) $movieAwardNomination->award_year);
        }

        if ($movieAwardNomination->text !== null) {
            $response->assertSee($movieAwardNomination->text);
        }

        if ($movieAwardNomination->is_winner !== null) {
            $response->assertSee($movieAwardNomination->is_winner ? 'Yes' : 'No');
        }

        if ($movieAwardNomination->winner_rank !== null) {
            $response
                ->assertSee((string) $movieAwardNomination->winner_rank)
                ->assertSeeHtml('data-slot="winner-rank-badge"');
        }

    }

    public function test_title_page_surfaces_raw_movie_award_nomination_summary_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieAwardNominationSummary = MovieAwardNominationSummary::query()
            ->select(['movie_id', 'nomination_count', 'win_count', 'next_page_token'])
            ->orderBy('movie_id')
            ->first();

        if (! $movieAwardNominationSummary instanceof MovieAwardNominationSummary) {
            $this->markTestSkipped('The remote catalog does not currently expose movie award nomination summary rows.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieAwardNominationSummary->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie award nomination summary row is not linked to a published title page.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-movie-award-nomination-summaries"')
            ->assertSee('Nominations')
            ->assertSee('Wins');

        $content = $response->getContent();

        $this->assertIsString($content);
        $this->assertMatchesRegularExpression(
            '/data-slot="title-detail-movie-award-nomination-summaries".*?<\/table>/s',
            $content,
        );
        preg_match(
            '/data-slot="title-detail-movie-award-nomination-summaries".*?<\/table>/s',
            $content,
            $matches,
        );

        $sectionMarkup = $matches[0] ?? '';

        $this->assertStringNotContainsString('>movie_id</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>nomination_count</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>win_count</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>next_page_token</th>', $sectionMarkup);

        if ($movieAwardNominationSummary->nomination_count !== null) {
            $response->assertSee((string) $movieAwardNominationSummary->nomination_count);
        }

        if ($movieAwardNominationSummary->win_count !== null) {
            $response->assertSee((string) $movieAwardNominationSummary->win_count);
        }

    }

    public function test_title_page_surfaces_raw_movie_certificate_summary_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieCertificateSummary = MovieCertificateSummary::query()
            ->select(['movie_id', 'total_count'])
            ->orderBy('movie_id')
            ->first();

        if (! $movieCertificateSummary instanceof MovieCertificateSummary) {
            $this->markTestSkipped('The remote catalog does not currently expose movie certificate summary rows.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieCertificateSummary->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie certificate summary row is not linked to a published title page.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-movie-certificate-summaries"')
            ->assertSee('movie_id')
            ->assertSee('total_count')
            ->assertSee((string) $movieCertificateSummary->movie_id);

        if ($movieCertificateSummary->total_count !== null) {
            $response->assertSee((string) $movieCertificateSummary->total_count);
        }
    }

    public function test_title_page_surfaces_raw_movie_certificate_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieCertificate = MovieCertificate::query()
            ->select(['id', 'movie_id', 'certificate_rating_id', 'country_code', 'position'])
            ->with([
                'certificateRating:id,name',
            ])
            ->whereHas('certificateRating', fn ($query) => $query->whereNotNull('name'))
            ->orderBy('movie_id')
            ->orderBy('position')
            ->first();

        if (! $movieCertificate instanceof MovieCertificate || ! $movieCertificate->certificateRating) {
            $this->markTestSkipped('The remote catalog does not currently expose movie certificate rows with linked certificate ratings.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieCertificate->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie certificate row is not linked to a published title page.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-movie-certificates"')
            ->assertSee('Certificate records linked directly to this title.')
            ->assertSee($movieCertificate->certificateRating->resolvedLabel())
            ->assertSee($movieCertificate->certificateRating->shortDescription())
            ->assertSeeHtml('data-slot="certificate-rating-chip"');

        $content = $response->getContent();

        $this->assertIsString($content);
        $this->assertMatchesRegularExpression(
            '/data-slot="title-detail-movie-certificates".*?<\/table>/s',
            $content,
        );
        preg_match(
            '/data-slot="title-detail-movie-certificates".*?<\/table>/s',
            $content,
            $matches,
        );

        $sectionMarkup = $matches[0] ?? '';

        $this->assertStringContainsString('>Rating</th>', $sectionMarkup);
        $this->assertStringContainsString('>Meaning</th>', $sectionMarkup);
        $this->assertStringContainsString('>Country</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>id</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>movie_id</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>certificate_rating_id</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>country_code</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>position</th>', $sectionMarkup);

        if ($movieCertificate->country_code !== null) {
            $expectedCountryLabel = CountryCode::labelFor($movieCertificate->country_code) ?? $movieCertificate->country_code;

            $response
                ->assertSee($expectedCountryLabel)
                ->assertSeeHtml('data-flag-code="'.strtolower($movieCertificate->country_code).'"');

            $this->assertStringContainsString($expectedCountryLabel, $sectionMarkup);
        }
    }

    public function test_title_page_surfaces_raw_movie_award_nomination_title_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieAwardNominationTitle = MovieAwardNominationTitle::query()
            ->select(['movie_award_nomination_id', 'nominated_movie_id', 'position'])
            ->with([
                'title:id,primarytitle,originaltitle,tconst',
                'movieAwardNomination:id,movie_id,event_imdb_id,award_category_id,award_year',
                'movieAwardNomination.event:imdb_id,name',
                'movieAwardNomination.awardCategory:id,name',
            ])
            ->whereHas('title', fn ($query) => $query->whereNotNull('primarytitle'))
            ->whereHas('movieAwardNomination.event', fn ($query) => $query->whereNotNull('name'))
            ->whereHas('movieAwardNomination.awardCategory', fn ($query) => $query->whereNotNull('name'))
            ->orderBy('movie_award_nomination_id')
            ->orderBy('position')
            ->first();

        if (! $movieAwardNominationTitle instanceof MovieAwardNominationTitle
            || ! $movieAwardNominationTitle->movieAwardNomination
            || ! $movieAwardNominationTitle->movieAwardNomination->event
            || ! $movieAwardNominationTitle->movieAwardNomination->awardCategory
            || ! $movieAwardNominationTitle->title) {
            $this->markTestSkipped('The remote catalog does not currently expose movie award nomination title rows with linked titles and nomination metadata.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieAwardNominationTitle->movieAwardNomination->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie award nomination title row is not linked to a published title page.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-movie-award-nomination-titles"')
            ->assertSeeText('Nominated titles linked to this title\'s award nominations.')
            ->assertSee($movieAwardNominationTitle->movieAwardNomination->awardCategory->name)
            ->assertSee($movieAwardNominationTitle->movieAwardNomination->event->name)
            ->assertSee($movieAwardNominationTitle->title->name);

        $sectionMarkup = $this->sectionMarkup($response, 'title-detail-movie-award-nomination-titles');

        $this->assertStringContainsString('>Nomination</th>', $sectionMarkup);
        $this->assertStringContainsString('>Title</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>movie_award_nomination_id</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>nominated_movie_id</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>position</th>', $sectionMarkup);
    }

    public function test_title_page_surfaces_raw_certificate_attribute_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieCertificateAttribute = MovieCertificateAttribute::query()
            ->select(['movie_certificate_id', 'certificate_attribute_id', 'position'])
            ->with([
                'certificateAttribute:id,name',
                'movieCertificate:id,movie_id,certificate_rating_id,country_code,position',
                'movieCertificate.certificateRating:id,name',
            ])
            ->whereHas('certificateAttribute', fn ($query) => $query->whereNotNull('name'))
            ->orderBy('movie_certificate_id')
            ->orderBy('position')
            ->first();

        if (! $movieCertificateAttribute instanceof MovieCertificateAttribute || ! $movieCertificateAttribute->movieCertificate || ! $movieCertificateAttribute->certificateAttribute) {
            $this->markTestSkipped('The remote catalog does not currently expose movie certificate attribute rows with linked attributes.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieCertificateAttribute->movieCertificate->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie certificate attribute row is not linked to a published title page.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-certificate-attributes"')
            ->assertSee($movieCertificateAttribute->certificateAttribute->name);

        $sectionMarkup = $this->sectionMarkup($response, 'title-detail-certificate-attributes');

        $this->assertStringContainsString('>Attribute</th>', $sectionMarkup);
        $this->assertStringContainsString('>Ratings on this title</th>', $sectionMarkup);
        $this->assertStringContainsString('>Countries on this title</th>', $sectionMarkup);
        $this->assertStringContainsString('>Certificates on this title</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>id</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>name</th>', $sectionMarkup);
        $this->assertStringContainsString(route('public.certificate-attributes.show', $movieCertificateAttribute->certificateAttribute), $sectionMarkup);

        if ($movieCertificateAttribute->movieCertificate?->certificateRating !== null) {
            $response
                ->assertSee($movieCertificateAttribute->movieCertificate->certificateRating->resolvedLabel())
                ->assertSeeHtml('data-slot="certificate-rating-chip"');
        }
    }

    public function test_title_page_surfaces_raw_movie_certificate_attribute_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieCertificateAttribute = MovieCertificateAttribute::query()
            ->select(['movie_certificate_id', 'certificate_attribute_id', 'position'])
            ->with([
                'certificateAttribute:id,name',
                'movieCertificate:id,movie_id,certificate_rating_id,position',
                'movieCertificate.certificateRating:id,name',
            ])
            ->whereHas('certificateAttribute', fn ($query) => $query->whereNotNull('name'))
            ->whereHas('movieCertificate.certificateRating', fn ($query) => $query->whereNotNull('name'))
            ->orderBy('movie_certificate_id')
            ->orderBy('position')
            ->first();

        if (! $movieCertificateAttribute instanceof MovieCertificateAttribute
            || ! $movieCertificateAttribute->movieCertificate
            || ! $movieCertificateAttribute->movieCertificate->certificateRating
            || ! $movieCertificateAttribute->certificateAttribute) {
            $this->markTestSkipped('The remote catalog does not currently expose movie certificate attribute rows with linked certificate metadata.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieCertificateAttribute->movieCertificate->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie certificate attribute bridge row is not linked to a published title page.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-movie-certificate-attributes"')
            ->assertSee($movieCertificateAttribute->movieCertificate->certificateRating->resolvedLabel())
            ->assertSee($movieCertificateAttribute->certificateAttribute->name);

        $sectionMarkup = $this->sectionMarkup($response, 'title-detail-movie-certificate-attributes');

        $this->assertStringContainsString('>Rating</th>', $sectionMarkup);
        $this->assertStringContainsString('>Attribute</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>movie_certificate_id</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>certificate_attribute_id</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>position</th>', $sectionMarkup);
    }

    public function test_title_page_surfaces_raw_certificate_rating_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieCertificate = MovieCertificate::query()
            ->select(['id', 'movie_id', 'certificate_rating_id', 'country_code', 'position'])
            ->with([
                'certificateRating:id,name',
                'movieCertificateAttributes' => fn ($movieCertificateAttributeQuery) => $movieCertificateAttributeQuery
                    ->select(['movie_certificate_id', 'certificate_attribute_id', 'position'])
                    ->with([
                        'certificateAttribute:id,name',
                    ])
                    ->orderBy('position'),
            ])
            ->whereHas('certificateRating', fn ($query) => $query->whereNotNull('name'))
            ->orderBy('id')
            ->first();

        if (! $movieCertificate instanceof MovieCertificate || ! $movieCertificate->certificateRating) {
            $this->markTestSkipped('The remote catalog does not currently expose a certificate rating sample.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieCertificate->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The remote catalog does not currently expose a published title with certificate ratings.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-certificate-ratings"')
            ->assertSeeHtml('data-slot="title-detail-certificate-rating-tabs"')
            ->assertSee('Certificate ratings linked to this title through its certificate records.')
            ->assertSee('By rating')
            ->assertSee('By title')
            ->assertSee($movieCertificate->certificateRating->resolvedLabel())
            ->assertSee($movieCertificate->certificateRating->shortDescription())
            ->assertSeeHtml('data-slot="certificate-rating-chip"')
            ->assertSee(route('public.certificate-ratings.show', $movieCertificate->certificateRating), false);

        $sectionMarkup = $this->sectionMarkup($response, 'title-detail-certificate-ratings');

        $this->assertStringContainsString('>Rating</th>', $sectionMarkup);
        $this->assertStringContainsString('>Countries on this title</th>', $sectionMarkup);
        $this->assertStringContainsString('>Attributes on this title</th>', $sectionMarkup);
        $this->assertStringContainsString('>Certificates on this title</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>id</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>name</th>', $sectionMarkup);

        $content = $response->getContent();

        $this->assertIsString($content);
        $this->assertMatchesRegularExpression(
            '/data-slot="title-detail-certificate-rating-tabs".*?data-name="title".*?>Meaning<\/th>.*?>Country<\/th>.*?>Attributes<\/th>/s',
            $content,
        );

        $linkedAttribute = $movieCertificate->movieCertificateAttributes
            ->map(fn ($movieCertificateAttribute) => $movieCertificateAttribute->certificateAttribute)
            ->first();

        if ($linkedAttribute !== null) {
            $response
                ->assertSee($linkedAttribute->name)
                ->assertSee(route('public.certificate-attributes.show', $linkedAttribute), false);
        }
    }

    public function test_title_page_surfaces_raw_movie_company_credits_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieCompanyCredit = MovieCompanyCredit::query()
            ->select(['id', 'movie_id', 'company_imdb_id', 'company_credit_category_id', 'start_year', 'end_year', 'position'])
            ->with([
                'company:imdb_id,name',
                'companyCreditCategory:id,name',
            ])
            ->whereHas('company', fn ($query) => $query->whereNotNull('name'))
            ->whereHas('companyCreditCategory', fn ($query) => $query->whereNotNull('name'))
            ->orderBy('movie_id')
            ->orderBy('position')
            ->first();

        if (! $movieCompanyCredit instanceof MovieCompanyCredit || ! $movieCompanyCredit->company || ! $movieCompanyCredit->companyCreditCategory) {
            $this->markTestSkipped('The remote catalog does not currently expose movie company credit rows with linked company metadata.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieCompanyCredit->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie company credit row is not linked to a published title page.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-movie-company-credits"')
            ->assertSee('Company credits linked directly to this title.')
            ->assertSee($movieCompanyCredit->company->name)
            ->assertSee($movieCompanyCredit->companyCreditCategory->name)
            ->assertSee(route('public.companies.show', $movieCompanyCredit->company), false);

        $sectionMarkup = $this->sectionMarkup($response, 'title-detail-movie-company-credits');

        $this->assertStringContainsString('>Company</th>', $sectionMarkup);
        $this->assertStringContainsString('>Category</th>', $sectionMarkup);
        $this->assertStringContainsString('>Start year</th>', $sectionMarkup);
        $this->assertStringContainsString('>End year</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>id</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>movie_id</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>company_imdb_id</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>company_credit_category_id</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>position</th>', $sectionMarkup);

        if ($movieCompanyCredit->start_year !== null) {
            $response->assertSee((string) $movieCompanyCredit->start_year);
        }

        if ($movieCompanyCredit->end_year !== null) {
            $response->assertSee((string) $movieCompanyCredit->end_year);
        }
    }

    public function test_title_page_surfaces_raw_movie_director_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieDirector = MovieDirector::query()
            ->select(['movie_id', 'name_basic_id', 'position'])
            ->with([
                'nameBasic:id,primaryname,displayName',
                'person' => fn ($personQuery) => $personQuery
                    ->selectDirectoryColumns()
                    ->withDirectoryRelations()
                    ->withDirectoryMetrics(),
            ])
            ->whereHas('person', fn ($query) => $query->whereNotNull('primaryname'))
            ->orderBy('movie_id')
            ->orderBy('position')
            ->first();

        if (! $movieDirector instanceof MovieDirector || ! $movieDirector->person) {
            $this->markTestSkipped('The remote catalog does not currently expose movie director rows with linked people.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieDirector->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie director row is not linked to a published title page.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-movie-directors"')
            ->assertSee('Directors linked directly to this title.')
            ->assertSeeHtml('data-slot="title-detail-director-link"')
            ->assertSee($movieDirector->person->name)
            ->assertSee(route('public.people.show', $movieDirector->person), false)
            ->assertSee(route('public.people.show', ['person' => $movieDirector->person, 'job' => 'Directing']), false)
            ->assertSee('Directed titles');

        $sectionMarkup = $this->sectionMarkup($response, 'title-detail-movie-directors');

        $this->assertStringContainsString($movieDirector->person->name, $sectionMarkup);
        $this->assertStringContainsString(route('public.people.show', $movieDirector->person), $sectionMarkup);

        if ($movieDirector->person->primaryImage_url !== null) {
            $response->assertSee($movieDirector->person->primaryImage_url);
        }
    }

    public function test_title_page_surfaces_raw_company_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieCompanyCredit = MovieCompanyCredit::query()
            ->select(['id', 'movie_id', 'company_imdb_id', 'position'])
            ->with([
                'company:imdb_id,name',
                'companyCreditCategory:id,name',
            ])
            ->whereHas('company', fn ($query) => $query->whereNotNull('name'))
            ->orderBy('movie_id')
            ->orderBy('position')
            ->first();

        if (! $movieCompanyCredit instanceof MovieCompanyCredit || ! $movieCompanyCredit->company) {
            $this->markTestSkipped('The remote catalog does not currently expose movie company credit rows with linked companies.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieCompanyCredit->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie company credit row is not linked to a published title page.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-companies"')
            ->assertSee('Companies connected to this title through its company credit records.')
            ->assertSee($movieCompanyCredit->company->name)
            ->assertSee(route('public.companies.show', $movieCompanyCredit->company), false)
            ->assertSee('Open company archive');

        $sectionMarkup = $this->sectionMarkup($response, 'title-detail-companies');

        $this->assertStringNotContainsString('>imdb_id</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>name</th>', $sectionMarkup);

        if ($movieCompanyCredit->companyCreditCategory?->name !== null) {
            $response->assertSee($movieCompanyCredit->companyCreditCategory->name);
        }
    }

    public function test_title_page_surfaces_raw_movie_company_credit_attribute_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieCompanyCreditAttribute = MovieCompanyCreditAttribute::query()
            ->select(['movie_company_credit_id', 'company_credit_attribute_id', 'position'])
            ->with([
                'companyCreditAttribute:id,name',
                'movieCompanyCredit:id,movie_id,company_imdb_id,company_credit_category_id,start_year,end_year',
                'movieCompanyCredit.company:imdb_id,name',
                'movieCompanyCredit.companyCreditCategory:id,name',
            ])
            ->whereHas('companyCreditAttribute', fn ($query) => $query->whereNotNull('name'))
            ->whereHas('movieCompanyCredit.company', fn ($query) => $query->whereNotNull('name'))
            ->whereHas('movieCompanyCredit.companyCreditCategory', fn ($query) => $query->whereNotNull('name'))
            ->orderBy('movie_company_credit_id')
            ->orderBy('position')
            ->first();

        if (! $movieCompanyCreditAttribute instanceof MovieCompanyCreditAttribute
            || ! $movieCompanyCreditAttribute->movieCompanyCredit
            || ! $movieCompanyCreditAttribute->movieCompanyCredit->company
            || ! $movieCompanyCreditAttribute->movieCompanyCredit->companyCreditCategory
            || ! $movieCompanyCreditAttribute->companyCreditAttribute) {
            $this->markTestSkipped('The remote catalog does not currently expose movie company credit attribute rows with linked company metadata.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieCompanyCreditAttribute->movieCompanyCredit->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie company credit attribute bridge row is not linked to a published title page.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-movie-company-credit-attributes"')
            ->assertSee($movieCompanyCreditAttribute->movieCompanyCredit->company->name)
            ->assertSee($movieCompanyCreditAttribute->movieCompanyCredit->companyCreditCategory->name)
            ->assertSee($movieCompanyCreditAttribute->companyCreditAttribute->name);

        $sectionMarkup = $this->sectionMarkup($response, 'title-detail-movie-company-credit-attributes');

        $this->assertStringContainsString('>Company</th>', $sectionMarkup);
        $this->assertStringContainsString('>Category</th>', $sectionMarkup);
        $this->assertStringContainsString('>Attribute</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>movie_company_credit_id</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>company_credit_attribute_id</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>position</th>', $sectionMarkup);
    }

    public function test_title_page_surfaces_raw_movie_company_credit_country_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieCompanyCreditCountry = MovieCompanyCreditCountry::query()
            ->select(['movie_company_credit_id', 'country_code', 'position'])
            ->with([
                'movieCompanyCredit:id,movie_id,company_imdb_id,company_credit_category_id,start_year,end_year',
                'movieCompanyCredit.company:imdb_id,name',
                'movieCompanyCredit.companyCreditCategory:id,name',
            ])
            ->whereNotNull('country_code')
            ->whereHas('movieCompanyCredit.company', fn ($query) => $query->whereNotNull('name'))
            ->whereHas('movieCompanyCredit.companyCreditCategory', fn ($query) => $query->whereNotNull('name'))
            ->orderBy('movie_company_credit_id')
            ->orderBy('position')
            ->first();

        if (! $movieCompanyCreditCountry instanceof MovieCompanyCreditCountry
            || ! $movieCompanyCreditCountry->movieCompanyCredit
            || ! $movieCompanyCreditCountry->movieCompanyCredit->company
            || ! $movieCompanyCreditCountry->movieCompanyCredit->companyCreditCategory) {
            $this->markTestSkipped('The remote catalog does not currently expose movie company credit country rows with linked company metadata.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieCompanyCreditCountry->movieCompanyCredit->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie company credit country bridge row is not linked to a published title page.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-movie-company-credit-countries"')
            ->assertSee($movieCompanyCreditCountry->movieCompanyCredit->company->name)
            ->assertSee($movieCompanyCreditCountry->movieCompanyCredit->companyCreditCategory->name);

        $sectionMarkup = $this->sectionMarkup($response, 'title-detail-movie-company-credit-countries');

        $this->assertStringContainsString('>Company</th>', $sectionMarkup);
        $this->assertStringContainsString('>Category</th>', $sectionMarkup);
        $this->assertStringContainsString('>Country</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>movie_company_credit_id</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>country_code</th>', $sectionMarkup);
        $this->assertStringNotContainsString('>position</th>', $sectionMarkup);

        if ($movieCompanyCreditCountry->country_code !== null) {
            $expectedCountryLabel = CountryCode::labelFor($movieCompanyCreditCountry->country_code) ?? $movieCompanyCreditCountry->country_code;

            $response
                ->assertSee($expectedCountryLabel)
                ->assertSeeHtml('data-flag-code="'.strtolower($movieCompanyCreditCountry->country_code).'"');

            $this->assertStringContainsString($expectedCountryLabel, $sectionMarkup);
        }
    }

    public function test_title_page_surfaces_raw_movie_company_credit_summary_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieCompanyCreditSummary = MovieCompanyCreditSummary::query()
            ->select(['movie_id', 'total_count', 'next_page_token'])
            ->orderBy('movie_id')
            ->first();

        if (! $movieCompanyCreditSummary instanceof MovieCompanyCreditSummary) {
            $this->markTestSkipped('The remote catalog does not currently expose movie company credit summary rows.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieCompanyCreditSummary->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie company credit summary row is not linked to a published title page.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-movie-company-credit-summaries"')
            ->assertSee('movie_id')
            ->assertSee('total_count')
            ->assertSee('next_page_token')
            ->assertSee((string) $movieCompanyCreditSummary->movie_id)
            ->assertSee((string) $movieCompanyCreditSummary->total_count);

        if ($movieCompanyCreditSummary->next_page_token !== null) {
            $response->assertSee($movieCompanyCreditSummary->next_page_token);
        }
    }

    public function test_title_page_surfaces_raw_movie_episode_summary_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieEpisodeSummary = MovieEpisodeSummary::query()
            ->select(['movie_id', 'total_count', 'next_page_token'])
            ->orderBy('movie_id')
            ->first();

        if (! $movieEpisodeSummary instanceof MovieEpisodeSummary) {
            $this->markTestSkipped('The remote catalog does not currently expose movie episode summary rows.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieEpisodeSummary->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie episode summary row is not linked to a published title page.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-movie-episode-summaries"')
            ->assertSee('movie_id')
            ->assertSee('total_count')
            ->assertSee('next_page_token')
            ->assertSee((string) $movieEpisodeSummary->movie_id)
            ->assertSee((string) $movieEpisodeSummary->total_count);

        if ($movieEpisodeSummary->next_page_token !== null) {
            $response->assertSee($movieEpisodeSummary->next_page_token);
        }
    }

    public function test_title_page_surfaces_raw_movie_episode_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieEpisode = MovieEpisode::query()
            ->select(['episode_movie_id', 'movie_id', 'season', 'episode_number', 'release_year', 'release_month', 'release_day'])
            ->orderBy('movie_id')
            ->orderBy('season')
            ->orderBy('episode_number')
            ->first();

        if (! $movieEpisode instanceof MovieEpisode) {
            $this->markTestSkipped('The remote catalog does not currently expose movie episode rows.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieEpisode->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie episode row is not linked to a published title page.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-movie-episodes"')
            ->assertSee('episode_movie_id')
            ->assertSee('movie_id')
            ->assertSee('season')
            ->assertSee('episode_number')
            ->assertSee('release_year')
            ->assertSee('release_month')
            ->assertSee('release_day')
            ->assertSee((string) $movieEpisode->episode_movie_id)
            ->assertSee((string) $movieEpisode->movie_id)
            ->assertSee((string) $movieEpisode->season)
            ->assertSee((string) $movieEpisode->episode_number);

        if ($movieEpisode->release_year !== null) {
            $response->assertSee((string) $movieEpisode->release_year);
        }

        if ($movieEpisode->release_month !== null) {
            $response->assertSee((string) $movieEpisode->release_month);
        }

        if ($movieEpisode->release_day !== null) {
            $response->assertSee((string) $movieEpisode->release_day);
        }
    }

    public function test_title_page_surfaces_humanized_genre_cards_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieGenre = MovieGenre::query()
            ->select(['movie_id', 'genre_id', 'position'])
            ->with([
                'genre:id,name',
            ])
            ->whereHas('genre', fn ($query) => $query->whereNotNull('name'))
            ->orderBy('movie_id')
            ->orderBy('position')
            ->first();

        if (! $movieGenre instanceof MovieGenre || ! $movieGenre->genre) {
            $this->markTestSkipped('The remote catalog does not currently expose movie genre rows with linked genre names.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieGenre->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie genre row is not linked to a published title page.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-genres"')
            ->assertSee('Genre lanes attached to this title. Open any genre to browse more titles in the existing archive.')
            ->assertSee($movieGenre->genre->name)
            ->assertSee('Open genre')
            ->assertSee(route('public.genres.show', $movieGenre->genre), false)
            ->assertDontSee('Genre links attached directly to this title.');
    }

    public function test_title_page_surfaces_raw_movie_image_summary_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieImageSummary = MovieImageSummary::query()
            ->select(['movie_id', 'total_count', 'next_page_token'])
            ->orderBy('movie_id')
            ->first();

        if (! $movieImageSummary instanceof MovieImageSummary) {
            $this->markTestSkipped('The remote catalog does not currently expose movie image summary rows.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieImageSummary->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie image summary row is not linked to a published title page.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-movie-image-summaries"')
            ->assertSee('movie_id')
            ->assertSee('total_count')
            ->assertSee('next_page_token')
            ->assertSee((string) $movieImageSummary->movie_id)
            ->assertSee((string) $movieImageSummary->total_count);

        if ($movieImageSummary->next_page_token !== null) {
            $response->assertSee($movieImageSummary->next_page_token);
        }
    }

    public function test_title_page_surfaces_raw_company_credit_attribute_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieCompanyCreditAttribute = MovieCompanyCreditAttribute::query()
            ->select(['movie_company_credit_id', 'company_credit_attribute_id', 'position'])
            ->with([
                'companyCreditAttribute:id,name',
                'movieCompanyCredit:id,movie_id,position',
            ])
            ->whereHas('companyCreditAttribute', fn ($query) => $query->whereNotNull('name'))
            ->orderBy('movie_company_credit_id')
            ->orderBy('position')
            ->first();

        if (! $movieCompanyCreditAttribute instanceof MovieCompanyCreditAttribute || ! $movieCompanyCreditAttribute->movieCompanyCredit || ! $movieCompanyCreditAttribute->companyCreditAttribute) {
            $this->markTestSkipped('The remote catalog does not currently expose movie company credit attribute rows with linked attributes.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieCompanyCreditAttribute->movieCompanyCredit->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie company credit attribute row is not linked to a published title page.');
        }

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-company-credit-attributes"')
            ->assertSee('id')
            ->assertSee('name')
            ->assertSee((string) $movieCompanyCreditAttribute->companyCreditAttribute->id)
            ->assertSee($movieCompanyCreditAttribute->companyCreditAttribute->name);
    }

    public function test_title_page_surfaces_raw_company_credit_category_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieCompanyCredit = MovieCompanyCredit::query()
            ->select(['id', 'movie_id', 'company_credit_category_id', 'position'])
            ->with([
                'companyCreditCategory:id,name',
            ])
            ->whereHas('companyCreditCategory', fn ($query) => $query->whereNotNull('name'))
            ->orderBy('movie_id')
            ->orderBy('position')
            ->first();

        if (! $movieCompanyCredit instanceof MovieCompanyCredit || ! $movieCompanyCredit->companyCreditCategory) {
            $this->markTestSkipped('The remote catalog does not currently expose movie company credit rows with linked categories.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieCompanyCredit->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie company credit category row is not linked to a published title page.');
        }

        $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-company-credit-categories"')
            ->assertSee('id')
            ->assertSee('name')
            ->assertSee((string) $movieCompanyCredit->companyCreditCategory->id)
            ->assertSee($movieCompanyCredit->companyCreditCategory->name);
    }

    public function test_title_page_surfaces_raw_country_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereHas('countries')
            ->orderBy('movies.id')
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The remote catalog does not currently expose a published title with countries.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-countries"')
            ->assertSee('code')
            ->assertSee('name');

        $country = $title->load([
            'countries:code,name',
        ])->countries->first();

        if ($country !== null) {
            $response->assertSee(strtoupper((string) $country->code));

            if ($country->name !== null) {
                $response->assertSee($country->name);
            }
        }
    }

    public function test_title_page_surfaces_raw_currency_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereHas('boxOfficeRecord', function ($query): void {
                $query
                    ->whereNotNull('production_budget_currency_code')
                    ->orWhereNotNull('domestic_gross_currency_code')
                    ->orWhereNotNull('opening_weekend_gross_currency_code')
                    ->orWhereNotNull('worldwide_gross_currency_code');
            })
            ->orderBy('movies.id')
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The remote catalog does not currently expose a published title with box office currencies.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-currencies"')
            ->assertSee('code');

        $title->load([
            'boxOfficeRecord:movie_id,production_budget_currency_code,domestic_gross_currency_code,opening_weekend_gross_currency_code,worldwide_gross_currency_code',
            'boxOfficeRecord.productionBudget:code',
            'boxOfficeRecord.domesticGross:code',
            'boxOfficeRecord.openingWeekendGross:code',
            'boxOfficeRecord.worldwideGross:code',
        ]);

        $currency = collect([
            $title->boxOfficeRecord?->productionBudget,
            $title->boxOfficeRecord?->domesticGross,
            $title->boxOfficeRecord?->openingWeekendGross,
            $title->boxOfficeRecord?->worldwideGross,
        ])->filter()->first();

        if ($currency !== null) {
            $response->assertSee(strtoupper((string) $currency->code));
        }
    }

    public function test_title_page_surfaces_raw_movie_box_office_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $movieBoxOffice = MovieBoxOffice::query()
            ->select([
                'movie_id',
                'domestic_gross_amount',
                'domestic_gross_currency_code',
                'worldwide_gross_amount',
                'worldwide_gross_currency_code',
                'opening_weekend_gross_amount',
                'opening_weekend_gross_currency_code',
                'opening_weekend_end_year',
                'opening_weekend_end_month',
                'opening_weekend_end_day',
                'production_budget_amount',
                'production_budget_currency_code',
            ])
            ->orderBy('movie_id')
            ->first();

        if (! $movieBoxOffice instanceof MovieBoxOffice) {
            $this->markTestSkipped('The remote catalog does not currently expose movie box office rows.');
        }

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereKey($movieBoxOffice->movie_id)
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The selected movie box office row is not linked to a published title page.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-movie-box-office"')
            ->assertSee('movie_id')
            ->assertSee('domestic_gross_amount')
            ->assertSee('domestic_gross_currency_code')
            ->assertSee('worldwide_gross_amount')
            ->assertSee('worldwide_gross_currency_code')
            ->assertSee('opening_weekend_gross_amount')
            ->assertSee('opening_weekend_gross_currency_code')
            ->assertSee('opening_weekend_end_year')
            ->assertSee('opening_weekend_end_month')
            ->assertSee('opening_weekend_end_day')
            ->assertSee('production_budget_amount')
            ->assertSee('production_budget_currency_code')
            ->assertSee((string) $movieBoxOffice->movie_id);

        foreach ([
            $movieBoxOffice->domestic_gross_amount,
            $movieBoxOffice->domestic_gross_currency_code,
            $movieBoxOffice->worldwide_gross_amount,
            $movieBoxOffice->worldwide_gross_currency_code,
            $movieBoxOffice->opening_weekend_gross_amount,
            $movieBoxOffice->opening_weekend_gross_currency_code,
            $movieBoxOffice->opening_weekend_end_year,
            $movieBoxOffice->opening_weekend_end_month,
            $movieBoxOffice->opening_weekend_end_day,
            $movieBoxOffice->production_budget_amount,
            $movieBoxOffice->production_budget_currency_code,
        ] as $value) {
            if ($value !== null) {
                $response->assertSee((string) $value);
            }
        }
    }

    public function test_title_page_surfaces_humanized_genre_archive_links_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereHas('genres')
            ->orderBy('movies.id')
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The remote catalog does not currently expose a published title with genres.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-genres"')
            ->assertSee('Open genre');

        $genre = $title->load([
            'genres:id,name',
        ])->genres->first();

        if ($genre !== null) {
            $response
                ->assertSee($genre->name)
                ->assertSee(route('public.genres.show', $genre), false);
        }
    }

    public function test_title_page_surfaces_humanized_interest_category_cards_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereHas('interests', fn ($query) => $query->whereHas('interestCategories'))
            ->orderBy('movies.id')
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The remote catalog does not currently expose a published title with interest categories.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-interest-categories"')
            ->assertSee('Discovery themes connected to this title through its imported interest graph.')
            ->assertSee('Open category');

        $interestCategory = $title->load([
            'interests:imdb_id,name,description,is_subgenre',
            'interests.interestCategoryInterests:interest_category_id,interest_imdb_id,position',
            'interests.interestCategoryInterests.interestCategory:id,name',
        ])->interests
            ->flatMap(fn ($interest) => $interest->interestCategoryInterests->map(fn ($interestCategoryInterest) => $interestCategoryInterest->interestCategory))
            ->filter()
            ->first();

        if ($interestCategory !== null) {
            $response
                ->assertSee($interestCategory->name)
                ->assertSee(route('public.interest-categories.show', $interestCategory), false);
        }
    }

    public function test_title_page_surfaces_raw_interest_primary_image_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereHas('interests', fn ($query) => $query->whereHas('interestPrimaryImages'))
            ->orderBy('movies.id')
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The remote catalog does not currently expose a published title with interest primary images.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-interest-primary-images"')
            ->assertSee('interest_imdb_id')
            ->assertSee('url')
            ->assertSee('width')
            ->assertSee('height')
            ->assertSee('type');

        $interestPrimaryImage = $title->load([
            'interests:imdb_id,name,description,is_subgenre',
            'interests.interestPrimaryImages:interest_imdb_id,url,width,height,type',
        ])->interests
            ->flatMap(fn ($interest) => $interest->interestPrimaryImages)
            ->filter()
            ->first();

        if ($interestPrimaryImage !== null) {
            $response
                ->assertSee($interestPrimaryImage->interest_imdb_id)
                ->assertSee($interestPrimaryImage->url);

            if ($interestPrimaryImage->width !== null) {
                $response->assertSee((string) $interestPrimaryImage->width);
            }

            if ($interestPrimaryImage->height !== null) {
                $response->assertSee((string) $interestPrimaryImage->height);
            }

            if ($interestPrimaryImage->type !== null) {
                $response->assertSee($interestPrimaryImage->type);
            }
        }
    }

    public function test_title_page_surfaces_raw_interest_similar_interest_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereHas('interests', fn ($query) => $query->whereHas('similarInterests'))
            ->orderBy('movies.id')
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The remote catalog does not currently expose a published title with similar interests.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-interest-similar-interests"')
            ->assertSee('interest_imdb_id')
            ->assertSee('similar_interest_imdb_id')
            ->assertSee('position');

        $interestSimilarInterest = $title->load([
            'interests:imdb_id,name,description,is_subgenre',
            'interests.interestSimilarInterests:interest_imdb_id,similar_interest_imdb_id,position',
        ])->interests
            ->flatMap(fn ($interest) => $interest->interestSimilarInterests)
            ->filter()
            ->first();

        if ($interestSimilarInterest !== null) {
            $response
                ->assertSee($interestSimilarInterest->interest_imdb_id)
                ->assertSee($interestSimilarInterest->similar_interest_imdb_id)
                ->assertSee((string) $interestSimilarInterest->position);
        }
    }

    public function test_title_page_surfaces_raw_interest_rows_when_available(): void
    {
        Livewire::withoutLazyLoading();

        $title = Title::query()
            ->select($this->remoteTitleColumns())
            ->publishedCatalog()
            ->whereHas('interests')
            ->orderBy('movies.id')
            ->first();

        if (! $title instanceof Title) {
            $this->markTestSkipped('The remote catalog does not currently expose a published title with interests.');
        }

        $response = $this->get(route('public.titles.show', $title))
            ->assertOk()
            ->assertSeeHtml('data-slot="title-detail-interests"')
            ->assertSee('imdb_id')
            ->assertSee('name')
            ->assertSee('description')
            ->assertSee('is_subgenre');

        $interest = $title->load([
            'interests:imdb_id,name,description,is_subgenre',
        ])->interests->first();

        if ($interest !== null) {
            $response
                ->assertSee($interest->imdb_id)
                ->assertSee($interest->name)
                ->assertSee((string) ((int) $interest->is_subgenre));

            if ($interest->description !== null) {
                $response->assertSee($interest->description);
            }
        }
    }

    private function sectionMarkup(TestResponse $response, string $slot): string
    {
        $content = $response->getContent();

        $this->assertIsString($content);

        $pattern = '/data-slot="'.preg_quote($slot, '/').'".*?<\/table>/s';

        $this->assertMatchesRegularExpression($pattern, $content);
        preg_match($pattern, $content, $matches);

        return $matches[0] ?? '';
    }
}
