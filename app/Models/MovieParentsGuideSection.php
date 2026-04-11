<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MovieParentsGuideSection extends ImdbModel
{
    protected $table = 'movie_parents_guide_sections';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_id',
        'parents_guide_category_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'movie_id' => 'integer',
            'parents_guide_category_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function parentsGuideCategory(): BelongsTo
    {
        return $this->belongsTo(ParentsGuideCategory::class, 'parents_guide_category_id', 'id');
    }

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class, 'movie_id', 'id');
    }

    public function movieParentsGuideReviews(): HasMany
    {
        return $this->hasMany(MovieParentsGuideReview::class, 'movie_parents_guide_section_id', 'id');
    }

    public function movieParentsGuideSeverityBreakdowns(): HasMany
    {
        return $this->hasMany(MovieParentsGuideSeverityBreakdown::class, 'movie_parents_guide_section_id', 'id');
    }
}
