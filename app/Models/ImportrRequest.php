<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportrRequest extends ImdbModel
{
    protected $table = 'importr_requests';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'run_id',
        'operation_id',
        'endpoint_path',
        'method',
        'request_key',
        'external_id',
        'page_token',
        'request_url',
        'http_status',
        'response_file_path',
        'response_sha1',
        'response_bytes',
        'error_message',
        'fetched_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'run_id' => 'integer',
            'http_status' => 'integer',
            'response_bytes' => 'integer',
            'fetched_at' => 'datetime',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(ImportrRun::class, 'run_id', 'id');
    }

    public function importrNodes(): HasMany
    {
        return $this->hasMany(ImportrNode::class, 'request_id', 'id');
    }
}
