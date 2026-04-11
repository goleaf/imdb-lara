<?php

namespace Tests\Unit\Search;

use App\Actions\Search\ResolveSearchTopMatchAction;
use App\Models\Person;
use App\Models\Title;
use PHPUnit\Framework\TestCase;

class ResolveSearchTopMatchActionTest extends TestCase
{
    public function test_exact_person_identifier_beats_a_fuzzy_title_match(): void
    {
        $person = new Person([
            'nconst' => 'nm0000123',
            'imdb_id' => 'nm0000123',
            'displayName' => 'Jane Doe',
        ]);
        $title = new Title([
            'tconst' => 'tt1234567',
            'imdb_id' => 'tt1234567',
            'primarytitle' => 'The Jane Doe Story',
        ]);

        $result = (new ResolveSearchTopMatchAction)->handle('nm0000123', $title, $person);

        $this->assertSame('person', $result['type']);
        $this->assertSame($person, $result['record']);
    }

    public function test_exact_person_name_beats_a_non_exact_title_match(): void
    {
        $person = new Person([
            'nconst' => 'nm0000456',
            'displayName' => 'Jane Doe',
        ]);
        $title = new Title([
            'tconst' => 'tt7654321',
            'primarytitle' => 'The Jane Doe Story',
        ]);

        $result = (new ResolveSearchTopMatchAction)->handle('Jane Doe', $title, $person);

        $this->assertSame('person', $result['type']);
        $this->assertSame($person, $result['record']);
    }

    public function test_exact_title_identifier_beats_a_person_fallback(): void
    {
        $person = new Person([
            'nconst' => 'nm0000999',
            'displayName' => 'Jane Doe',
        ]);
        $title = new Title([
            'tconst' => 'tt0000123',
            'imdb_id' => 'tt0000123',
            'primarytitle' => 'Jane Doe',
        ]);

        $result = (new ResolveSearchTopMatchAction)->handle('tt0000123', $title, $person);

        $this->assertSame('title', $result['type']);
        $this->assertSame($title, $result['record']);
    }

    public function test_generic_overlap_keeps_title_first_fallback(): void
    {
        $person = new Person([
            'nconst' => 'nm0000456',
            'displayName' => 'Jane Doe',
        ]);
        $title = new Title([
            'tconst' => 'tt7654321',
            'primarytitle' => 'The Jane Doe Story',
        ]);

        $result = (new ResolveSearchTopMatchAction)->handle('Jane', $title, $person);

        $this->assertSame('title', $result['type']);
        $this->assertSame($title, $result['record']);
    }
}
