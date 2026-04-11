<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieParentsGuideReview extends ImdbModel
{
    protected $table = 'movie_parents_guide_reviews';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_parents_guide_section_id',
        'text',
        'is_spoiler',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'movie_parents_guide_section_id' => 'integer',
            'is_spoiler' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function movieParentsGuideSection(): BelongsTo
    {
        return $this->belongsTo(MovieParentsGuideSection::class, 'movie_parents_guide_section_id', 'id');
    }
}
