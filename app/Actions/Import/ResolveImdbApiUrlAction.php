<?php

namespace App\Actions\Import;

use Illuminate\Support\Arr;

class ResolveImdbApiUrlAction
{
    /**
     * @param  array<string, string|int>  $parameters
     */
    public function handle(string $pathOrUrl, array $parameters = []): string
    {
        if (preg_match('/^https?:\/\//i', $pathOrUrl) === 1) {
            return $this->replacePlaceholders($pathOrUrl, $parameters);
        }

        $baseUrl = rtrim((string) config('services.imdb.base_url', 'https://api.imdbapi.dev'), '/');
        $path = '/'.ltrim($this->replacePlaceholders($pathOrUrl, $parameters), '/');

        return $baseUrl.$path;
    }

    public function endpoint(string $key): string
    {
        $endpoints = config('services.imdb.endpoints', []);

        if (! is_array($endpoints)) {
            return '';
        }

        $exactMatch = $endpoints[$key] ?? null;

        if (is_string($exactMatch) && trim($exactMatch) !== '') {
            return $exactMatch;
        }

        $nestedMatch = data_get($endpoints, $key);

        return is_string($nestedMatch) ? $nestedMatch : '';
    }

    /**
     * @param  array<string, string|int>  $parameters
     */
    private function replacePlaceholders(string $template, array $parameters): string
    {
        $resolved = $template;

        foreach (Arr::where($parameters, fn (mixed $value): bool => is_string($value) || is_int($value)) as $key => $value) {
            $resolved = str_replace('{'.$key.'}', (string) $value, $resolved);
        }

        return $resolved;
    }
}
