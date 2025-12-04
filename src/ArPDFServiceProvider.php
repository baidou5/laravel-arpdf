<?php

namespace Baidouabdellah\LaravelArpdf;

use Illuminate\Support\ServiceProvider;

class ArPDFServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // دمج ملف الإعدادات
        $this->mergeConfigFrom(
            __DIR__ . '/../config/arpdf.php',
            'arpdf'
        );

        // تسجيل الـ ArPDF في الـ container
        $this->app->singleton(ArPDF::class, function ($app) {
            // نمرر إعدادات من config إلى الكلاس
            $config = [
                'directionality' => config('arpdf.direction', 'rtl'),
                'default_font'   => config('arpdf.default_font', 'cairo'),
                'tempDir'        => config('arpdf.temp_dir', storage_path('app/laravel-arpdf')),
            ];

            return new ArPDF($config);
        });

        $this->app->alias(ArPDF::class, 'ArPDF');
    }

    public function boot(): void
    {
        // نشر ملف الإعدادات
        $this->publishes([
            __DIR__ . '/../config/arpdf.php' => config_path('arpdf.php'),
        ], 'arpdf-config');

        // نشر الخطوط
        $this->publishes([
            __DIR__ . '/../resources/fonts' => resource_path('fonts/arpdf'),
        ], 'arpdf-fonts');

        // نشر الكل معًا
        $this->publishes([
            __DIR__ . '/../config/arpdf.php' => config_path('arpdf.php'),
            __DIR__ . '/../resources/fonts'  => resource_path('fonts/arpdf'),
        ], 'arpdf');
    }
}
