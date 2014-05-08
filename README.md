# laravel-export

Registry-based data export system for Laravel with pluggable format support. Ships with CSV and JSON exporters out of the box. Extend with your own formats by implementing a single interface.

## Requirements

- PHP 8.2+
- Laravel 11 or 12

## Installation

```bash
composer require philiprehberger/laravel-export
```

The service provider is auto-discovered via Laravel's package discovery.

Optionally publish the config file:

```bash
php artisan vendor:publish --tag=laravel-export-config
```

## Basic Usage

### Export a Collection to CSV

```php
use PhilipRehberger\Export\Facades\Export;
use Illuminate\Support\Collection;

$data = collect([
    ['name' => 'Alice', 'email' => 'alice@example.com', 'plan' => 'pro'],
    ['name' => 'Bob',   'email' => 'bob@example.com',   'plan' => 'free'],
]);

$columns = [
    'name'  => 'Name',
    'email' => 'Email Address',
    'plan'  => 'Plan',
];

$csvString = Export::export($data, $columns, 'csv');
```

### Export a Collection to JSON

```php
$jsonString = Export::export($data, $columns, 'json');
```

### Download Response (CSV)

Return a download response directly from a controller:

```php
public function download(Request $request): Response
{
    $users = User::all();

    $data = $users->map(fn ($u) => [
        'name'       => $u->name,
        'email'      => $u->email,
        'created_at' => $u->created_at,
    ]);

    $columns = [
        'name'       => 'Name',
        'email'      => 'Email',
        'created_at' => 'Registered',
    ];

    return Export::download($data, $columns, 'csv', 'users');
    // Sends users.csv with Content-Disposition: attachment
}
```

### Stream Response (for large datasets)

```php
return Export::stream($data, $columns, 'csv', 'users');
// Returns a StreamedResponse
```

## ExportableInterface on Models

Implement `ExportableInterface` on your Eloquent model to let the service derive columns and filename automatically:

```php
use PhilipRehberger\Export\Contracts\ExportableInterface;

class User extends Model implements ExportableInterface
{
    public function toExportArray(): array
    {
        return [
            'name'       => $this->name,
            'email'      => $this->email,
            'plan'       => $this->plan,           // enums are auto-unwrapped
            'created_at' => $this->created_at,     // Carbon dates are auto-formatted
        ];
    }

    public static function getExportColumns(): array
    {
        return [
            'name'       => 'Name',
            'email'      => 'Email Address',
            'plan'       => 'Plan',
            'created_at' => 'Registered',
        ];
    }

    public static function getExportFilename(): string
    {
        return 'users-export';
    }
}
```

Then export a collection of models:

```php
$users = User::where('active', true)->get();

// Returns the CSV string
$csv = Export::exportModels($users, 'csv');

// Returns a download Response, filename derived from model
return Export::downloadModels($users, 'csv');

// Override filename
return Export::downloadModels($users, 'csv', 'active-users');
```

### Automatic Value Transformations

`AbstractExportFormat` handles these transformations automatically inside `toExportArray` or when using raw collections:

| Type | Transformation |
|------|---------------|
| `BackedEnum` | `->value` |
| `Carbon\Carbon` | `->toDateTimeString()` (Y-m-d H:i:s) |
| `array` / `object` | `json_encode()` |

## Creating a Custom Format Exporter

Implement `ExportFormatInterface` (or extend the provided `AbstractExportFormat` base class):

```php
use Illuminate\Support\Collection;
use PhilipRehberger\Export\Formats\AbstractExportFormat;

class XmlExporter extends AbstractExportFormat
{
    public function export(Collection $data, array $columns, array $options = []): string
    {
        $rows = $this->transformData($data, $columns);

        $xml = new SimpleXMLElement('<export/>');
        foreach ($rows as $row) {
            $item = $xml->addChild('item');
            foreach ($row as $key => $value) {
                $item->addChild(preg_replace('/\s+/', '_', $key), htmlspecialchars((string) $value));
            }
        }

        return $xml->asXML();
    }

    public function getContentType(): string { return 'application/xml'; }
    public function getFileExtension(): string { return 'xml'; }
    public function getFormatName(): string { return 'xml'; }
}
```

Register it in a service provider:

```php
use PhilipRehberger\Export\ExportFormatRegistry;

public function boot(ExportFormatRegistry $registry): void
{
    $registry->register(new XmlExporter);
}
```

Then use it exactly like the built-in formats:

```php
Export::download($data, $columns, 'xml', 'report');
```

## Checking Available Formats

```php
Export::supportsFormat('csv');       // true
Export::supportsFormat('excel');     // false (not registered)
Export::getAvailableFormats();       // ['csv', 'json']
Export::getFormatMetadata();         // [['name'=>'csv', 'extension'=>'csv', 'contentType'=>'text/csv; charset=UTF-8'], ...]
```

## Configuration Reference

Published at `config/laravel-export.php`:

```php
return [
    'default_format' => 'csv',

    'csv' => [
        'delimiter'       => ',',
        'enclosure'       => '"',
        'include_bom'     => true,   // UTF-8 BOM for Excel compatibility
        'include_headers' => true,
    ],

    'json' => [
        'pretty_print'      => true,
        'include_metadata'  => false, // wraps output in {metadata:{}, data:[]}
    ],
];
```

### Option Reference

| Option | Format | Default | Description |
|--------|--------|---------|-------------|
| `delimiter` | CSV | `,` | Field separator |
| `enclosure` | CSV | `"` | String enclosure character |
| `include_bom` | CSV | `true` | Prepend UTF-8 BOM (improves Excel compatibility) |
| `include_headers` | CSV | `true` | Write column headers as the first row |
| `pretty_print` | JSON | `true` | Human-readable indented output |
| `include_metadata` | JSON | `false` | Wrap array in `{metadata, data}` envelope |

Options can be passed per-call to override the config defaults:

```php
Export::export($data, $columns, 'csv', [
    'delimiter'   => ';',
    'include_bom' => false,
]);
```

## Running the Tests

```bash
composer install
vendor/bin/phpunit
```

## License

MIT. See [LICENSE](LICENSE).
