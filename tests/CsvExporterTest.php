<?php

declare(strict_types=1);

namespace PhilipRehberger\Export\Tests;

use Carbon\Carbon;
use Orchestra\Testbench\TestCase;
use PhilipRehberger\Export\ExportServiceProvider;
use PhilipRehberger\Export\Formats\CsvExporter;

enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

class CsvExporterTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ExportServiceProvider::class];
    }

    private function makeExporter(): CsvExporter
    {
        return new CsvExporter;
    }

    private function makeData(): \Illuminate\Support\Collection
    {
        return collect([
            ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 30],
            ['name' => 'Bob', 'email' => 'bob@example.com', 'age' => 25],
        ]);
    }

    private function makeColumns(): array
    {
        return [
            'name' => 'Name',
            'email' => 'Email Address',
            'age' => 'Age',
        ];
    }

    public function test_exports_csv_with_headers(): void
    {
        $exporter = $this->makeExporter();
        $csv = $exporter->export($this->makeData(), $this->makeColumns(), ['include_headers' => true, 'include_bom' => false]);

        $lines = explode("\n", trim($csv));
        $this->assertStringContainsString('Name', $lines[0]);
        $this->assertStringContainsString('Email Address', $lines[0]);
        $this->assertStringContainsString('Age', $lines[0]);
        $this->assertCount(3, $lines); // header + 2 data rows
    }

    public function test_exports_csv_without_headers(): void
    {
        $exporter = $this->makeExporter();
        $csv = $exporter->export($this->makeData(), $this->makeColumns(), ['include_headers' => false, 'include_bom' => false]);

        $lines = explode("\n", trim($csv));
        $this->assertStringNotContainsString('Name', $lines[0]);
        $this->assertStringContainsString('Alice', $lines[0]);
        $this->assertCount(2, $lines);
    }

    public function test_csv_includes_bom_by_default(): void
    {
        $exporter = $this->makeExporter();
        $csv = $exporter->export($this->makeData(), $this->makeColumns());

        // UTF-8 BOM is \xEF\xBB\xBF
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
    }

    public function test_csv_custom_delimiter(): void
    {
        $exporter = $this->makeExporter();
        $csv = $exporter->export(
            $this->makeData(),
            $this->makeColumns(),
            ['delimiter' => ';', 'include_bom' => false]
        );

        $this->assertStringContainsString(';', $csv);
    }

    public function test_handles_empty_collection(): void
    {
        $exporter = $this->makeExporter();
        $csv = $exporter->export(collect(), $this->makeColumns(), ['include_bom' => false]);

        $lines = array_filter(explode("\n", trim($csv)));
        // Only the header line should remain
        $this->assertCount(1, $lines);
        $this->assertStringContainsString('Name', reset($lines));
    }

    public function test_transforms_enum_values(): void
    {
        $data = collect([['status' => UserStatus::Active]]);
        $columns = ['status' => 'Status'];

        $exporter = $this->makeExporter();
        $csv = $exporter->export($data, $columns, ['include_bom' => false]);

        $this->assertStringContainsString('active', $csv);
    }

    public function test_transforms_carbon_dates(): void
    {
        $date = Carbon::create(2024, 1, 15, 10, 30, 0);
        $data = collect([['created_at' => $date]]);
        $columns = ['created_at' => 'Created At'];

        $exporter = $this->makeExporter();
        $csv = $exporter->export($data, $columns, ['include_bom' => false]);

        $this->assertStringContainsString('2024-01-15 10:30:00', $csv);
    }

    public function test_content_type_is_csv(): void
    {
        $exporter = $this->makeExporter();
        $this->assertSame('text/csv; charset=UTF-8', $exporter->getContentType());
    }

    public function test_file_extension_is_csv(): void
    {
        $exporter = $this->makeExporter();
        $this->assertSame('csv', $exporter->getFileExtension());
    }
}
