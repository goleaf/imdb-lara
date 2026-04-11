<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class RelationAttribute extends ImdbModel
{
    protected $table = 'relation_attributes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
        ];
    }

    public function nameRelationshipAttributes(): HasMany
    {
        return $this->hasMany(NameRelationshipAttribute::class, 'relation_attribute_id', 'id');
    }
}
