<?php

namespace App\Livewire\Pages\Admin\Concerns;

use App\Models\Title;
use DateTimeInterface;

trait InteractsWithCatalogTitleState
{
    protected function optionalTitleAttribute(?Title $title, string $attribute, mixed $default = null): mixed
    {
        if (! $title instanceof Title) {
            return $default;
        }

        if (! array_key_exists($attribute, $title->getAttributes())) {
            return $default;
        }

        return $title->getAttributeValue($attribute);
    }

    protected function optionalTitleDateString(?Title $title, string $attribute): ?string
    {
        $value = $this->optionalTitleAttribute($title, $attribute);

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return is_string($value) && $value !== ''
            ? $value
            : null;
    }
}
