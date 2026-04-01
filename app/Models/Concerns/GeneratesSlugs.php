<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait GeneratesSlugs
{
    public static function bootGeneratesSlugs(): void
    {
        static::creating(function ($model): void {
            if (! filled($model->slug)) {
                $model->slug = $model->generateUniqueSlug();
            }
        });
    }

    public function generateUniqueSlug(): string
    {
        $baseSlug = Str::slug((string) $this->getSlugSourceValue());

        if ($baseSlug === '') {
            $baseSlug = Str::random(10);
        }

        $slug = $baseSlug;
        $suffix = 2;

        while (static::query()
            ->where('slug', $slug)
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
            ->exists()) {
            $slug = sprintf('%s-%d', $baseSlug, $suffix);
            $suffix++;
        }

        return $slug;
    }

    protected function getSlugSourceValue(): mixed
    {
        return $this->{property_exists($this, 'slugSourceColumn') ? $this->slugSourceColumn : 'name'};
    }
}
