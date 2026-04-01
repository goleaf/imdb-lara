<?php

namespace App\Models;

use Database\Factories\ImdbTitleImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImdbTitleImport extends Model
{
    /** @use HasFactory<ImdbTitleImportFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'imdb_id',
        'source_url',
        'storage_path',
        'payload_hash',
        'payload',
        'downloaded_at',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'downloaded_at' => 'datetime',
            'imported_at' => 'datetime',
        ];
    }
}
