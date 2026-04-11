<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportrRun extends ImdbModel
{
    protected $table = 'importr_runs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'scope',
        'sample_limit',
        'status',
        'notes',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'sample_limit' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function importrRequests(): HasMany
    {
        return $this->hasMany(ImportrRequest::class, 'run_id', 'id');
    }
}
