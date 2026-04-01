<?php

namespace App\Actions\Search;

use App\Enums\TitleType;
use App\Models\Genre;
use App\Models\Title;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class GetSearchFilterOptionsAction
{
    /**
     * @return array{
     *     countries: list<array{value: string, label: string}>,
     *     genres: Collection<int, Genre>,
     *     languages: list<array{value: string, label: string}>,
     *     runtimeOptions: list<array{value: string, label: string}>,
     *     sortOptions: list<array{value: string, label: string}>,
     *     statusOptions: list<array{value: string, label: string}>,
     *     titleTypes: list<TitleType>,
     *     voteThresholdOptions: list<array{value: string, label: string}>,
     *     years: list<int>
     * }
     */
    public function handle(): array
    {
        return Cache::remember(
            'search:filter-options:'.app()->getLocale(),
            now()->addMinutes(10),
            function (): array {
                $publishedTitles = Title::query()->published();
                $minimumYear = (clone $publishedTitles)->whereNotNull('release_year')->min('release_year');
                $maximumYear = (clone $publishedTitles)->whereNotNull('release_year')->max('release_year');

                return [
                    'countries' => Title::query()
                        ->published()
                        ->whereNotNull('origin_country')
                        ->distinct()
                        ->orderBy('origin_country')
                        ->pluck('origin_country')
                        ->filter()
                        ->map(fn (string $country): array => [
                            'value' => $country,
                            'label' => $this->countryLabel($country),
                        ])
                        ->values()
                        ->all(),
                    'genres' => Genre::query()
                        ->select(['id', 'name', 'slug'])
                        ->orderBy('name')
                        ->get(),
                    'languages' => Title::query()
                        ->published()
                        ->whereNotNull('original_language')
                        ->distinct()
                        ->orderBy('original_language')
                        ->pluck('original_language')
                        ->filter()
                        ->map(fn (string $language): array => [
                            'value' => $language,
                            'label' => $this->languageLabel($language),
                        ])
                        ->values()
                        ->all(),
                    'runtimeOptions' => [
                        ['value' => 'under-30', 'label' => 'Under 30 min'],
                        ['value' => '30-60', 'label' => '30 to 60 min'],
                        ['value' => '60-90', 'label' => '60 to 90 min'],
                        ['value' => '90-120', 'label' => '90 to 120 min'],
                        ['value' => '120-plus', 'label' => '120+ min'],
                    ],
                    'sortOptions' => [
                        ['value' => 'popular', 'label' => 'Popularity'],
                        ['value' => 'trending', 'label' => 'Trending'],
                        ['value' => 'rating', 'label' => 'Rating'],
                        ['value' => 'latest', 'label' => 'Latest release'],
                        ['value' => 'year', 'label' => 'Year'],
                        ['value' => 'name', 'label' => 'Name'],
                    ],
                    'statusOptions' => [
                        ['value' => 'returning', 'label' => 'Returning series'],
                        ['value' => 'ended', 'label' => 'Ended'],
                        ['value' => 'limited', 'label' => 'Limited series'],
                        ['value' => 'upcoming', 'label' => 'Upcoming'],
                    ],
                    'titleTypes' => TitleType::cases(),
                    'voteThresholdOptions' => [
                        ['value' => '10', 'label' => '10+ votes'],
                        ['value' => '50', 'label' => '50+ votes'],
                        ['value' => '100', 'label' => '100+ votes'],
                        ['value' => '500', 'label' => '500+ votes'],
                        ['value' => '1000', 'label' => '1,000+ votes'],
                    ],
                    'years' => $minimumYear && $maximumYear
                        ? range((int) $maximumYear, (int) $minimumYear)
                        : [],
                ];
            },
        );
    }

    private function countryLabel(string $country): string
    {
        if (class_exists(\Locale::class)) {
            $label = \Locale::getDisplayRegion('-'.$country, app()->getLocale());

            if (filled($label)) {
                return $label;
            }
        }

        return strtoupper($country);
    }

    private function languageLabel(string $language): string
    {
        if (class_exists(\Locale::class)) {
            $label = \Locale::getDisplayLanguage($language, app()->getLocale());

            if (filled($label)) {
                return str($label)->headline()->value();
            }
        }

        return strtoupper($language);
    }
}
