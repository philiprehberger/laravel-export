<?php

declare(strict_types=1);

namespace PhilipRehberger\Export;

use InvalidArgumentException;
use PhilipRehberger\Export\Contracts\ExportFormatInterface;

/**
 * Registry for export format strategies.
 */
class ExportFormatRegistry
{
    /**
     * Registered export formats indexed by format name.
     *
     * @var array<string, ExportFormatInterface>
     */
    protected array $formats = [];

    /**
     * Register an export format.
     */
    public function register(ExportFormatInterface $format): self
    {
        $this->formats[$format->getFormatName()] = $format;

        return $this;
    }

    /**
     * Register multiple export formats.
     *
     * @param  array<ExportFormatInterface>  $formats
     */
    public function registerMany(array $formats): self
    {
        foreach ($formats as $format) {
            $this->register($format);
        }

        return $this;
    }

    /**
     * Get an export format by name.
     *
     * @throws InvalidArgumentException
     */
    public function get(string $formatName): ExportFormatInterface
    {
        if (! isset($this->formats[$formatName])) {
            throw new InvalidArgumentException("Export format '{$formatName}' is not registered.");
        }

        return $this->formats[$formatName];
    }

    /**
     * Check if a format is registered.
     */
    public function has(string $formatName): bool
    {
        return isset($this->formats[$formatName]);
    }

    /**
     * Get all registered formats.
     *
     * @return array<string, ExportFormatInterface>
     */
    public function all(): array
    {
        return $this->formats;
    }

    /**
     * Get available format names.
     *
     * @return array<string>
     */
    public function getAvailableFormats(): array
    {
        return array_keys($this->formats);
    }

    /**
     * Get format metadata for UI rendering.
     *
     * @return array<array{name: string, extension: string, contentType: string}>
     */
    public function getFormatMetadata(): array
    {
        return array_map(
            fn (ExportFormatInterface $format) => [
                'name' => $format->getFormatName(),
                'extension' => $format->getFileExtension(),
                'contentType' => $format->getContentType(),
            ],
            $this->formats
        );
    }
}
