<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TitleCrewDirector extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'title_crew_directors';

    protected $primaryKey = 'title_crew_id';

    public $incrementing = false;

    protected array $compositeKey = ['title_crew_id', 'name_basic_id'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title_crew_id',
        'name_basic_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'title_crew_id' => 'integer',
            'name_basic_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function nameBasic(): BelongsTo
    {
        return $this->belongsTo(NameBasic::class, 'name_basic_id', 'id');
    }

    public function titleCrew(): BelongsTo
    {
        return $this->belongsTo(TitleCrew::class, 'title_crew_id', 'id');
    }
}
