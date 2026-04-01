<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSlugs;
use Database\Factories\AwardEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AwardEvent extends Model
{
    /** @use HasFactory<AwardEventFactory> */
    use GeneratesSlugs;

    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'award_id',
        'name',
        'slug',
        'year',
        'edition',
        'event_date',
        'location',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function award(): BelongsTo
    {
        return $this->belongsTo(Award::class);
    }

    public function nominations(): HasMany
    {
        return $this->hasMany(AwardNomination::class);
    }
}
