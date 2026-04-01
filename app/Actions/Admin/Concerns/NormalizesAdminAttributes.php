<?php

namespace App\Actions\Admin\Concerns;

trait NormalizesAdminAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    protected function normalizeAttributes(array $attributes): array
    {
        return collect($attributes)
            ->map(function (mixed $value): mixed {
                if (! is_string($value)) {
                    return $value;
                }

                $trimmed = trim($value);

                return $trimmed === '' ? null : $trimmed;
            })
            ->all();
    }
}
