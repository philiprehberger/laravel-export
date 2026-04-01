<?php

declare(strict_types=1);

namespace PhilipRehberger\Export\Tests;

use Illuminate\Support\Collection;
use Orchestra\Testbench\TestCase;
use PhilipRehberger\Export\ExportServiceProvider;
use PhilipRehberger\Export\Formats\XmlExporter;

class XmlExporterTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [ExportServiceProvider::class];
    }

    private function makeExporter(): XmlExporter
    {
        return new XmlExporter;
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
            'email' => 'Email',
        ];
    }

    public function test_exports_valid_xml_with_root_and_item_elements(): void
    {
        $exporter = $this->makeExporter();
        $xml = $exporter->export($this->makeData(), $this->makeColumns());

        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<items>', $xml);
        $this->assertStringContainsString('</items>', $xml);
        $this->assertStringContainsString('<item>', $xml);
        $this->assertStringContainsString('</item>', $xml);
        $this->assertStringContainsString('<Name>Alice</Name>', $xml);
        $this->assertStringContainsString('<Email>bob@example.com</Email>', $xml);

        // Verify it parses as valid XML
        $doc = simplexml_load_string($xml);
        $this->assertNotFalse($doc);
        $this->assertCount(2, $doc->item);
    }

    public function test_custom_element_names_via_options(): void
    {
        $exporter = $this->makeExporter();
        $xml = $exporter->export($this->makeData(), $this->makeColumns(), [
            'root_element' => 'users',
            'item_element' => 'user',
        ]);

        $this->assertStringContainsString('<users>', $xml);
        $this->assertStringContainsString('</users>', $xml);
        $this->assertStringContainsString('<user>', $xml);
        $this->assertStringContainsString('</user>', $xml);

        $doc = simplexml_load_string($xml);
        $this->assertNotFalse($doc);
        $this->assertCount(2, $doc->user);
    }

    public function test_custom_element_names_via_setters(): void
    {
        $exporter = $this->makeExporter();
        $exporter->setRootElement('records')->setItemElement('record');

        $xml = $exporter->export($this->makeData(), $this->makeColumns());

        $this->assertStringContainsString('<records>', $xml);
        $this->assertStringContainsString('<record>', $xml);
    }

    public function test_proper_escaping_of_special_characters(): void
    {
        $exporter = $this->makeExporter();
        $data = collect([
            ['name' => 'O\'Brien & Sons', 'email' => '<script>alert("xss")</script>'],
        ]);

        $xml = $exporter->export($data, $this->makeColumns());

        $this->assertStringContainsString('O&apos;Brien &amp; Sons', $xml);
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', $xml);

        // Verify it still parses as valid XML
        $doc = simplexml_load_string($xml);
        $this->assertNotFalse($doc);
    }

    public function test_empty_data_produces_valid_xml(): void
    {
        $exporter = $this->makeExporter();
        $xml = $exporter->export(collect(), $this->makeColumns());

        $this->assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $xml);
        $this->assertStringContainsString('<items>', $xml);
        $this->assertStringContainsString('</items>', $xml);
        $this->assertStringNotContainsString('<item>', $xml);

        $doc = simplexml_load_string($xml);
        $this->assertNotFalse($doc);
    }

    public function test_content_type_is_xml(): void
    {
        $exporter = $this->makeExporter();
        $this->assertSame('application/xml', $exporter->getContentType());
    }

    public function test_file_extension_is_xml(): void
    {
        $exporter = $this->makeExporter();
        $this->assertSame('xml', $exporter->getFileExtension());
    }

    public function test_format_name_is_xml(): void
    {
        $exporter = $this->makeExporter();
        $this->assertSame('xml', $exporter->getFormatName());
    }

    public function test_sanitizes_invalid_element_names(): void
    {
        $exporter = $this->makeExporter();
        $data = collect([
            ['full name' => 'Alice', '123field' => 'value'],
        ]);
        $columns = [
            'full name' => 'Full Name',
            '123field' => '123 Field',
        ];

        $xml = $exporter->export($data, $columns);

        // Spaces should become underscores, leading digits get underscore prefix
        $this->assertStringContainsString('<Full_Name>', $xml);
        $this->assertStringContainsString('<_123_Field>', $xml);

        $doc = simplexml_load_string($xml);
        $this->assertNotFalse($doc);
    }
}
