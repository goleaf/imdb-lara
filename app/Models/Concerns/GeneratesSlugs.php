<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait GeneratesSlugs
{
    public static function bootGeneratesSlugs(): void
    {
        static::saving(function ($model): void {
            if (! filled($model->slug)) {
                $model->slug = $model->generateUniqueSlug();

                return;
            }

            if ($model->isDirty('slug')) {
                $model->slug = $model->generateUniqueSlug((string) $model->slug);
            }
        });
    }

    public function generateUniqueSlug(?string $source = null): string
    {
        $baseSlug = $this->normalizeSlug($source);

        if ($baseSlug === '') {
            $baseSlug = Str::random(10);
        }

        $slug = $baseSlug;
        $suffix = 2;

        while ($this->slugConflictQuery($slug)->exists()) {
            $slug = sprintf('%s-%d', $baseSlug, $suffix);
            $suffix++;
        }

        return $slug;
    }

    protected function slugConflictQuery(string $slug): Builder
    {
        return static::query()
            ->where('slug', $slug)
            ->when($this->exists, fn (Builder $query) => $query->whereKeyNot($this->getKey()));
    }

    protected function normalizeSlug(?string $source = null): string
    {
        return Str::slug((string) ($source ?? $this->getSlugSourceValue()));
    }

    protected function getSlugSourceValue(): mixed
    {
        return $this->{property_exists($this, 'slugSourceColumn') ? $this->slugSourceColumn : 'name'};
    }
}
