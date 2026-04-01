<?php

namespace App\Actions\Admin;

use App\Actions\Admin\Concerns\NormalizesAdminAttributes;
use App\Enums\TitleType;
use App\Models\Episode;
use App\Models\Season;
use App\Models\Title;

class SaveEpisodeAction
{
    use NormalizesAdminAttributes;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Episode $episode, Season $season, array $attributes): Episode
    {
        $attributes = $this->normalizeAttributes($attributes);

        $titleAttributes = [
            'name' => $attributes['name'],
            'original_name' => $attributes['original_name'] ?? null,
            'slug' => $attributes['slug'],
            'sort_title' => $attributes['name'],
            'title_type' => TitleType::Episode,
            'release_year' => $attributes['release_year'] ?? null,
            'release_date' => $attributes['release_date'] ?? null,
            'runtime_minutes' => $attributes['runtime_minutes'] ?? null,
            'age_rating' => $attributes['age_rating'] ?? null,
            'plot_outline' => $attributes['plot_outline'] ?? null,
            'synopsis' => $attributes['synopsis'] ?? null,
            'tagline' => $attributes['tagline'] ?? null,
            'origin_country' => $attributes['origin_country'] ?? null,
            'original_language' => $attributes['original_language'] ?? null,
            'meta_title' => $attributes['meta_title'] ?? null,
            'meta_description' => $attributes['meta_description'] ?? null,
            'search_keywords' => $attributes['search_keywords'] ?? null,
            'is_published' => (bool) ($attributes['is_published'] ?? false),
        ];

        $title = $episode->exists && $episode->title
            ? tap($episode->title)->fill($titleAttributes)
            : new Title($titleAttributes);

        $title->save();

        $episode->fill([
            'title_id' => $title->id,
            'series_id' => $season->series_id,
            'season_id' => $season->id,
            'season_number' => $attributes['season_number'] ?? $season->season_number,
            'episode_number' => $attributes['episode_number'] ?? null,
            'absolute_number' => $attributes['absolute_number'] ?? null,
            'production_code' => $attributes['production_code'] ?? null,
            'aired_at' => $attributes['aired_at'] ?? null,
        ]);
        $episode->save();

        return $episode->refresh()->load(['title', 'season', 'series']);
    }
}
