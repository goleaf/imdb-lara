<?php

namespace App\Actions\Import\Concerns;

use Illuminate\Database\Eloquent\Model;

trait BatchesCatalogImportLookups
{
    /**
     * @var array<string, array<string, Model>>
     */
    private array $batchedLookupCache = [];

    private function resetBatchedLookupCache(): void
    {
        $this->batchedLookupCache = [];
    }

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @param  array<string, array<string, mixed>>  $rowsByKey
     * @return array<string, TModel>
     */
    private function batchLookupModels(string $modelClass, string $matchColumn, array $rowsByKey): array
    {
        $rowsByKey = $this->filterLookupRows($rowsByKey);

        if ($rowsByKey === []) {
            return [];
        }

        $cacheKey = $modelClass.'|'.$matchColumn;
        $cached = $this->batchedLookupCache[$cacheKey] ?? [];
        $missingKeys = array_values(array_diff(array_keys($rowsByKey), array_keys($cached)));

        if ($missingKeys !== []) {
            $existing = $modelClass::query()
                ->whereIn($matchColumn, $missingKeys)
                ->get()
                ->keyBy(fn (Model $model): string => (string) $model->getAttribute($matchColumn))
                ->all();

            $missingRows = array_diff_key(
                array_intersect_key($rowsByKey, array_flip($missingKeys)),
                $existing,
            );

            if ($missingRows !== []) {
                $modelClass::query()->insertOrIgnore(array_values($missingRows));

                $inserted = $modelClass::query()
                    ->whereIn($matchColumn, array_keys($missingRows))
                    ->get()
                    ->keyBy(fn (Model $model): string => (string) $model->getAttribute($matchColumn))
                    ->all();

                $existing = array_replace($existing, $inserted);
            }

            $cached = array_replace($cached, $existing);
            $this->batchedLookupCache[$cacheKey] = $cached;
        }

        return array_intersect_key($cached, $rowsByKey);
    }

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $modelClass
     * @param  array<string, array<string, mixed>>  $rowsByKey
     * @param  list<string>  $updateColumns
     * @return array<string, TModel>
     */
    private function batchUpsertModels(string $modelClass, string $matchColumn, array $rowsByKey, array $updateColumns): array
    {
        $rowsByKey = $this->filterLookupRows($rowsByKey);

        if ($rowsByKey === []) {
            return [];
        }

        $modelClass::query()->upsert(array_values($rowsByKey), [$matchColumn], $updateColumns);

        $loaded = $modelClass::query()
            ->whereIn($matchColumn, array_keys($rowsByKey))
            ->get()
            ->keyBy(fn (Model $model): string => (string) $model->getAttribute($matchColumn))
            ->all();

        $cacheKey = $modelClass.'|'.$matchColumn;
        $this->batchedLookupCache[$cacheKey] = array_replace(
            $this->batchedLookupCache[$cacheKey] ?? [],
            $loaded,
        );

        return array_intersect_key($this->batchedLookupCache[$cacheKey], $rowsByKey);
    }

    /**
     * @param  array<string, array<string, mixed>>  $rowsByKey
     * @return array<string, array<string, mixed>>
     */
    private function filterLookupRows(array $rowsByKey): array
    {
        return array_filter(
            $rowsByKey,
            static fn (array $row, string $key): bool => $key !== '',
            ARRAY_FILTER_USE_BOTH,
        );
    }
}
