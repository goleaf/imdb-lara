<?php

namespace App\Actions\Search;

use App\Models\Person;
use App\Models\Title;
use Illuminate\Support\Str;

class ResolveSearchTopMatchAction
{
    /**
     * @return array{record: Person|Title|null, type: 'person'|'title'|null}
     */
    public function handle(string $searchQuery, ?Title $topTitleMatch, ?Person $topPersonMatch): array
    {
        $trimmedQuery = trim($searchQuery);

        if ($trimmedQuery === '') {
            return $this->fallback($topTitleMatch, $topPersonMatch);
        }

        if ($this->looksLikePersonIdentifier($trimmedQuery)
            && $topPersonMatch instanceof Person
            && $this->matchesPersonIdentifier($trimmedQuery, $topPersonMatch)) {
            return ['record' => $topPersonMatch, 'type' => 'person'];
        }

        if ($this->looksLikeTitleIdentifier($trimmedQuery)
            && $topTitleMatch instanceof Title
            && $this->matchesTitleIdentifier($trimmedQuery, $topTitleMatch)) {
            return ['record' => $topTitleMatch, 'type' => 'title'];
        }

        $personExactMatch = $topPersonMatch instanceof Person
            && $this->matchesExactPersonQuery($trimmedQuery, $topPersonMatch);
        $titleExactMatch = $topTitleMatch instanceof Title
            && $this->matchesExactTitleQuery($trimmedQuery, $topTitleMatch);

        if ($personExactMatch && ! $titleExactMatch) {
            return ['record' => $topPersonMatch, 'type' => 'person'];
        }

        if ($titleExactMatch) {
            return ['record' => $topTitleMatch, 'type' => 'title'];
        }

        return $this->fallback($topTitleMatch, $topPersonMatch);
    }

    /**
     * @return array{record: Person|Title|null, type: 'person'|'title'|null}
     */
    private function fallback(?Title $topTitleMatch, ?Person $topPersonMatch): array
    {
        if ($topTitleMatch instanceof Title) {
            return ['record' => $topTitleMatch, 'type' => 'title'];
        }

        if ($topPersonMatch instanceof Person) {
            return ['record' => $topPersonMatch, 'type' => 'person'];
        }

        return ['record' => null, 'type' => null];
    }

    private function looksLikePersonIdentifier(string $searchQuery): bool
    {
        return preg_match('/^nm\d+$/i', $searchQuery) === 1;
    }

    private function looksLikeTitleIdentifier(string $searchQuery): bool
    {
        return preg_match('/^tt\d+$/i', $searchQuery) === 1;
    }

    private function matchesPersonIdentifier(string $searchQuery, Person $person): bool
    {
        $normalizedQuery = $this->normalize($searchQuery);

        return collect([$person->nconst, $person->imdb_id])
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->contains(fn (string $value): bool => $this->normalize($value) === $normalizedQuery);
    }

    private function matchesTitleIdentifier(string $searchQuery, Title $title): bool
    {
        $normalizedQuery = $this->normalize($searchQuery);

        return collect([$title->tconst, $title->imdb_id])
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->contains(fn (string $value): bool => $this->normalize($value) === $normalizedQuery);
    }

    private function matchesExactPersonQuery(string $searchQuery, Person $person): bool
    {
        $normalizedQuery = $this->normalize($searchQuery);

        return $this->matchesPersonIdentifier($searchQuery, $person)
            || collect([$person->name, $person->displayName, $person->primaryname])
                ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
                ->contains(fn (string $value): bool => $this->normalize($value) === $normalizedQuery);
    }

    private function matchesExactTitleQuery(string $searchQuery, Title $title): bool
    {
        $normalizedQuery = $this->normalize($searchQuery);

        return $this->matchesTitleIdentifier($searchQuery, $title)
            || collect([$title->name, $title->primarytitle, $title->originaltitle])
                ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
                ->contains(fn (string $value): bool => $this->normalize($value) === $normalizedQuery);
    }

    private function normalize(string $value): string
    {
        return Str::of($value)->squish()->lower()->toString();
    }
}
