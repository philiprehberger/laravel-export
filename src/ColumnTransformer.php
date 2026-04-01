<?php

declare(strict_types=1);

namespace PhilipRehberger\Export;

/**
 * Transforms row data by selecting, renaming, and computing derived columns.
 *
 * Column definitions map output names to either a source column name (string)
 * or a callable that receives the full row and returns the computed value.
 */
class ColumnTransformer
{
    /**
     * Column definitions: output name => source column name or callable.
     *
     * @var array<string, string|callable>
     */
    protected array $columns;

    /**
     * Create a new column transformer.
     *
     * @param  array<string, string|callable>  $columns  Keys are output names, values are source column names or callables
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    /**
     * Transform a single row using the column definitions.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function transform(array $row): array
    {
        $result = [];

        foreach ($this->columns as $outputName => $source) {
            if (is_callable($source)) {
                $result[$outputName] = $source($row);
            } else {
                $result[$outputName] = $row[$source] ?? null;
            }
        }

        return $result;
    }

    /**
     * Get the output column names (headers).
     *
     * @return array<string>
     */
    public function headers(): array
    {
        return array_keys($this->columns);
    }
}
