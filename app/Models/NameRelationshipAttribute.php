<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NameRelationshipAttribute extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'name_relationship_attributes';

    protected $primaryKey = 'name_relationship_id';

    public $incrementing = false;

    protected array $compositeKey = ['name_relationship_id', 'relation_attribute_id'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name_relationship_id',
        'relation_attribute_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'name_relationship_id' => 'integer',
            'relation_attribute_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function relationAttribute(): BelongsTo
    {
        return $this->belongsTo(RelationAttribute::class, 'relation_attribute_id', 'id');
    }

    public function nameRelationship(): BelongsTo
    {
        return $this->belongsTo(NameRelationship::class, 'name_relationship_id', 'id');
    }
}
