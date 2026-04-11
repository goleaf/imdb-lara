<?php

namespace App\Actions\Database;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;

class GenerateSchemaModelsAction
{
    /**
     * @param  list<string>  $tables
     * @return array{generated: list<string>, skipped: list<string>, output_path: string}
     */
    public function handle(
        string $connection,
        string $outputPath,
        string $namespace,
        array $tables = [],
        bool $force = false,
    ): array {
        $namespace = $this->normalizeNamespace($namespace);
        $schema = Schema::connection($connection);
        $availableTables = collect($schema->getTables())
            ->pluck('name')
            ->map(fn (mixed $table): string => (string) $table)
            ->values();

        $selectedTables = $tables === []
            ? $availableTables
            : collect($tables)
                ->map(fn (string $table): string => trim($table))
                ->filter()
                ->unique()
                ->values();

        $missingTables = $selectedTables
            ->reject(fn (string $table): bool => $availableTables->contains($table))
            ->values();

        if ($missingTables->isNotEmpty()) {
            throw new InvalidArgumentException('Unknown tables: '.$missingTables->implode(', '));
        }

        /** @var array<string, string> $classMap */
        $classMap = $selectedTables
            ->mapWithKeys(fn (string $table): array => [$table => $this->classNameForTable($table)])
            ->all();

        $duplicateClasses = collect(array_count_values($classMap))
            ->filter(fn (int $count): bool => $count > 1)
            ->keys()
            ->values();

        if ($duplicateClasses->isNotEmpty()) {
            throw new InvalidArgumentException('Duplicate class names detected: '.$duplicateClasses->implode(', '));
        }

        /** @var array<string, array{columns: list<array<string, mixed>>, indexes: list<array<string, mixed>>, foreign_keys: list<array<string, mixed>>}> $tableMetadata */
        $tableMetadata = $selectedTables
            ->mapWithKeys(fn (string $table): array => [
                $table => [
                    'columns' => $schema->getColumns($table),
                    'indexes' => $schema->getIndexes($table),
                    'foreign_keys' => $schema->getForeignKeys($table),
                ],
            ])
            ->all();

        /** @var array<string, list<array{table: string, column: string, foreign_column: string, unique: bool}>> $incomingRelations */
        $incomingRelations = [];
        /** @var array<string, list<array{related_class: string, pivot_table: string, foreign_pivot_key: string, related_pivot_key: string, parent_key: string, related_key: string, method_base: string}>> $manyToManyRelations */
        $manyToManyRelations = $this->detectBelongsToManyRelations(
            tableMetadata: $tableMetadata,
            classMap: $classMap,
        );

        foreach ($tableMetadata as $table => $metadata) {
            foreach ($metadata['foreign_keys'] as $foreignKey) {
                $foreignTable = (string) $foreignKey['foreign_table'];
                $columns = $this->normalizeColumns($foreignKey['columns'] ?? []);
                $foreignColumns = $this->normalizeColumns($foreignKey['foreign_columns'] ?? []);
                $column = $columns[0] ?? null;
                $foreignColumn = $foreignColumns[0] ?? null;

                if (
                    $column === null
                    || $foreignColumn === null
                    || count($columns) !== 1
                    || count($foreignColumns) !== 1
                    || ! array_key_exists($foreignTable, $classMap)
                ) {
                    continue;
                }

                $incomingRelations[$foreignTable] ??= [];
                $incomingRelations[$foreignTable][] = [
                    'table' => $table,
                    'column' => $column,
                    'foreign_column' => $foreignColumn,
                    'unique' => $this->columnsAreUniquelyIndexed($metadata['indexes'], $columns),
                ];
            }
        }

        $resolvedOutputPath = $this->resolveOutputPath($outputPath);
        File::ensureDirectoryExists($resolvedOutputPath);

        $generated = [];
        $skipped = [];

        foreach ($selectedTables as $table) {
            $className = $classMap[$table];
            $filePath = $resolvedOutputPath.'/'.$className.'.php';

            if (File::exists($filePath) && ! $force) {
                $skipped[] = $table;

                continue;
            }

            File::put(
                $filePath,
                $this->renderModel(
                    table: $table,
                    className: $className,
                    namespace: $namespace,
                    metadata: $tableMetadata[$table],
                    classMap: $classMap,
                    incomingRelations: $incomingRelations[$table] ?? [],
                    manyToManyRelations: $manyToManyRelations[$table] ?? [],
                ),
            );

            $generated[] = $table;
        }

        return [
            'generated' => $generated,
            'skipped' => $skipped,
            'output_path' => $resolvedOutputPath,
        ];
    }

    private function resolveOutputPath(string $outputPath): string
    {
        if (Str::startsWith($outputPath, DIRECTORY_SEPARATOR)) {
            return $outputPath;
        }

        return base_path($outputPath);
    }

    private function normalizeNamespace(string $namespace): string
    {
        $normalizedNamespace = trim($namespace);

        while (str_contains($normalizedNamespace, '\\\\')) {
            $normalizedNamespace = str_replace('\\\\', '\\', $normalizedNamespace);
        }

        return trim($normalizedNamespace, '\\');
    }

    private function classNameForTable(string $table): string
    {
        return Str::studly(Str::singular($table));
    }

    /**
     * @param  array{columns: list<array<string, mixed>>, indexes: list<array<string, mixed>>, foreign_keys: list<array<string, mixed>>}  $metadata
     * @param  array<string, string>  $classMap
     * @param  list<array{table: string, column: string, foreign_column: string, unique: bool}>  $incomingRelations
     * @param  list<array{related_class: string, pivot_table: string, foreign_pivot_key: string, related_pivot_key: string, parent_key: string, related_key: string, method_base: string}>  $manyToManyRelations
     */
    private function renderModel(
        string $table,
        string $className,
        string $namespace,
        array $metadata,
        array $classMap,
        array $incomingRelations,
        array $manyToManyRelations,
    ): string {
        $columns = collect($metadata['columns']);
        $indexes = collect($metadata['indexes']);
        $foreignKeys = collect($metadata['foreign_keys']);
        $primaryIndex = $indexes->firstWhere('primary', true);
        $primaryColumns = collect($primaryIndex['columns'] ?? [])
            ->map(fn (mixed $column): string => (string) $column)
            ->values();
        $primaryColumn = $primaryColumns->first();
        $currentClassSnake = Str::snake($className);

        /** @var list<string> $fillableColumns */
        $fillableColumns = $columns
            ->reject(fn (array $column): bool => (bool) ($column['auto_increment'] ?? false))
            ->pluck('name')
            ->map(fn (mixed $column): string => (string) $column)
            ->values()
            ->all();

        /** @var array<string, string> $casts */
        $casts = $columns
            ->mapWithKeys(function (array $column): array {
                $cast = $this->castForColumn($column);

                return $cast === null ? [] : [(string) $column['name'] => $cast];
            })
            ->all();

        $timestamps = $columns->contains(fn (array $column): bool => in_array($column['name'], ['created_at', 'updated_at'], true));
        $usesCompositePrimaryKey = $primaryColumns->count() > 1;
        $primaryColumnMetadata = $primaryColumn === null
            ? null
            : $columns->firstWhere('name', $primaryColumn);
        $isStringPrimaryKey = is_array($primaryColumnMetadata)
            && in_array((string) $primaryColumnMetadata['type_name'], ['char', 'varchar', 'text', 'mediumtext', 'longtext'], true);
        $isIncrementingPrimaryKey = is_array($primaryColumnMetadata) && (bool) ($primaryColumnMetadata['auto_increment'] ?? false);

        $imports = collect(['App\Models\ImdbModel']);
        $methodNames = [];
        $relationMethods = [];

        foreach ($foreignKeys as $foreignKey) {
            $foreignTable = (string) $foreignKey['foreign_table'];
            $columns = $this->normalizeColumns($foreignKey['columns'] ?? []);
            $foreignColumns = $this->normalizeColumns($foreignKey['foreign_columns'] ?? []);
            $column = $columns[0] ?? null;
            $foreignColumn = $foreignColumns[0] ?? null;

            if (
                $column === null
                || $foreignColumn === null
                || count($columns) !== 1
                || count($foreignColumns) !== 1
                || ! array_key_exists($foreignTable, $classMap)
            ) {
                continue;
            }

            $relatedClass = $classMap[$foreignTable];
            $methodName = $this->ensureUniqueMethodName(
                $methodNames,
                Str::camel($this->relationBaseName($column, $foreignColumn, $relatedClass)),
            );

            $imports->push('Illuminate\Database\Eloquent\Relations\BelongsTo');
            $relationMethods[] = <<<PHP
    public function {$methodName}(): BelongsTo
    {
        return \$this->belongsTo({$relatedClass}::class, '{$column}', '{$foreignColumn}');
    }
PHP;
        }

        foreach ($manyToManyRelations as $relation) {
            $methodName = $this->ensureUniqueMethodName(
                $methodNames,
                Str::camel(Str::pluralStudly($relation['method_base'])),
            );

            $imports->push('Illuminate\Database\Eloquent\Relations\BelongsToMany');
            $relationMethods[] = <<<PHP
    public function {$methodName}(): BelongsToMany
    {
        return \$this->belongsToMany({$relation['related_class']}::class, '{$relation['pivot_table']}', '{$relation['foreign_pivot_key']}', '{$relation['related_pivot_key']}', '{$relation['parent_key']}', '{$relation['related_key']}');
    }
PHP;
        }

        $incomingByTable = collect($incomingRelations)->groupBy('table');

        foreach ($incomingByTable as $incomingTable => $entries) {
            if (! array_key_exists($incomingTable, $classMap)) {
                continue;
            }

            $relatedClass = $classMap[$incomingTable];
            $entryCount = $entries->count();

            foreach ($entries as $entry) {
                $isUnique = (bool) $entry['unique'];
                $defaultMethod = $isUnique
                    ? Str::camel($relatedClass)
                    : Str::camel(Str::pluralStudly($relatedClass));
                $prefix = $this->relationBaseName(
                    $entry['column'],
                    $entry['foreign_column'],
                    $className,
                );

                $suggestedMethod = $entryCount > 1 && $prefix !== $currentClassSnake
                    ? Str::camel($prefix.' '.($isUnique ? $relatedClass : Str::pluralStudly($relatedClass)))
                    : $defaultMethod;

                $methodName = $this->ensureUniqueMethodName($methodNames, $suggestedMethod);

                $relationType = $isUnique ? 'HasOne' : 'HasMany';

                $imports->push("Illuminate\\Database\\Eloquent\\Relations\\{$relationType}");
                $relationMethods[] = <<<PHP
    public function {$methodName}(): {$relationType}
    {
        return \$this->{$this->hasRelationshipMethodForRelationType($relationType)}({$relatedClass}::class, '{$entry['column']}', '{$entry['foreign_column']}');
    }
PHP;
            }
        }

        if ($usesCompositePrimaryKey) {
            $imports->push('App\Models\Concerns\HasCompositePrimaryKey');
        }

        $importBlock = $imports
            ->unique()
            ->sort()
            ->map(fn (string $import): string => 'use '.$import.';')
            ->implode("\n");

        $traitBlock = $usesCompositePrimaryKey ? "\n    use HasCompositePrimaryKey;\n" : '';
        $propertyLines = [
            "    protected \$table = '{$table}';",
        ];

        if ($timestamps) {
            $propertyLines[] = '';
            $propertyLines[] = '    public $timestamps = true;';
        }

        if ($primaryColumn !== null && ($primaryColumn !== 'id' || ! $isIncrementingPrimaryKey)) {
            $propertyLines[] = '';
            $propertyLines[] = "    protected \$primaryKey = '{$primaryColumn}';";
        }

        if ($usesCompositePrimaryKey || ! $isIncrementingPrimaryKey) {
            $propertyLines[] = '';
            $propertyLines[] = '    public $incrementing = false;';
        }

        if ($isStringPrimaryKey) {
            $propertyLines[] = '';
            $propertyLines[] = "    protected \$keyType = 'string';";
        }

        if ($usesCompositePrimaryKey) {
            $compositeKeyLiteral = collect($primaryColumns)
                ->map(fn (string $column): string => "'{$column}'")
                ->implode(', ');

            $propertyLines[] = '';
            $propertyLines[] = "    protected array \$compositeKey = [{$compositeKeyLiteral}];";
        }

        $fillableBlock = collect($fillableColumns)
            ->map(fn (string $column): string => "        '{$column}',")
            ->implode("\n");

        $propertiesBlock = implode("\n", $propertyLines);

        $castsBlock = '';

        if ($casts !== []) {
            $castRows = collect($casts)
                ->map(fn (string $cast, string $column): string => "            '{$column}' => '{$cast}',")
                ->implode("\n");

            $castsBlock = <<<PHP

    protected function casts(): array
    {
        return [
{$castRows}
        ];
    }
PHP;
        }

        $relationBlock = $relationMethods === [] ? '' : "\n\n".implode("\n\n", $relationMethods);

        return <<<PHP
<?php

namespace {$namespace};

{$importBlock}

class {$className} extends ImdbModel
{
{$traitBlock}
{$propertiesBlock}

    /**
     * @var list<string>
     */
    protected \$fillable = [
{$fillableBlock}
    ];
{$castsBlock}{$relationBlock}
}
PHP;
    }

    /**
     * @param  array{name: string, type_name: string, type: string, nullable: bool, default: mixed, auto_increment: bool, comment: mixed, collation: mixed, generation: mixed}  $column
     */
    private function castForColumn(array $column): ?string
    {
        $typeName = Str::lower((string) $column['type_name']);
        $type = Str::lower((string) $column['type']);

        if ($typeName === 'tinyint' && str_contains($type, '(1)')) {
            return 'boolean';
        }

        return match ($typeName) {
            'bool', 'boolean' => 'boolean',
            'int', 'integer', 'bigint', 'mediumint', 'smallint', 'tinyint', 'year' => 'integer',
            'decimal', 'numeric' => $this->decimalCast($type),
            'double', 'float', 'real' => 'float',
            'json' => 'array',
            'date' => 'date',
            'datetime', 'timestamp' => 'datetime',
            default => null,
        };
    }

    private function decimalCast(string $type): string
    {
        if (preg_match('/\((\d+),\s*(\d+)\)/', $type, $matches) === 1) {
            return 'decimal:'.$matches[2];
        }

        return 'decimal:2';
    }

    /**
     * @param  array<int, string>  $usedMethodNames
     */
    private function ensureUniqueMethodName(array &$usedMethodNames, string $methodName): string
    {
        $uniqueName = $methodName;
        $suffix = 2;

        while (in_array($uniqueName, $usedMethodNames, true)) {
            $uniqueName = $methodName.$suffix;
            $suffix++;
        }

        $usedMethodNames[] = $uniqueName;

        return $uniqueName;
    }

    private function relationBaseName(string $column, string $foreignColumn, string $relatedClass): string
    {
        $base = Str::snake($column);
        $foreignBase = Str::snake($foreignColumn);
        $relatedBase = Str::snake($relatedClass);

        if ($base !== $foreignBase && Str::endsWith($base, '_'.$foreignBase)) {
            $base = Str::beforeLast($base, '_'.$foreignBase);
        } elseif ($base !== $foreignBase && Str::endsWith($base, $foreignBase)) {
            $base = rtrim(Str::beforeLast($base, $foreignBase), '_');
        } elseif (Str::endsWith($base, '_id')) {
            $base = Str::beforeLast($base, '_id');
        } elseif (Str::endsWith($base, '_code')) {
            $base = Str::beforeLast($base, '_code');
        } elseif (
            ! Str::contains($base, '_')
            && preg_match('/(?:id|code)$/', $base) === 1
        ) {
            $base = $relatedBase;
        }

        if ($base !== $relatedBase && Str::endsWith($base, '_'.$relatedBase)) {
            $base = Str::beforeLast($base, '_'.$relatedBase);
        }

        if (
            blank($base)
            || in_array($base, ['id', 'code', $foreignBase], true)
        ) {
            $base = $relatedBase;
        }

        return $base;
    }

    /**
     * @param  list<array<string, mixed>>  $indexes
     * @param  list<string>  $columns
     */
    private function columnsAreUniquelyIndexed(array $indexes, array $columns): bool
    {
        if ($columns === []) {
            return false;
        }

        $normalizedColumns = collect($columns)
            ->map(fn (string $column): string => Str::lower($column))
            ->sort()
            ->values()
            ->all();

        return collect($indexes)->contains(function (array $index) use ($normalizedColumns): bool {
            if (! ((bool) ($index['primary'] ?? false) || (bool) ($index['unique'] ?? false))) {
                return false;
            }

            return $this->normalizeColumnsForComparison($index['columns'] ?? []) === $normalizedColumns;
        });
    }

    /**
     * @param  array<string, array{columns: list<array<string, mixed>>, indexes: list<array<string, mixed>>, foreign_keys: list<array<string, mixed>>}>  $tableMetadata
     * @param  array<string, string>  $classMap
     * @return array<string, list<array{related_class: string, pivot_table: string, foreign_pivot_key: string, related_pivot_key: string, parent_key: string, related_key: string, method_base: string}>>
     */
    private function detectBelongsToManyRelations(array $tableMetadata, array $classMap): array
    {
        $relations = [];

        foreach ($tableMetadata as $pivotTable => $metadata) {
            $foreignKeys = collect($metadata['foreign_keys'])
                ->map(function (array $foreignKey): ?array {
                    $columns = $this->normalizeColumns($foreignKey['columns'] ?? []);
                    $foreignColumns = $this->normalizeColumns($foreignKey['foreign_columns'] ?? []);
                    $foreignTable = (string) ($foreignKey['foreign_table'] ?? '');

                    if ($foreignTable === '' || count($columns) !== 1 || count($foreignColumns) !== 1) {
                        return null;
                    }

                    return [
                        'column' => $columns[0],
                        'foreign_column' => $foreignColumns[0],
                        'foreign_table' => $foreignTable,
                    ];
                })
                ->filter()
                ->values();

            if ($foreignKeys->count() !== 2) {
                continue;
            }

            $firstForeignKey = $foreignKeys->get(0);
            $secondForeignKey = $foreignKeys->get(1);

            if (
                ! is_array($firstForeignKey)
                || ! is_array($secondForeignKey)
                || ! array_key_exists($firstForeignKey['foreign_table'], $classMap)
                || ! array_key_exists($secondForeignKey['foreign_table'], $classMap)
                || ! $this->columnsAreUniquelyIndexed(
                    $metadata['indexes'],
                    [$firstForeignKey['column'], $secondForeignKey['column']],
                )
            ) {
                continue;
            }

            $relations[$firstForeignKey['foreign_table']][] = [
                'related_class' => $classMap[$secondForeignKey['foreign_table']],
                'pivot_table' => $pivotTable,
                'foreign_pivot_key' => $firstForeignKey['column'],
                'related_pivot_key' => $secondForeignKey['column'],
                'parent_key' => $firstForeignKey['foreign_column'],
                'related_key' => $secondForeignKey['foreign_column'],
                'method_base' => $this->relationBaseName(
                    $secondForeignKey['column'],
                    $secondForeignKey['foreign_column'],
                    $classMap[$secondForeignKey['foreign_table']],
                ),
            ];

            $relations[$secondForeignKey['foreign_table']][] = [
                'related_class' => $classMap[$firstForeignKey['foreign_table']],
                'pivot_table' => $pivotTable,
                'foreign_pivot_key' => $secondForeignKey['column'],
                'related_pivot_key' => $firstForeignKey['column'],
                'parent_key' => $secondForeignKey['foreign_column'],
                'related_key' => $firstForeignKey['foreign_column'],
                'method_base' => $this->relationBaseName(
                    $firstForeignKey['column'],
                    $firstForeignKey['foreign_column'],
                    $classMap[$firstForeignKey['foreign_table']],
                ),
            ];
        }

        return $relations;
    }

    /**
     * @param  iterable<mixed>  $columns
     * @return list<string>
     */
    private function normalizeColumns(iterable $columns): array
    {
        return collect($columns)
            ->map(fn (mixed $column): string => trim((string) $column))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  iterable<mixed>  $columns
     * @return list<string>
     */
    private function normalizeColumnsForComparison(iterable $columns): array
    {
        return collect($columns)
            ->map(fn (mixed $column): string => Str::lower((string) $column))
            ->filter()
            ->sort()
            ->values()
            ->all();
    }

    private function hasRelationshipMethodForRelationType(string $relationType): string
    {
        return $relationType === 'HasOne' ? 'hasOne' : 'hasMany';
    }
}
