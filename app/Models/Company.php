<?php

namespace App\Models;

use App\CompanyKind;
use App\Models\Concerns\GeneratesSlugs;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use GeneratesSlugs;
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'kind',
        'country_code',
        'description',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'kind' => CompanyKind::class,
            'is_published' => 'boolean',
        ];
    }

    public function titles(): BelongsToMany
    {
        return $this->belongsToMany(Title::class)
            ->withPivot('relationship')
            ->withTimestamps();
    }
}
