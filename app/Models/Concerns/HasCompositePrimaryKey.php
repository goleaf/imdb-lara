<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasCompositePrimaryKey
{
    protected function setKeysForSaveQuery($query): Builder
    {
        foreach ($this->compositeKeyColumns() as $keyColumn) {
            $query->where($keyColumn, $this->getAttribute($keyColumn));
        }

        return $query;
    }

    /**
     * @return list<string>
     */
    protected function compositeKeyColumns(): array
    {
        $columns = $this->compositeKey ?? [];

        return is_array($columns) ? array_values($columns) : [];
    }
}
