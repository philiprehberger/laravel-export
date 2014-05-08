# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2025-03-05

### Added
- Initial release
- `ExportFormatInterface` contract for pluggable format strategies
- `ExportableInterface` contract for Eloquent model export support
- `ExportFormatRegistry` for registering and retrieving format implementations
- `ExportService` with `export`, `exportModels`, `download`, `stream`, and `downloadModels` methods
- `CsvExporter` with UTF-8 BOM, configurable delimiter, enclosure, and header options
- `JsonExporter` with pretty-print and optional metadata envelope
- `ExportServiceProvider` with auto-registration of CSV and JSON formats
- `Export` facade for convenient static access
- Publishable config at `config/laravel-export.php`
- Support for Laravel 11 and Laravel 12
- Support for PHP 8.2+
