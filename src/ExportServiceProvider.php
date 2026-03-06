<?php

declare(strict_types=1);

namespace PhilipRehberger\Export;

use Illuminate\Support\ServiceProvider;
use PhilipRehberger\Export\Formats\CsvExporter;
use PhilipRehberger\Export\Formats\JsonExporter;

class ExportServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/laravel-export.php',
            'laravel-export'
        );

        $this->app->singleton(ExportFormatRegistry::class, function (): ExportFormatRegistry {
            $registry = new ExportFormatRegistry;

            // Register built-in formats
            $registry->register(new CsvExporter);
            $registry->register(new JsonExporter);

            return $registry;
        });

        $this->app->singleton(ExportService::class, function ($app): ExportService {
            return new ExportService($app->make(ExportFormatRegistry::class));
        });

        $this->app->alias(ExportService::class, 'laravel-export');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [
                    __DIR__.'/../config/laravel-export.php' => config_path('laravel-export.php'),
                ],
                'laravel-export-config'
            );
        }
    }
}
