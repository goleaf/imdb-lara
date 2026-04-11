<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieParentsGuideSeverityBreakdown extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'movie_parents_guide_severity_breakdowns';

    protected $primaryKey = 'movie_parents_guide_section_id';

    public $incrementing = false;

    protected array $compositeKey = ['movie_parents_guide_section_id', 'parents_guide_severity_level_id'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_parents_guide_section_id',
        'parents_guide_severity_level_id',
        'vote_count',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'movie_parents_guide_section_id' => 'integer',
            'parents_guide_severity_level_id' => 'integer',
            'vote_count' => 'integer',
            'position' => 'integer',
        ];
    }

    public function parentsGuideSeverityLevel(): BelongsTo
    {
        return $this->belongsTo(ParentsGuideSeverityLevel::class, 'parents_guide_severity_level_id', 'id');
    }

    public function movieParentsGuideSection(): BelongsTo
    {
        return $this->belongsTo(MovieParentsGuideSection::class, 'movie_parents_guide_section_id', 'id');
    }
}
