<?php

use App\Actions\Seo\GetSitemapDataAction;
use App\Enums\TitleMediaArchiveKind;
use App\Livewire\Pages\Public\AkaAttributePage;
use App\Livewire\Pages\Public\AwardNominationPage;
use App\Livewire\Pages\Public\AwardsPage;
use App\Livewire\Pages\Public\CatalogExplorerPage;
use App\Livewire\Pages\Public\CertificateAttributePage;
use App\Livewire\Pages\Public\CertificateRatingPage;
use App\Livewire\Pages\Public\ChangesPage;
use App\Livewire\Pages\Public\CompanyCreditAttributePage;
use App\Livewire\Pages\Public\CompanyPage;
use App\Livewire\Pages\Public\DiscoverPage;
use App\Livewire\Pages\Public\EpisodeShowPage;
use App\Livewire\Pages\Public\GenreBrowsePage;
use App\Livewire\Pages\Public\HomePage;
use App\Livewire\Pages\Public\InterestCategoriesPage;
use App\Livewire\Pages\Public\LatestReviewsPage;
use App\Livewire\Pages\Public\LatestTrailersPage;
use App\Livewire\Pages\Public\ListsPage;
use App\Livewire\Pages\Public\MoviesBrowsePage;
use App\Livewire\Pages\Public\PeoplePage;
use App\Livewire\Pages\Public\SearchPage;
use App\Livewire\Pages\Public\SeasonShowPage;
use App\Livewire\Pages\Public\SeriesBrowsePage;
use App\Livewire\Pages\Public\TitleBoxOfficePage;
use App\Livewire\Pages\Public\TitleCastPage;
use App\Livewire\Pages\Public\TitleMediaArchivePage;
use App\Livewire\Pages\Public\TitleMediaPage;
use App\Livewire\Pages\Public\TitleMetadataPage;
use App\Livewire\Pages\Public\TitlePage;
use App\Livewire\Pages\Public\TitleParentsGuidePage;
use App\Livewire\Pages\Public\TitlesBrowsePage;
use App\Livewire\Pages\Public\TitleTriviaPage;
use App\Livewire\Pages\Public\TopRatedMoviesPage;
use App\Livewire\Pages\Public\TopRatedSeriesPage;
use App\Livewire\Pages\Public\TrendingPage;
use App\Livewire\Pages\Public\UserPage;
use App\Livewire\Pages\Public\YearBrowsePage;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', function (GetSitemapDataAction $getSitemapData): Response {
    return response()
        ->view('seo.sitemap', $getSitemapData->handle())
        ->header('Content-Type', 'application/xml; charset=UTF-8');
})->name('sitemap');

Route::get('/robots.txt', fn () => response(
    "User-agent: *\nAllow: /\n\nSitemap: ".route('sitemap')."\n",
    200,
    ['Content-Type' => 'text/plain; charset=UTF-8'],
))->name('robots');

Route::name('public.')->group(function (): void {
    Route::livewire('/', HomePage::class)->name('home');
    Route::livewire('/discover', DiscoverPage::class)->name('discover');
    Route::livewire('/catalog/{section?}', CatalogExplorerPage::class)
        ->whereIn('section', ['titles', 'people', 'themes'])
        ->name('catalog.explorer');
    Route::livewire('/movies', MoviesBrowsePage::class)->name('movies.index');
    Route::livewire('/tv-shows', SeriesBrowsePage::class)->name('series.index');
    Route::livewire('/titles', TitlesBrowsePage::class)->name('titles.index');
    Route::livewire('/titles/{title:slug}', TitlePage::class)->name('titles.show');
    Route::livewire('/titles/{title:slug}/cast', TitleCastPage::class)->name('titles.cast');
    Route::livewire('/titles/{title:slug}/media', TitleMediaPage::class)->name('titles.media');
    Route::livewire('/titles/{title:slug}/media/{archive}', TitleMediaArchivePage::class)
        ->whereIn('archive', TitleMediaArchiveKind::values())
        ->name('titles.media.archive');
    Route::livewire('/titles/{title:slug}/box-office', TitleBoxOfficePage::class)->name('titles.box-office');
    Route::livewire('/titles/{title:slug}/parents-guide', TitleParentsGuidePage::class)->name('titles.parents-guide');
    Route::livewire('/titles/{title:slug}/trivia', TitleTriviaPage::class)->name('titles.trivia');
    Route::livewire('/titles/{title:slug}/metadata', TitleMetadataPage::class)->name('titles.metadata');
    Route::livewire('/people', PeoplePage::class)->name('people.index');
    Route::livewire('/people/{person:slug}', PeoplePage::class)->name('people.show');
    Route::livewire('/companies/{company:slug}', CompanyPage::class)->name('companies.show');
    Route::livewire('/company-credit-attributes/{companyCreditAttribute:slug}', CompanyCreditAttributePage::class)->name('company-credit-attributes.show');
    Route::livewire('/interest-categories', InterestCategoriesPage::class)->name('interest-categories.index');
    Route::livewire('/interest-categories/{interestCategory:slug}', InterestCategoriesPage::class)->name('interest-categories.show');
    Route::livewire('/awards', AwardsPage::class)->name('awards.index');
    Route::livewire('/awards/nominations/{awardNomination:slug}', AwardNominationPage::class)->name('awards.nominations.show');
    Route::livewire('/aka-attributes/{akaAttribute:slug}', AkaAttributePage::class)->name('aka-attributes.show');
    Route::livewire('/certificate-ratings/{certificateRating:slug}', CertificateRatingPage::class)->name('certificate-ratings.show');
    Route::livewire('/certificate-attributes/{certificateAttribute:slug}', CertificateAttributePage::class)->name('certificate-attributes.show');
    Route::livewire('/reviews', LatestReviewsPage::class)->name('reviews.latest');
    Route::livewire('/lists', ListsPage::class)->name('lists.index');
    Route::livewire('/users/{user:username}', UserPage::class)->name('users.show');
    Route::livewire('/users/{user:username}/lists/{list:slug}', UserPage::class)->name('lists.show');
    Route::livewire('/trailers', LatestTrailersPage::class)->name('trailers.latest');
    Route::livewire('/genres/{genre:slug}', GenreBrowsePage::class)->name('genres.show');
    Route::livewire('/years/{year}', YearBrowsePage::class)->whereNumber('year')->name('years.show');
    Route::livewire('/top-rated/movies', TopRatedMoviesPage::class)->name('rankings.movies');
    Route::livewire('/top-rated/series', TopRatedSeriesPage::class)->name('rankings.series');
    Route::livewire('/trending', TrendingPage::class)->name('trending');
    Route::livewire('/search', SearchPage::class)->name('search');
    Route::livewire('/changes', ChangesPage::class)->name('changes');

    Route::withoutScopedBindings()->group(function (): void {
        Route::livewire('/series/{series:slug}/seasons/{season:slug}', SeasonShowPage::class)->name('seasons.show');
        Route::livewire('/series/{series:slug}/seasons/{season:slug}/episodes/{episode:slug}', EpisodeShowPage::class)->name('episodes.show');
    });
});

require __DIR__.'/auth.php';
require __DIR__.'/account.php';
require __DIR__.'/admin.php';
