<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportrNode extends ImdbModel
{
    protected $table = 'importr_nodes';

    public $timestamps = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'request_id',
        'parent_node_id',
        'node_path',
        'parent_path',
        'field_name',
        'array_index',
        'node_type',
        'scalar_type',
        'scalar_value',
        'scalar_boolean',
        'referenced_imdb_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'request_id' => 'integer',
            'parent_node_id' => 'integer',
            'array_index' => 'integer',
            'scalar_boolean' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ImportrRequest::class, 'request_id', 'id');
    }
}
