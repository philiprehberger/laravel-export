<?php

declare(strict_types=1);

namespace PhilipRehberger\Export\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use PhilipRehberger\Export\ExportService;

/**
 * Queue job that performs a data export and stores the result to disk.
 *
 * Dispatched by ExportService::queue() for background processing of large exports.
 */
class QueuedExportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The serialized data rows.
     *
     * @var array<int, mixed>
     */
    protected array $dataArray;

    /**
     * The column mapping.
     *
     * @var array<string, string>
     */
    protected array $columns;

    /**
     * The export format name.
     */
    protected string $format;

    /**
     * Format-specific options.
     *
     * @var array<string, mixed>
     */
    protected array $options;

    /**
     * The storage disk name.
     */
    protected string $disk;

    /**
     * The file path on the disk.
     */
    protected string $path;

    /**
     * Optional callback to run after export completes.
     *
     * @var callable|null
     */
    protected $onComplete;

    /**
     * Create a new queued export job.
     *
     * @param  array<int, mixed>  $dataArray
     * @param  array<string, string>  $columns
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        array $dataArray,
        array $columns,
        string $format,
        array $options,
        string $disk,
        string $path,
        ?callable $onComplete = null,
    ) {
        $this->dataArray = $dataArray;
        $this->columns = $columns;
        $this->format = $format;
        $this->options = $options;
        $this->disk = $disk;
        $this->path = $path;
        $this->onComplete = $onComplete;
    }

    /**
     * Execute the job.
     */
    public function handle(ExportService $service): void
    {
        $data = new Collection($this->dataArray);

        $content = $service->export($data, $this->columns, $this->format, $this->options);

        Storage::disk($this->disk)->put($this->path, $content);

        if ($this->onComplete !== null) {
            ($this->onComplete)($this->path, $this->disk);
        }
    }
}
