<?php

namespace App\Models;

use Database\Factories\PersonProfessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
            'person_id' => 'integer',
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
