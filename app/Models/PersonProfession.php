<?php

namespace App\Models;

use App\Models\Profession as ImdbProfession;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonProfession extends Model
{
    protected $connection = 'imdb_mysql';

    protected $table = 'name_basic_professions';

    protected $primaryKey = 'name_basic_id';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name_basic_id',
        'profession_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'name_basic_id' => 'integer',
            'profession_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'name_basic_id', 'id');
    }

    public function professionTerm(): BelongsTo
    {
        return $this->belongsTo(ImdbProfession::class, 'profession_id', 'id');
    }

    public function getIdAttribute(): string
    {
        return $this->name_basic_id.':'.$this->profession_id;
    }

    public function getPersonIdAttribute(): int
    {
        return (int) $this->name_basic_id;
    }

    public function getDepartmentAttribute(): ?string
    {
        return $this->profession;
    }

    public function getProfessionAttribute(): ?string
    {
        if ($this->relationLoaded('professionTerm')) {
            return $this->professionTerm?->name;
        }

        return null;
    }

    public function getIsPrimaryAttribute(): bool
    {
        return (int) ($this->position ?? 0) === 1;
    }

    public function getSortOrderAttribute(): int
    {
        return (int) ($this->position ?? 0);
    }
}
