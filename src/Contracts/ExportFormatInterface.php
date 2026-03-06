<?php

declare(strict_types=1);

namespace PhilipRehberger\Export\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for export format strategies.
 *
 * Each export format (CSV, JSON, etc.) should implement
 * this interface to be usable with the ExportService.
 */
interface ExportFormatInterface
{
    /**
     * Export the data to the format.
     *
     * @param  Collection  $data  The data to export
     * @param  array<string, string>  $columns  Column mapping (key => header)
     * @param  array  $options  Format-specific options
     * @return string The exported content
     */
    public function export(Collection $data, array $columns, array $options = []): string;

    /**
     * Get the MIME content type for this format.
     */
    public function getContentType(): string;

    /**
     * Get the file extension for this format.
     */
    public function getFileExtension(): string;

    /**
     * Get the format name/identifier.
     */
    public function getFormatName(): string;
}
