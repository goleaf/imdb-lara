<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ParentsGuideCategory extends ImdbModel
{
    protected $table = 'parents_guide_categories';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
        ];
    }

    public function movieParentsGuideSections(): HasMany
    {
        return $this->hasMany(MovieParentsGuideSection::class, 'parents_guide_category_id', 'id');
    }
}
