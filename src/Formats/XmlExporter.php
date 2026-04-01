<?php

declare(strict_types=1);

namespace PhilipRehberger\Export\Formats;

use Illuminate\Support\Collection;

/**
 * XML export format implementation.
 *
 * Generates well-formed XML documents with configurable root and item element names.
 * Nested arrays are rendered as child elements; scalar values as text content.
 */
class XmlExporter extends AbstractExportFormat
{
    /**
     * The root element name wrapping all items.
     */
    protected string $rootElement = 'items';

    /**
     * The element name for each row/item.
     */
    protected string $itemElement = 'item';

    /**
     * Export data to XML format.
     *
     * @param  Collection<int, mixed>  $data
     * @param  array<string, string>  $columns  Key => Header mapping
     */
    public function export(Collection $data, array $columns, array $options = []): string
    {
        $rootElement = $options['root_element'] ?? $this->rootElement;
        $itemElement = $options['item_element'] ?? $this->itemElement;

        $transformedData = $this->transformData($data, $columns);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<'.$this->sanitizeElementName($rootElement).'>'."\n";

        foreach ($transformedData as $row) {
            $xml .= '  <'.$this->sanitizeElementName($itemElement).'>'."\n";
            foreach ($row as $key => $value) {
                $elementName = $this->sanitizeElementName((string) $key);
                $xml .= $this->buildElement($elementName, $value, 4);
            }
            $xml .= '  </'.$this->sanitizeElementName($itemElement).'>'."\n";
        }

        $xml .= '</'.$this->sanitizeElementName($rootElement).'>'."\n";

        return $xml;
    }

    /**
     * Get the MIME content type for XML.
     */
    public function getContentType(): string
    {
        return 'application/xml';
    }

    /**
     * Get the file extension for XML.
     */
    public function getFileExtension(): string
    {
        return 'xml';
    }

    /**
     * Get the format name/identifier.
     */
    public function getFormatName(): string
    {
        return 'xml';
    }

    /**
     * Set the root element name.
     */
    public function setRootElement(string $rootElement): self
    {
        $this->rootElement = $rootElement;

        return $this;
    }

    /**
     * Set the item element name.
     */
    public function setItemElement(string $itemElement): self
    {
        $this->itemElement = $itemElement;

        return $this;
    }

    /**
     * Build an XML element string, handling nested arrays recursively.
     */
    private function buildElement(string $name, mixed $value, int $indent): string
    {
        $padding = str_repeat(' ', $indent);

        if (is_array($value)) {
            $xml = $padding.'<'.$name.'>'."\n";
            foreach ($value as $childKey => $childValue) {
                $childName = is_int($childKey) ? 'item' : $this->sanitizeElementName((string) $childKey);
                $xml .= $this->buildElement($childName, $childValue, $indent + 2);
            }
            $xml .= $padding.'</'.$name.'>'."\n";

            return $xml;
        }

        return $padding.'<'.$name.'>'.$this->escapeXmlValue((string) $value).'</'.$name.'>'."\n";
    }

    /**
     * Sanitize a string to be a valid XML element name.
     *
     * Replaces spaces and invalid characters with underscores. Ensures the name
     * starts with a letter or underscore.
     */
    private function sanitizeElementName(string $name): string
    {
        // Replace spaces and invalid characters with underscores
        $name = (string) preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $name);

        // Ensure name starts with a letter or underscore
        if ($name !== '' && ! preg_match('/^[a-zA-Z_]/', $name)) {
            $name = '_'.$name;
        }

        return $name !== '' ? $name : '_element';
    }

    /**
     * Escape a value for safe inclusion in XML text content.
     */
    private function escapeXmlValue(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
