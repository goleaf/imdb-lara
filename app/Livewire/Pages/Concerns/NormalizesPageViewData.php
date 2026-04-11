<?php

namespace App\Livewire\Pages\Concerns;

use Illuminate\Support\Collection;

trait NormalizesPageViewData
{
    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $keys
     * @return array<string, mixed>
     */
    protected function withCollectionDefaults(array $data, array $keys): array
    {
        foreach ($keys as $key) {
            if (! (($data[$key] ?? null) instanceof Collection)) {
                $data[$key] = collect();
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    protected function withDefaultValues(array $data, array $defaults): array
    {
        foreach ($defaults as $key => $value) {
            if (! array_key_exists($key, $data) || $data[$key] === null) {
                $data[$key] = $value;
            }
        }

        return $data;
    }
}
