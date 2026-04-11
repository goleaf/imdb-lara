<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ParentsGuideSeverityLevel extends ImdbModel
{
    protected $table = 'parents_guide_severity_levels';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
        ];
    }

    public function movieParentsGuideSeverityBreakdowns(): HasMany
    {
        return $this->hasMany(MovieParentsGuideSeverityBreakdown::class, 'parents_guide_severity_level_id', 'id');
    }
}
