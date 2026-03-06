<?php

declare(strict_types=1);

namespace PhilipRehberger\Export\Facades;

use Illuminate\Support\Facades\Facade;
use PhilipRehberger\Export\ExportService;

/**
 * @method static string export(\Illuminate\Support\Collection $data, array $columns, string $format, array $options = [])
 * @method static string exportModels(\Illuminate\Support\Collection $items, string $format, array $options = [])
 * @method static \Symfony\Component\HttpFoundation\Response download(\Illuminate\Support\Collection $data, array $columns, string $format, string $filename, array $options = [])
 * @method static \Symfony\Component\HttpFoundation\StreamedResponse stream(\Illuminate\Support\Collection $data, array $columns, string $format, string $filename, array $options = [])
 * @method static \Symfony\Component\HttpFoundation\Response downloadModels(\Illuminate\Support\Collection $items, string $format, ?string $filename = null, array $options = [])
 * @method static bool supportsFormat(string $format)
 * @method static array getAvailableFormats()
 * @method static array getFormatMetadata()
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
