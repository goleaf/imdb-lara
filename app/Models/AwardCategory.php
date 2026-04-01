<?php

namespace App\Models;

use Database\Factories\AwardCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AwardCategory extends Model
{
    /** @use HasFactory<AwardCategoryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'award_id',
        'name',
        'slug',
        'recipient_scope',
        'description',
    ];

    public function award(): BelongsTo
    {
        return $this->belongsTo(Award::class);
    }

    public function nominations(): HasMany
    {
        return $this->hasMany(AwardNomination::class);
    }
}
