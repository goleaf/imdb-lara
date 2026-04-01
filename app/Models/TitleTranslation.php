<?php

namespace App\Models;

use Database\Factories\TitleTranslationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TitleTranslation extends Model
{
    /** @use HasFactory<TitleTranslationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title_id',
        'locale',
        'localized_title',
        'localized_slug',
        'localized_plot_outline',
        'localized_synopsis',
        'localized_tagline',
        'meta_title',
        'meta_description',
    ];

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }
}
