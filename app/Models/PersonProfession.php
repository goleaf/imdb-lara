<?php

namespace App\Models;

use Database\Factories\PersonProfessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PersonProfession extends Model
{
    /** @use HasFactory<PersonProfessionFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'person_id',
        'department',
        'profession',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }
}
