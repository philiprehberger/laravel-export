# Changelog

All notable changes to `laravel-export` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.8] - 2026-03-31

### Changed
- Standardize README to 3-badge format with emoji Support section
- Update CI checkout action to v5 for Node.js 24 compatibility
- Add GitHub issue templates, dependabot config, and PR template

## [1.1.7] - 2026-03-23

### Fixed
- Standardize CHANGELOG preamble to use package name

## [1.1.6] - 2026-03-21

### Changed
- Consolidate README and configuration updates from diverged branch

## [1.1.4] - 2026-03-17

### Fixed
- Add phpstan.neon configuration for CI static analysis

## [1.1.3] - 2026-03-17

### Changed
- Standardized package metadata, README structure, and CI workflow per package guide

## [1.1.2] - 2026-03-16

### Changed
- Standardize composer.json: add type, homepage, scripts
- Add Development section to README

## [1.1.1] - 2026-03-15

### Changed
- Add README badges

## [1.1.0] - 2026-03-13

### Fixed
- HTTP header injection vulnerability in `Content-Disposition` filenames — control characters, quotes, and path separators are now stripped
- `json_encode()` failures in nested data transformations no longer silently produce `false`

### Removed
- Unused `default_format` config option (was defined but never read by any code path)

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
