<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSlugs;
use Database\Factories\AwardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Award extends Model
{
    /** @use HasFactory<AwardFactory> */
    use GeneratesSlugs;

    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'country_code',
        'meta_title',
        'meta_description',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function events(): HasMany
    {
        return $this->hasMany(AwardEvent::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(AwardCategory::class);
    }
}
