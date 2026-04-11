<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class NameRelationType extends ImdbModel
{
    protected $table = 'name_relation_types';

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

    public function nameRelationships(): HasMany
    {
        return $this->hasMany(NameRelationship::class, 'name_relation_type_id', 'id');
    }
}
