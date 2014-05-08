<?php

declare(strict_types=1);

namespace PhilipRehberger\Export\Tests;

use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use PhilipRehberger\Export\Contracts\ExportableInterface;
use PhilipRehberger\Export\ExportService;
use PhilipRehberger\Export\ExportServiceProvider;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportServiceTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ExportServiceProvider::class];
    }

    private function makeService(): ExportService
    {
        return $this->app->make(ExportService::class);
    }

    private function makeData(): \Illuminate\Support\Collection
    {
        return collect([
            ['name' => 'Alice', 'email' => 'alice@example.com'],
            ['name' => 'Bob', 'email' => 'bob@example.com'],
        ]);
    }

    private function makeColumns(): array
    {
        return [
            'name' => 'Name',
            'email' => 'Email',
        ];
    }

    private function makeExportableItem(string $name, string $email): ExportableInterface
    {
        return new class($name, $email) implements ExportableInterface
        {
            public function __construct(
                private readonly string $name,
                private readonly string $email,
            ) {}

            public function toExportArray(): array
            {
                return [
                    'name' => $this->name,
                    'email' => $this->email,
                ];
            }

            public static function getExportColumns(): array
            {
                return [
                    'name' => 'Name',
                    'email' => 'Email',
                ];
            }

            public static function getExportFilename(): string
            {
                return 'users-export';
            }
        };
    }

    public function test_export_csv(): void
    {
        $service = $this->makeService();
        $csv = $service->export($this->makeData(), $this->makeColumns(), 'csv', ['include_bom' => false]);

        $this->assertStringContainsString('Name', $csv);
        $this->assertStringContainsString('Alice', $csv);
        $this->assertStringContainsString('Bob', $csv);
    }

    public function test_export_json(): void
    {
        $service = $this->makeService();
        $json = $service->export($this->makeData(), $this->makeColumns(), 'json', ['pretty_print' => false]);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertCount(2, $decoded);
    }

    public function test_export_models_with_exportable_interface(): void
    {
        $service = $this->makeService();
        $items = collect([
            $this->makeExportableItem('Alice', 'alice@example.com'),
            $this->makeExportableItem('Bob', 'bob@example.com'),
        ]);

        $csv = $service->exportModels($items, 'csv', ['include_bom' => false]);

        $this->assertStringContainsString('Alice', $csv);
        $this->assertStringContainsString('Bob', $csv);
        $this->assertStringContainsString('Name', $csv);
    }

    public function test_download_returns_response_with_correct_headers(): void
    {
        $service = $this->makeService();
        $response = $service->download(
            $this->makeData(),
            $this->makeColumns(),
            'csv',
            'test-export'
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('test-export.csv', $response->headers->get('Content-Disposition'));
    }

    public function test_stream_returns_streamed_response(): void
    {
        $service = $this->makeService();
        $response = $service->stream(
            $this->makeData(),
            $this->makeColumns(),
            'json',
            'test-export'
        );

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertStringContainsString('test-export.json', $response->headers->get('Content-Disposition'));
    }

    public function test_supports_format(): void
    {
        $service = $this->makeService();

        $this->assertTrue($service->supportsFormat('csv'));
        $this->assertTrue($service->supportsFormat('json'));
        $this->assertFalse($service->supportsFormat('excel'));
    }

    public function test_get_available_formats(): void
    {
        $service = $this->makeService();
        $formats = $service->getAvailableFormats();

        $this->assertContains('csv', $formats);
        $this->assertContains('json', $formats);
    }

    public function test_export_models_throws_for_non_exportable(): void
    {
        $service = $this->makeService();
        $items = collect([
            (object) ['name' => 'Alice'],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Items must implement ExportableInterface.');

        $service->exportModels($items, 'csv');
    }
}
