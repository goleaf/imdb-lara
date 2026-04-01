<?php

namespace App\Models;

use App\Models\Concerns\GeneratesSlugs;
use Database\Factories\GenreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genre extends Model
{
    /** @use HasFactory<GenreFactory> */
    use GeneratesSlugs;
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function titles(): BelongsToMany
    {
        return $this->belongsToMany(Title::class)->withTimestamps();
    }
}
