<?php

namespace App\Models;

use App\Models\Concerns\HasCompositePrimaryKey;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovieCertificateAttribute extends ImdbModel
{
    use HasCompositePrimaryKey;

    protected $table = 'movie_certificate_attributes';

    protected $primaryKey = 'movie_certificate_id';

    public $incrementing = false;

    protected array $compositeKey = ['movie_certificate_id', 'certificate_attribute_id'];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'movie_certificate_id',
        'certificate_attribute_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'movie_certificate_id' => 'integer',
            'certificate_attribute_id' => 'integer',
            'position' => 'integer',
        ];
    }

    public function certificateAttribute(): BelongsTo
    {
        return $this->belongsTo(CertificateAttribute::class, 'certificate_attribute_id', 'id');
    }

    public function movieCertificate(): BelongsTo
    {
        return $this->belongsTo(MovieCertificate::class, 'movie_certificate_id', 'id');
    }
}
