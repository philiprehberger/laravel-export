<?php

declare(strict_types=1);

namespace PhilipRehberger\Export\Facades;

use Illuminate\Support\Facades\Facade;
use PhilipRehberger\Export\ExportService;

/**
 * @method static string export(\Illuminate\Support\Collection $data, array<string, string> $columns, string $format, array<string, mixed> $options = [])
 * @method static string exportModels(\Illuminate\Support\Collection $items, string $format, array<string, mixed> $options = [])
 * @method static \Symfony\Component\HttpFoundation\Response download(\Illuminate\Support\Collection $data, array<string, string> $columns, string $format, string $filename, array<string, mixed> $options = [])
 * @method static \Symfony\Component\HttpFoundation\StreamedResponse stream(\Illuminate\Support\Collection $data, array<string, string> $columns, string $format, string $filename, array<string, mixed> $options = [])
 * @method static \Symfony\Component\HttpFoundation\Response downloadModels(\Illuminate\Support\Collection $items, string $format, ?string $filename = null, array<string, mixed> $options = [])
 * @method static \PhilipRehberger\Export\ExportService columns(array<string, string|callable> $columns)
 * @method static \PhilipRehberger\Export\PendingExport queue(\Illuminate\Support\Collection $data, array<string, string> $columns, string $format, string $disk = 'local', ?string $path = null, array<string, mixed> $options = [], ?callable $onComplete = null)
 * @method static bool supportsFormat(string $format)
 * @method static array<int, string> getAvailableFormats()
 * @method static array<string, array<string, mixed>> getFormatMetadata()
 * @method static \PhilipRehberger\Export\Contracts\ExportFormatInterface getFormat(string $format)
 *
 * @see ExportService
 */
class Export extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-export';
    }
}
