<?php

namespace App\Models\Concerns;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Schema;
use Throwable;

trait DetectsCatalogSchemaAvailability
{
    /** @var array<string, bool> */
    private static array $catalogTablePresenceCache = [];

    protected static function catalogModeEnabled(): bool
    {
        $container = Container::getInstance();

        if (! $container instanceof Container || ! $container->bound('config')) {
            return false;
        }

        $config = $container->make('config');

        if ((bool) $config->get('screenbase.catalog_only', false)) {
            return true;
        }

        return $config->get('database.default') === 'imdb_mysql';
    }

    protected static function catalogSchemaHasTables(string ...$tables): bool
    {
        $normalizedTables = array_values(array_unique(array_filter($tables)));

        if ($normalizedTables === []) {
            return false;
        }

        sort($normalizedTables);
        $cacheKey = implode('|', $normalizedTables);

        if (array_key_exists($cacheKey, static::$catalogTablePresenceCache)) {
            return static::$catalogTablePresenceCache[$cacheKey];
        }

        try {
            $schema = Schema::connection('imdb_mysql');

            foreach ($normalizedTables as $table) {
                if (! $schema->hasTable($table)) {
                    return static::$catalogTablePresenceCache[$cacheKey] = false;
                }
            }

            return static::$catalogTablePresenceCache[$cacheKey] = true;
        } catch (Throwable) {
            return static::$catalogTablePresenceCache[$cacheKey] = false;
        }
    }

    protected static function shouldUseCatalogOnlySchema(string ...$requiredTables): bool
    {
        if (! static::catalogModeEnabled()) {
            return false;
        }

        return static::catalogSchemaHasTables(...$requiredTables);
    }

    public static function clearCatalogSchemaPresenceCache(): void
    {
        static::$catalogTablePresenceCache = [];
    }
}
