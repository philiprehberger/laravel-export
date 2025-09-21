<?php

declare(strict_types=1);

namespace PhilipRehberger\Export;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use PhilipRehberger\Export\Contracts\ExportableInterface;
use PhilipRehberger\Export\Contracts\ExportFormatInterface;
use PhilipRehberger\Export\Jobs\QueuedExportJob;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Service for exporting data in various formats.
 *
 * Provides a unified API for exporting collections and Eloquent models
 * to CSV, JSON, and any other registered format.
 */
class ExportService
{
    /**
     * The column transformer for the current export chain.
     */
    protected ?ColumnTransformer $columnTransformer = null;

    public function __construct(
        protected ExportFormatRegistry $registry
    ) {}

    /**
     * Set a column transformer for selecting, renaming, and computing columns.
     *
     * @param  array<string, string|callable>  $columns  Keys are output names, values are source column names or callables
     */
    public function columns(array $columns): self
    {
        $this->columnTransformer = new ColumnTransformer($columns);

        return $this;
    }

    /**
     * Export a collection of items to the specified format.
     *
     * @param  Collection<int, mixed>  $data
     * @param  array<string, string>  $columns  Key => Header mapping
     */
    public function export(
        Collection $data,
        array $columns,
        string $format,
        array $options = []
    ): string {
        $exporter = $this->registry->get($format);

        $data = $this->applyColumnTransformer($data);

        return $exporter->export($data, $columns, $options);
    }

    /**
     * Queue an export for background processing.
     *
     * The export result is stored to the specified disk and path via Storage::put().
     *
     * @param  Collection<int, mixed>  $data
     * @param  array<string, string>  $columns
     */
    public function queue(
        Collection $data,
        array $columns,
        string $format,
        string $disk = 'local',
        ?string $path = null,
        array $options = [],
        ?callable $onComplete = null,
    ): PendingExport {
        $data = $this->applyColumnTransformer($data);

        $id = Str::uuid()->toString();
        $exporter = $this->registry->get($format);
        $extension = $exporter->getFileExtension();
        $path = $path ?? 'exports/'.$id.'.'.$extension;

        QueuedExportJob::dispatch(
            $data->values()->all(),
            $columns,
            $format,
            $options,
            $disk,
            $path,
            $onComplete,
        );

        return new PendingExport($id, $disk, $path);
    }

    /**
     * Export items that implement ExportableInterface.
     *
     * @param  Collection<int, ExportableInterface>  $items
     */
    public function exportModels(Collection $items, string $format, array $options = []): string
    {
        if ($items->isEmpty()) {
            $exporter = $this->registry->get($format);

            return $exporter->export(collect(), [], $options);
        }

        $firstItem = $items->first();
        if (! ($firstItem instanceof ExportableInterface)) {
            throw new InvalidArgumentException('Items must implement ExportableInterface.');
        }

        // Get columns from the model's static method
        $columns = $firstItem::getExportColumns();

        // Transform items using their toExportArray method
        $data = $items->map(fn (ExportableInterface $item) => $item->toExportArray());

        return $this->export($data, $columns, $format, $options);
    }

    /**
     * Download export as a response.
     *
     * @param  Collection<int, mixed>  $data
     * @param  array<string, string>  $columns
     */
    public function download(
        Collection $data,
        array $columns,
        string $format,
        string $filename,
        array $options = []
    ): Response {
        $exporter = $this->registry->get($format);
        $content = $exporter->export($data, $columns, $options);

        $filename = $this->sanitizeFilename($filename);

        // Ensure filename has correct extension
        $extension = $exporter->getFileExtension();
        if (! str_ends_with($filename, '.'.$extension)) {
            $filename .= '.'.$extension;
        }

        return response($content)
            ->header('Content-Type', $exporter->getContentType())
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Stream export as a response (for large datasets).
     *
     * @param  Collection<int, mixed>  $data
     * @param  array<string, string>  $columns
     */
    public function stream(
        Collection $data,
        array $columns,
        string $format,
        string $filename,
        array $options = []
    ): StreamedResponse {
        $exporter = $this->registry->get($format);

        $filename = $this->sanitizeFilename($filename);

        // Ensure filename has correct extension
        $extension = $exporter->getFileExtension();
        if (! str_ends_with($filename, '.'.$extension)) {
            $filename .= '.'.$extension;
        }

        return response()->streamDownload(
            function () use ($exporter, $data, $columns, $options): void {
                echo $exporter->export($data, $columns, $options);
            },
            $filename,
            [
                'Content-Type' => $exporter->getContentType(),
            ]
        );
    }

    /**
     * Download models that implement ExportableInterface.
     *
     * @param  Collection<int, ExportableInterface>  $items
     */
    public function downloadModels(
        Collection $items,
        string $format,
        ?string $filename = null,
        array $options = []
    ): Response {
        if ($items->isEmpty()) {
            $exporter = $this->registry->get($format);

            return response($exporter->export(collect(), [], $options))
                ->header('Content-Type', $exporter->getContentType())
                ->header('Content-Disposition', 'attachment; filename="export.'.$exporter->getFileExtension().'"');
        }

        $firstItem = $items->first();
        if (! ($firstItem instanceof ExportableInterface)) {
            throw new InvalidArgumentException('Items must implement ExportableInterface.');
        }

        $filename = $filename ?? $firstItem::getExportFilename();
        $columns = $firstItem::getExportColumns();
        $data = $items->map(fn (ExportableInterface $item) => $item->toExportArray());

        return $this->download($data, $columns, $format, $filename, $options);
    }

    /**
     * Check if a format is supported.
     */
    public function supportsFormat(string $format): bool
    {
        return $this->registry->has($format);
    }

    /**
     * Get available export formats.
     *
     * @return array<string>
     */
    public function getAvailableFormats(): array
    {
        return $this->registry->getAvailableFormats();
    }

    /**
     * Get a specific format exporter.
     */
    public function getFormat(string $format): ExportFormatInterface
    {
        return $this->registry->get($format);
    }

    /**
     * Get format metadata for UI.
     */
    public function getFormatMetadata(): array
    {
        return $this->registry->getFormatMetadata();
    }

    /**
     * Apply the column transformer to the data if one is set.
     *
     * Resets the transformer after application so it does not leak to subsequent calls.
     *
     * @param  Collection<int, mixed>  $data
     * @return Collection<int, mixed>
     */
    private function applyColumnTransformer(Collection $data): Collection
    {
        if ($this->columnTransformer === null) {
            return $data;
        }

        $transformer = $this->columnTransformer;
        $this->columnTransformer = null;

        return $data->map(fn ($row) => $transformer->transform((array) $row));
    }

    private function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[\x00-\x1F\x7F"\\\\\/]/', '', $filename);

        return trim($filename) !== '' ? trim($filename) : 'export';
    }
}
