<?php

namespace App\Models;

use Database\Factories\CreditFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Credit extends Model
{
    /** @use HasFactory<CreditFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title_id',
        'person_id',
        'department',
        'job',
        'character_name',
        'billing_order',
        'is_principal',
    ];

    protected function casts(): array
    {
        return [
            'is_principal' => 'boolean',
        ];
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
