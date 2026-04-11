<?php

namespace Tests\Unit\Models;

use App\Models\Person;
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
}
