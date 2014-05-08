<?php

declare(strict_types=1);

namespace PhilipRehberger\Export\Formats;

use Illuminate\Support\Collection;

/**
 * JSON export format implementation.
 */
class JsonExporter extends AbstractExportFormat
{
    /**
     * Export data to JSON format.
     *
     * @param  Collection<int, mixed>  $data
     * @param  array<string, string>  $columns  Key => Header mapping
     */
    public function export(Collection $data, array $columns, array $options = []): string
    {
        $prettyPrint = $options['pretty_print'] ?? true;
        $includeMetadata = $options['include_metadata'] ?? false;

        $transformedData = $this->transformData($data, $columns);

        $flags = JSON_THROW_ON_ERROR;
        if ($prettyPrint) {
            $flags |= JSON_PRETTY_PRINT;
        }

        if ($includeMetadata) {
            $output = [
                'metadata' => [
                    'exported_at' => now()->toIso8601String(),
                    'total_records' => count($transformedData),
                    'columns' => $this->getHeaders($columns),
                ],
                'data' => $transformedData,
            ];

            return json_encode($output, $flags);
        }

        return json_encode($transformedData, $flags);
    }

    public function getContentType(): string
    {
        return 'application/json';
    }

    public function getFileExtension(): string
    {
        return 'json';
    }

    public function getFormatName(): string
    {
        return 'json';
    }
}
