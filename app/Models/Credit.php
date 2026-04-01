<?php

namespace App\Models;

use Database\Factories\CreditFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Credit extends Model
{
    /** @use HasFactory<CreditFactory> */
    use HasFactory;

    use SoftDeletes;

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
        'person_profession_id',
        'episode_id',
        'credited_as',
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

    public function profession(): BelongsTo
    {
        return $this->belongsTo(PersonProfession::class, 'person_profession_id');
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }
}
