<?php

namespace App\Models;

use App\TitleRelationshipType;
use Database\Factories\TitleRelationshipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TitleRelationship extends Model
{
    /** @use HasFactory<TitleRelationshipFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'from_title_id',
        'to_title_id',
        'relationship_type',
        'weight',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'relationship_type' => TitleRelationshipType::class,
        ];
    }

    public function fromTitle(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'from_title_id');
    }

    public function toTitle(): BelongsTo
    {
        return $this->belongsTo(Title::class, 'to_title_id');
    }
}
