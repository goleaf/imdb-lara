<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NameBasic extends ImdbModel
{
    protected $table = 'name_basics';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'nconst',
        'primaryname',
        'birthyear',
        'deathyear',
        'primaryprofession',
        'knownfortitles',
        'alternativeNames',
        'biography',
        'birthDate_day',
        'birthDate_month',
        'birthDate_year',
        'birthLocation',
        'birthName',
        'deathDate_day',
        'deathDate_month',
        'deathDate_year',
        'deathLocation',
        'deathReason',
        'displayName',
        'heightCm',
        'imdb_id',
        'primaryImage_height',
        'primaryImage_url',
        'primaryImage_width',
        'primaryProfessions',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'birthyear' => 'integer',
            'deathyear' => 'integer',
            'birthDate_day' => 'integer',
            'birthDate_month' => 'integer',
            'birthDate_year' => 'integer',
            'deathDate_day' => 'integer',
            'deathDate_month' => 'integer',
            'deathDate_year' => 'integer',
            'heightCm' => 'integer',
            'primaryImage_height' => 'integer',
            'primaryImage_width' => 'integer',
        ];
    }

    public function movieAwardNominationNominees(): HasMany
    {
        return $this->hasMany(MovieAwardNominationNominee::class, 'name_basic_id', 'id');
    }

    public function movieDirectors(): HasMany
    {
        return $this->hasMany(MovieDirector::class, 'name_basic_id', 'id');
    }

    public function movieStars(): HasMany
    {
        return $this->hasMany(MovieStar::class, 'name_basic_id', 'id');
    }

    public function movieWriters(): HasMany
    {
        return $this->hasMany(MovieWriter::class, 'name_basic_id', 'id');
    }

    public function nameBasicAlternativeNames(): HasMany
    {
        return $this->hasMany(NameBasicAlternativeName::class, 'name_basic_id', 'id');
    }

    public function nameBasicKnownForTitles(): HasMany
    {
        return $this->hasMany(NameBasicKnownForTitle::class, 'name_basic_id', 'id');
    }

    public function nameBasicMeterRankings(): HasMany
    {
        return $this->hasMany(NameBasicMeterRanking::class, 'name_basic_id', 'id');
    }

    public function nameBasicPrimaryImages(): HasMany
    {
        return $this->hasMany(NameBasicPrimaryImage::class, 'name_basic_id', 'id');
    }

    public function nameBasicProfessions(): HasMany
    {
        return $this->hasMany(NameBasicProfession::class, 'name_basic_id', 'id');
    }

    public function professions(): BelongsToMany
    {
        return $this->belongsToMany(Profession::class, 'name_basic_professions', 'name_basic_id', 'profession_id', 'id', 'id');
    }

    public function nameCredits(): HasMany
    {
        return $this->hasMany(NameCredit::class, 'name_basic_id', 'id');
    }

    public function nameCreditSummaries(): HasMany
    {
        return $this->hasMany(NameCreditSummary::class, 'name_basic_id', 'id');
    }

    public function nameImages(): HasMany
    {
        return $this->hasMany(NameImage::class, 'name_basic_id', 'id');
    }

    public function nameImageSummaries(): HasMany
    {
        return $this->hasMany(NameImageSummary::class, 'name_basic_id', 'id');
    }

    public function nameRelationships(): HasMany
    {
        return $this->hasMany(NameRelationship::class, 'name_basic_id', 'id');
    }

    public function relatedNameRelationships(): HasMany
    {
        return $this->hasMany(NameRelationship::class, 'related_name_basic_id', 'id');
    }

    public function nameTriviaEntries(): HasMany
    {
        return $this->hasMany(NameTriviaEntry::class, 'name_basic_id', 'id');
    }

    public function nameTriviaSummaries(): HasMany
    {
        return $this->hasMany(NameTriviaSummary::class, 'name_basic_id', 'id');
    }

    public function titleCrewDirectors(): HasMany
    {
        return $this->hasMany(TitleCrewDirector::class, 'name_basic_id', 'id');
    }

    public function titleCrewWriters(): HasMany
    {
        return $this->hasMany(TitleCrewWriter::class, 'name_basic_id', 'id');
    }

    public function titlePrincipals(): HasMany
    {
        return $this->hasMany(TitlePrincipal::class, 'nconst', 'nconst');
    }
}
