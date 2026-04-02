<?php

namespace App\Models;

use Database\Factories\AwardNominationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use JsonException;

class AwardNomination extends Model
{
    /** @use HasFactory<AwardNominationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'award_event_id',
        'award_category_id',
        'title_id',
        'person_id',
        'company_id',
        'episode_id',
        'credited_name',
        'details',
        'is_winner',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_winner' => 'boolean',
        ];
    }

    public function awardEvent(): BelongsTo
    {
        return $this->belongsTo(AwardEvent::class);
    }

    public function awardCategory(): BelongsTo
    {
        return $this->belongsTo(AwardCategory::class);
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function detailsPayload(): ?array
    {
        if (! is_string($this->details) || trim($this->details) === '') {
            return null;
        }

        try {
            $payload = json_decode($this->details, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($payload) ? $payload : null;
    }

    public function detailSummary(): ?string
    {
        $text = data_get($this->detailsPayload(), 'text');

        if (is_string($text) && trim($text) !== '') {
            return trim($text);
        }

        return is_string($this->details) && trim($this->details) !== ''
            ? trim($this->details)
            : null;
    }
}
