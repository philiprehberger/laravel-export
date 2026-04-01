<?php

declare(strict_types=1);

namespace PhilipRehberger\Export\Tests;

use Orchestra\Testbench\TestCase;
use PhilipRehberger\Export\ColumnTransformer;
use PhilipRehberger\Export\ExportService;
use PhilipRehberger\Export\ExportServiceProvider;

class ColumnTransformerTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ExportServiceProvider::class];
    }

    public function test_string_columns_pass_through_values(): void
    {
        $transformer = new ColumnTransformer([
            'Full Name' => 'name',
            'Email Address' => 'email',
        ]);

        $result = $transformer->transform([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'age' => 30,
        ]);

        $this->assertSame([
            'Full Name' => 'Alice',
            'Email Address' => 'alice@example.com',
        ], $result);
    }

    public function test_callable_columns_transform_values(): void
    {
        $transformer = new ColumnTransformer([
            'Full Name' => fn (array $row) => strtoupper($row['name']),
            'Domain' => fn (array $row) => explode('@', $row['email'])[1] ?? '',
        ]);

        $result = $transformer->transform([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);

        $this->assertSame([
            'Full Name' => 'ALICE',
            'Domain' => 'example.com',
        ], $result);
    }

    public function test_missing_source_column_returns_null(): void
    {
        $transformer = new ColumnTransformer([
            'Name' => 'name',
            'Missing' => 'nonexistent',
        ]);

        $result = $transformer->transform(['name' => 'Alice']);

        $this->assertSame('Alice', $result['Name']);
        $this->assertNull($result['Missing']);
    }

    public function test_headers_returns_output_column_names(): void
    {
        $transformer = new ColumnTransformer([
            'Full Name' => 'name',
            'Email Address' => 'email',
            'Computed' => fn (array $row) => 'value',
        ]);

        $this->assertSame(['Full Name', 'Email Address', 'Computed'], $transformer->headers());
    }

    public function test_mixed_string_and_callable_columns(): void
    {
        $transformer = new ColumnTransformer([
            'Name' => 'name',
            'Name Upper' => fn (array $row) => strtoupper($row['name']),
            'Email' => 'email',
        ]);

        $result = $transformer->transform([
            'name' => 'Alice',
            'email' => 'alice@example.com',
        ]);

        $this->assertSame([
            'Name' => 'Alice',
            'Name Upper' => 'ALICE',
            'Email' => 'alice@example.com',
        ], $result);
    }

    public function test_integration_with_export_service(): void
    {
        /** @var ExportService $service */
        $service = $this->app->make(ExportService::class);

        $data = collect([
            ['first_name' => 'Alice', 'last_name' => 'Smith', 'email' => 'alice@example.com'],
            ['first_name' => 'Bob', 'last_name' => 'Jones', 'email' => 'bob@example.com'],
        ]);

        $columns = [
            'Full Name' => 'Full Name',
            'Email' => 'Email',
        ];

        $json = $service->columns([
            'Full Name' => fn (array $row) => $row['first_name'].' '.$row['last_name'],
            'Email' => 'email',
        ])->export($data, $columns, 'json', ['pretty_print' => false]);

        $decoded = json_decode($json, true);
        $this->assertCount(2, $decoded);
        $this->assertSame('Alice Smith', $decoded[0]['Full Name']);
        $this->assertSame('alice@example.com', $decoded[0]['Email']);
        $this->assertSame('Bob Jones', $decoded[1]['Full Name']);
    }

    public function test_column_transformer_resets_after_export(): void
    {
        /** @var ExportService $service */
        $service = $this->app->make(ExportService::class);

        $data = collect([
            ['name' => 'Alice', 'email' => 'alice@example.com'],
        ]);

        // First export with transformer
        $service->columns([
            'Name Upper' => fn (array $row) => strtoupper($row['name']),
        ])->export($data, ['Name Upper' => 'Name Upper'], 'json', ['pretty_print' => false]);

        // Second export without transformer should use original data
        $json = $service->export($data, ['name' => 'Name'], 'json', ['pretty_print' => false]);
        $decoded = json_decode($json, true);

        $this->assertSame('Alice', $decoded[0]['Name']);
    }
}
