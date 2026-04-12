<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatabaseMigrationState extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'source_driver',
        'source_host',
        'source_database',
        'table_name',
        'cursor_columns',
        'last_cursor',
        'rows_copied',
        'status',
        'last_error',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'cursor_columns' => 'array',
            'last_cursor' => 'array',
            'rows_copied' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
