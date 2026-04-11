<?php

namespace Tests\Unit\Models;

use App\Models\MediaAsset;
use App\Models\Person;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\Concerns\UsesCatalogOnlyApplication;
use Tests\TestCase;

class PersonTest extends TestCase
{
    use UsesCatalogOnlyApplication;

    public function test_primary_profession_label_falls_back_without_recursing(): void
    {
        $person = new Person;

        $this->assertSame('Screenbase profile', $person->primaryProfessionLabel());
    }

    public function test_known_for_department_is_derived_from_primary_professions(): void
    {
        $person = new Person;
        $person->setRawAttributes([
            'primaryProfessions' => json_encode(['actor'], JSON_THROW_ON_ERROR),
        ], sync: true);

        $this->assertSame('Actor', $person->primaryProfessionLabel());
        $this->assertSame('Cast', $person->known_for_department);
    }

    public function test_summary_text_prefers_loaded_short_biography_attribute(): void
    {
        $person = new Person;
        $person->setRawAttributes([
            'short_biography' => 'A concise profile.',
            'biography' => 'A longer biography that should not be used first.',
        ], sync: true);

        $this->assertSame('A concise profile.', $person->summaryText());
    }

    public function test_alternate_names_fall_back_to_imdb_payload_without_recursing(): void
    {
        $person = new Person;
        $person->setRawAttributes([
            'imdb_alternative_names' => json_encode(['A. Stone', 'Ava Marie Stone'], JSON_THROW_ON_ERROR),
        ], sync: true);

        $this->assertSame(['A. Stone', 'Ava Marie Stone'], $person->resolvedAlternateNames());
        $this->assertSame('A. Stone | Ava Marie Stone', $person->alternate_names);
    }

    public function test_preferred_headshot_tolerates_partial_loaded_media_assets(): void
    {
        $headshot = new MediaAsset;
        $headshot->forceFill([
            'mediable_type' => Person::class,
            'mediable_id' => 1,
            'kind' => 'headshot',
            'url' => 'https://cdn.example.com/ava-mercer.jpg',
            'is_primary' => true,
        ]);
        $headshot->exists = true;

        $person = new Person;
        $person->forceFill([
            'id' => 1,
            'name' => 'Ava Mercer',
        ]);
        $person->exists = true;
        $person->setRelation('mediaAssets', new EloquentCollection([$headshot]));

        $preferredHeadshot = $person->preferredHeadshot();

        $this->assertNotNull($preferredHeadshot);
        $this->assertSame('https://cdn.example.com/ava-mercer.jpg', $preferredHeadshot->url);
        $this->assertNull($preferredHeadshot->caption);
        $this->assertTrue($preferredHeadshot->is_primary);
    }
}
