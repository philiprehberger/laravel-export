<?php

declare(strict_types=1);

namespace PhilipRehberger\Export\Contracts;

/**
 * Contract for models that can be exported.
 *
 * Implement this interface on Eloquent models to enable them
 * to be exported via the ExportService.
 */
interface ExportableInterface
{
    /**
     * Convert the model to an array suitable for export.
     *
     * This should return a flat array with all values that should be exported.
     * Complex objects should be converted to strings.
     *
     * @return array<string, mixed>
     */
    public function toExportArray(): array;

    /**
     * Get the column definitions for export.
     *
     * Returns an array mapping internal keys to display headers.
     * Example: ['name' => 'Client Name', 'email' => 'Email Address']
     *
     * @return array<string, string>
     */
    public static function getExportColumns(): array;

    /**
     * Get the default filename for exports of this type.
     */
    public static function getExportFilename(): string;
}
