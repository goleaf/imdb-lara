<?php

namespace App\Models;

use App\Enums\CompanyKind;
use App\Models\Concerns\GeneratesSlugs;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use GeneratesSlugs;

    use HasFactory;
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'kind',
        'country_code',
        'description',
        'meta_title',
        'meta_description',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'kind' => CompanyKind::class,
            'is_published' => 'boolean',
        ];
    }

    public function titles(): BelongsToMany
    {
        return $this->belongsToMany(Title::class)
            ->withPivot('relationship', 'credited_as', 'is_primary', 'sort_order')
            ->withTimestamps();
    }

    public function awardNominations(): HasMany
    {
        return $this->hasMany(AwardNomination::class);
    }

    public function contributions(): MorphMany
    {
        return $this->morphMany(Contribution::class, 'contributable');
    }
}
