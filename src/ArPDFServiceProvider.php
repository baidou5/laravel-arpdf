<?php

namespace Baidouabdellah\LaravelArpdf;

use Illuminate\Support\ServiceProvider;

class ArPDFServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Merge the package configuration file with the application's config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/arpdf.php',
            'arpdf'
        );

        // Register ArPDF as a singleton inside the service container
        $this->app->singleton(ArPDF::class, function ($app) {
            // Load settings from config to pass into the class
            $config = [
                'directionality' => config('arpdf.direction', 'rtl'),
                'default_font'   => config('arpdf.default_font', 'cairo'),
                'tempDir'        => config('arpdf.temp_dir', storage_path('app/laravel-arpdf')),
            ];

            return new ArPDF($config);
        });

        // Register alias for easier static access via Facade
        $this->app->alias(ArPDF::class, 'ArPDF');
    }

    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__ . '/../config/arpdf.php' => config_path('arpdf.php'),
        ], 'arpdf-config');

        // Publish fonts directory
        $this->publishes([
            __DIR__ . '/../resources/fonts' => resource_path('fonts/arpdf'),
        ], 'arpdf-fonts');

        // Publish everything together (config + fonts)
        $this->publishes([
            __DIR__ . '/../config/arpdf.php' => config_path('arpdf.php'),
            __DIR__ . '/../resources/fonts'  => resource_path('fonts/arpdf'),
        ], 'arpdf');
    }
}
