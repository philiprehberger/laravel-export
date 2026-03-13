<?php

declare(strict_types=1);

namespace PhilipRehberger\Export\Tests;

use Illuminate\Support\Collection;
use Orchestra\Testbench\TestCase;
use PhilipRehberger\Export\ExportServiceProvider;
use PhilipRehberger\Export\Formats\JsonExporter;

class JsonExporterTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ExportServiceProvider::class];
    }

    private function makeExporter(): JsonExporter
    {
        return new JsonExporter;
    }

    private function makeData(): Collection
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
            'email' => 'Email Address',
        ];
    }

    public function test_exports_json_array(): void
    {
        $exporter = $this->makeExporter();
        $json = $exporter->export($this->makeData(), $this->makeColumns(), ['pretty_print' => false]);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertCount(2, $decoded);
        $this->assertSame('Alice', $decoded[0]['Name']);
        $this->assertSame('alice@example.com', $decoded[0]['Email Address']);
    }

    public function test_pretty_print_enabled_by_default(): void
    {
        $exporter = $this->makeExporter();
        $json = $exporter->export($this->makeData(), $this->makeColumns());

        // Pretty-printed JSON contains newlines
        $this->assertStringContainsString("\n", $json);
    }

    public function test_includes_metadata_when_requested(): void
    {
        $exporter = $this->makeExporter();
        $json = $exporter->export(
            $this->makeData(),
            $this->makeColumns(),
            ['include_metadata' => true, 'pretty_print' => false]
        );

        $decoded = json_decode($json, true);
        $this->assertArrayHasKey('metadata', $decoded);
        $this->assertArrayHasKey('data', $decoded);
        $this->assertSame(2, $decoded['metadata']['total_records']);
        $this->assertContains('Name', $decoded['metadata']['columns']);
        $this->assertArrayHasKey('exported_at', $decoded['metadata']);
    }

    public function test_content_type_is_json(): void
    {
        $exporter = $this->makeExporter();
        $this->assertSame('application/json', $exporter->getContentType());
    }

    public function test_file_extension_is_json(): void
    {
        $exporter = $this->makeExporter();
        $this->assertSame('json', $exporter->getFileExtension());
    }
}
