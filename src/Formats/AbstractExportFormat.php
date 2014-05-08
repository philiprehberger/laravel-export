<?php

declare(strict_types=1);

namespace PhilipRehberger\Export\Formats;

use Illuminate\Support\Collection;
use PhilipRehberger\Export\Contracts\ExportFormatInterface;

/**
 * Base class for export format implementations.
 */
abstract class AbstractExportFormat implements ExportFormatInterface
{
    /**
     * Transform data using column mappings.
     *
     * @param  Collection<int, mixed>  $data
     * @param  array<string, string>  $columns  Key => Header mapping
     * @return array<int, array<string, mixed>>
     */
    protected function transformData(Collection $data, array $columns): array
    {
        return $data->map(function ($row) use ($columns) {
            $transformed = [];
            foreach ($columns as $key => $header) {
                $value = data_get($row, $key, '');

                // Handle enums
                if ($value instanceof \BackedEnum) {
                    $value = $value->value;
                }

                // Handle Carbon dates
                if ($value instanceof \Carbon\Carbon) {
                    $value = $value->toDateTimeString();
                }

                // Handle arrays and objects
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }

                $transformed[$header] = $value;
            }

            return $transformed;
        })->values()->all();
    }

    /**
     * Get column headers from mapping.
     *
     * @param  array<string, string>  $columns
     * @return array<string>
     */
    protected function getHeaders(array $columns): array
    {
        return array_values($columns);
    }
}
