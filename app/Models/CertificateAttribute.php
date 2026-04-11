<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateAttribute extends ImdbModel
{
    protected $table = 'certificate_attributes';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
        ];
    }

    public function movieCertificateAttributes(): HasMany
    {
        return $this->hasMany(MovieCertificateAttribute::class, 'certificate_attribute_id', 'id');
    }
}
