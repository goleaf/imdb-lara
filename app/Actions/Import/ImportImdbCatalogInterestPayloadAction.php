<?php

namespace App\Actions\Import;

use App\Models\Interest;
use App\Models\InterestCategory;
use App\Models\InterestCategoryInterest;
use App\Models\InterestPrimaryImage;
use App\Models\InterestSimilarInterest;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportImdbCatalogInterestPayloadAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): Interest
    {
        $interestPayload = is_array(data_get($payload, 'interest')) ? data_get($payload, 'interest') : $payload;

        if (! is_array($interestPayload)) {
            throw new RuntimeException('The IMDb interest payload is missing the interest object.');
        }

        return DB::connection('imdb_mysql')->transaction(function () use ($interestPayload): Interest {
            $interest = $this->upsertInterest($interestPayload);

            $this->syncPrimaryImage($interest, is_array(data_get($interestPayload, 'primaryImage')) ? data_get($interestPayload, 'primaryImage') : []);
            $this->syncSimilarInterests($interest, $this->normalizeObjectList(data_get($interestPayload, 'similarInterests')));

            return $interest->fresh() ?? $interest;
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleFrontier(array $payload): int
    {
        $categoryPayloads = $this->normalizeObjectList(data_get($payload, 'categories'));

        if ($categoryPayloads === []) {
            return 0;
        }

        return DB::connection('imdb_mysql')->transaction(function () use ($categoryPayloads): int {
            $bridges = [];
            $seenCategoryIds = [];

            foreach ($categoryPayloads as $categoryPayload) {
                $categoryName = $this->nullableString(data_get($categoryPayload, 'category'));

                if ($categoryName === null) {
                    continue;
                }

                $category = InterestCategory::query()->firstOrCreate(['name' => $categoryName]);
                $seenCategoryIds[] = (int) $category->getKey();

                foreach ($this->normalizeObjectList(data_get($categoryPayload, 'interests')) as $interestIndex => $interestPayload) {
                    $interest = $this->upsertInterest($interestPayload);
                    $this->syncPrimaryImage($interest, is_array(data_get($interestPayload, 'primaryImage')) ? data_get($interestPayload, 'primaryImage') : []);

                    $bridges[$category->getKey().'|'.$interest->getKey()] = [
                        'interest_category_id' => $category->getKey(),
                        'interest_imdb_id' => $interest->getKey(),
                        'position' => $interestIndex + 1,
                    ];
                }
            }

            InterestCategoryInterest::query()->delete();

            if ($bridges !== []) {
                InterestCategoryInterest::query()->insert(array_values($bridges));
            }

            if ($seenCategoryIds !== []) {
                InterestCategory::query()
                    ->whereNotIn('id', array_values(array_unique($seenCategoryIds)))
                    ->delete();
            }

            return count($bridges);
        });
    }

    /**
     * @param  array<string, mixed>  $interestPayload
     */
    private function upsertInterest(array $interestPayload): Interest
    {
        $imdbId = $this->nullableString(data_get($interestPayload, 'id'));

        if ($imdbId === null) {
            throw new RuntimeException('The IMDb interest payload is missing an id.');
        }

        $interest = Interest::query()->firstOrNew(['imdb_id' => $imdbId]);
        $interest->fill([
            'imdb_id' => $imdbId,
            'name' => $this->nullableString(data_get($interestPayload, 'name')),
            'description' => $this->nullableString(data_get($interestPayload, 'description')),
            'is_subgenre' => $this->nullableBool(data_get($interestPayload, 'isSubgenre')) ?? false,
        ]);
        $interest->save();

        return $interest;
    }

    /**
     * @param  array<string, mixed>  $primaryImagePayload
     */
    private function syncPrimaryImage(Interest $interest, array $primaryImagePayload): void
    {
        $url = $this->nullableString(data_get($primaryImagePayload, 'url'));

        if ($url === null) {
            return;
        }

        InterestPrimaryImage::query()->updateOrCreate(
            ['interest_imdb_id' => $interest->getKey()],
            [
                'url' => $url,
                'width' => $this->nullableInt(data_get($primaryImagePayload, 'width')),
                'height' => $this->nullableInt(data_get($primaryImagePayload, 'height')),
                'type' => $this->nullableString(data_get($primaryImagePayload, 'type')) ?? 'primary',
            ],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $similarInterests
     */
    private function syncSimilarInterests(Interest $interest, array $similarInterests): void
    {
        InterestSimilarInterest::query()->where('interest_imdb_id', $interest->getKey())->delete();

        foreach ($similarInterests as $index => $similarInterestPayload) {
            $similarInterest = $this->upsertInterest($similarInterestPayload);

            InterestSimilarInterest::query()->create([
                'interest_imdb_id' => $interest->getKey(),
                'similar_interest_imdb_id' => $similarInterest->getKey(),
                'position' => $index + 1,
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function normalizeObjectList(mixed $value): array
    {
        if (! is_iterable($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            if (is_array($item)) {
                $items[] = $item;
            }
        }

        return array_values($items);
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function nullableBool(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            return match (strtolower(trim($value))) {
                '1', 'true', 'yes' => true,
                '0', 'false', 'no' => false,
                default => null,
            };
        }

        return null;
    }
}
