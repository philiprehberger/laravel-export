<?php

declare(strict_types=1);

namespace PhilipRehberger\Export\Tests;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use PhilipRehberger\Export\Contracts\ExportFormatInterface;
use PhilipRehberger\Export\ExportFormatRegistry;
use PhilipRehberger\Export\ExportServiceProvider;
use PhilipRehberger\Export\Formats\CsvExporter;
use PhilipRehberger\Export\Formats\JsonExporter;

class ExportFormatRegistryTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ExportServiceProvider::class];
    }

    private function makeRegistry(): ExportFormatRegistry
    {
        return new ExportFormatRegistry;
    }

    private function makeStubFormat(string $name, string $extension = 'txt', string $contentType = 'text/plain'): ExportFormatInterface
    {
        return new class($name, $extension, $contentType) implements ExportFormatInterface
        {
            public function __construct(
                private readonly string $name,
                private readonly string $extension,
                private readonly string $contentType,
            ) {}

            public function export(Collection $data, array $columns, array $options = []): string
            {
                return '';
            }

            public function getContentType(): string
            {
                return $this->contentType;
            }

            public function getFileExtension(): string
            {
                return $this->extension;
            }

            public function getFormatName(): string
            {
                return $this->name;
            }
        };
    }

    public function test_register_and_get_format(): void
    {
        $registry = $this->makeRegistry();
        $format = new CsvExporter;
        $registry->register($format);

        $retrieved = $registry->get('csv');
        $this->assertSame($format, $retrieved);
    }

    public function test_get_unknown_format_throws_exception(): void
    {
        $registry = $this->makeRegistry();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Export format 'excel' is not registered.");

        $registry->get('excel');
    }

    public function test_has_returns_true_for_registered(): void
    {
        $registry = $this->makeRegistry();
        $registry->register(new CsvExporter);

        $this->assertTrue($registry->has('csv'));
    }

    public function test_has_returns_false_for_unregistered(): void
    {
        $registry = $this->makeRegistry();

        $this->assertFalse($registry->has('excel'));
    }

    public function test_get_available_formats(): void
    {
        $registry = $this->makeRegistry();
        $registry->register(new CsvExporter);
        $registry->register(new JsonExporter);

        $formats = $registry->getAvailableFormats();

        $this->assertContains('csv', $formats);
        $this->assertContains('json', $formats);
        $this->assertCount(2, $formats);
    }

    public function test_get_format_metadata(): void
    {
        $registry = $this->makeRegistry();
        $registry->register(new CsvExporter);
        $registry->register(new JsonExporter);

        $metadata = $registry->getFormatMetadata();

        $this->assertCount(2, $metadata);

        $names = array_column($metadata, 'name');
        $this->assertContains('csv', $names);
        $this->assertContains('json', $names);

        $csvMeta = array_values(array_filter($metadata, fn ($m) => $m['name'] === 'csv'))[0];
        $this->assertSame('csv', $csvMeta['extension']);
        $this->assertSame('text/csv; charset=UTF-8', $csvMeta['contentType']);
    }

    public function test_register_many(): void
    {
        $registry = $this->makeRegistry();
        $registry->registerMany([
            new CsvExporter,
            new JsonExporter,
        ]);

        $this->assertTrue($registry->has('csv'));
        $this->assertTrue($registry->has('json'));
        $this->assertCount(2, $registry->getAvailableFormats());
    }
}
