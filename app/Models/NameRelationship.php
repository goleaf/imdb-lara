<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NameRelationship extends ImdbModel
{
    protected $table = 'name_relationships';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name_basic_id',
        'related_name_basic_id',
        'name_relation_type_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'name_basic_id' => 'integer',
            'related_name_basic_id' => 'integer',
            'name_relation_type_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function nameBasic(): BelongsTo
    {
        return $this->belongsTo(NameBasic::class, 'name_basic_id', 'id');
    }

    public function related(): BelongsTo
    {
        return $this->belongsTo(NameBasic::class, 'related_name_basic_id', 'id');
    }

    public function nameRelationType(): BelongsTo
    {
        return $this->belongsTo(NameRelationType::class, 'name_relation_type_id', 'id');
    }

    public function nameRelationshipAttributes(): HasMany
    {
        return $this->hasMany(NameRelationshipAttribute::class, 'name_relationship_id', 'id');
    }
}
