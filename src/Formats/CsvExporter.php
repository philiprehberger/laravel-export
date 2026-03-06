<?php

declare(strict_types=1);

namespace PhilipRehberger\Export\Formats;

use Illuminate\Support\Collection;

/**
 * CSV export format implementation.
 */
class CsvExporter extends AbstractExportFormat
{
    /**
     * Export data to CSV format.
     *
     * @param  Collection<int, mixed>  $data
     * @param  array<string, string>  $columns  Key => Header mapping
     */
    public function export(Collection $data, array $columns, array $options = []): string
    {
        $includeHeaders = $options['include_headers'] ?? true;
        $delimiter = $options['delimiter'] ?? ',';
        $enclosure = $options['enclosure'] ?? '"';
        $includeBom = $options['include_bom'] ?? true;

        $output = fopen('php://temp', 'r+');

        // Write UTF-8 BOM for Excel compatibility
        if ($includeBom) {
            fwrite($output, "\xEF\xBB\xBF");
        }

        // Header row
        if ($includeHeaders) {
            fputcsv($output, $this->getHeaders($columns), $delimiter, $enclosure);
        }

        // Data rows
        $transformedData = $this->transformData($data, $columns);
        foreach ($transformedData as $row) {
            fputcsv($output, array_values($row), $delimiter, $enclosure);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    public function getContentType(): string
    {
        return 'text/csv; charset=UTF-8';
    }

    public function getFileExtension(): string
    {
        return 'csv';
    }

    public function getFormatName(): string
    {
        return 'csv';
    }
}
