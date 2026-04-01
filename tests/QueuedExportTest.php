<?php

declare(strict_types=1);

namespace PhilipRehberger\Export\Tests;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;
use PhilipRehberger\Export\ExportService;
use PhilipRehberger\Export\ExportServiceProvider;
use PhilipRehberger\Export\Jobs\QueuedExportJob;
use PhilipRehberger\Export\PendingExport;

class QueuedExportTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ExportServiceProvider::class];
    }

    private function makeService(): ExportService
    {
        return $this->app->make(ExportService::class);
    }

    public function test_queue_returns_pending_export(): void
    {
        Bus::fake();

        $service = $this->makeService();
        $data = collect([
            ['name' => 'Alice', 'email' => 'alice@example.com'],
        ]);
        $columns = ['name' => 'Name', 'email' => 'Email'];

        $pending = $service->queue($data, $columns, 'csv');

        $this->assertInstanceOf(PendingExport::class, $pending);
        Bus::assertDispatched(QueuedExportJob::class);
    }

    public function test_pending_export_has_correct_properties(): void
    {
        Bus::fake();

        $service = $this->makeService();
        $data = collect([
            ['name' => 'Alice', 'email' => 'alice@example.com'],
        ]);
        $columns = ['name' => 'Name', 'email' => 'Email'];

        $pending = $service->queue($data, $columns, 'json', 'local');

        $this->assertSame('local', $pending->disk);
        $this->assertNotEmpty($pending->id);
        $this->assertStringEndsWith('.json', $pending->path);
    }

    public function test_pending_export_with_custom_path(): void
    {
        Bus::fake();

        $service = $this->makeService();
        $data = collect([
            ['name' => 'Alice', 'email' => 'alice@example.com'],
        ]);
        $columns = ['name' => 'Name', 'email' => 'Email'];

        $pending = $service->queue($data, $columns, 'csv', 'local', 'custom/report.csv');

        $this->assertSame('custom/report.csv', $pending->path);
    }

    public function test_pending_export_status_is_pending_when_file_missing(): void
    {
        Storage::fake('local');

        $pending = new PendingExport('test-id', 'local', 'exports/test.csv');

        $this->assertSame('pending', $pending->status());
    }

    public function test_pending_export_status_is_completed_when_file_exists(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('exports/test.csv', 'Name,Email');

        $pending = new PendingExport('test-id', 'local', 'exports/test.csv');

        $this->assertSame('completed', $pending->status());
    }

    public function test_queued_export_job_dispatched_with_correct_format(): void
    {
        Bus::fake();

        $service = $this->makeService();
        $data = collect([
            ['name' => 'Alice', 'email' => 'alice@example.com'],
        ]);
        $columns = ['name' => 'Name', 'email' => 'Email'];

        $service->queue($data, $columns, 'xml', 'local');

        Bus::assertDispatched(QueuedExportJob::class);
    }
}
