<?php

namespace App\Livewire\Pages\Admin\Concerns;

use App\Models\Person;
use DateTimeInterface;

trait InteractsWithCatalogPersonState
{
    protected function optionalPersonAttribute(?Person $person, string $attribute, mixed $default = null): mixed
    {
        if (! $person instanceof Person) {
            return $default;
        }

        if (! array_key_exists($attribute, $person->getAttributes())) {
            return $default;
        }

        return $person->getAttributeValue($attribute);
    }

    protected function optionalPersonDateString(?Person $person, string $attribute): ?string
    {
        $value = $this->optionalPersonAttribute($person, $attribute);

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return is_string($value) && $value !== ''
            ? $value
            : null;
    }
}
