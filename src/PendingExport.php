<?php

declare(strict_types=1);

namespace PhilipRehberger\Export;

use Illuminate\Support\Facades\Storage;

/**
 * Value object representing a queued export that is pending or completed.
 *
 * Provides status checking by verifying whether the export file exists on disk.
 */
class PendingExport
{
    /**
     * Create a new pending export instance.
     *
     * @param  string  $id  Unique identifier for this export
     * @param  string  $disk  Storage disk name
     * @param  string  $path  File path on the disk
     */
    public function __construct(
        public readonly string $id,
        public readonly string $disk,
        public readonly string $path,
    ) {}

    /**
     * Check the status of the export.
     *
     * Returns 'completed' if the file exists on disk, 'pending' otherwise.
     */
    public function status(): string
    {
        if (Storage::disk($this->disk)->exists($this->path)) {
            return 'completed';
        }

        return 'pending';
    }
}
