<?php

namespace Baidouabdellah\LaravelArpdf;

use Baidouabdellah\LaravelArpdf\Console\Commands\VerifySignatureCommand;
use Baidouabdellah\LaravelArpdf\Contracts\PdfEngine;
use Baidouabdellah\LaravelArpdf\Engines\MpdfEngine;
use Illuminate\Support\ServiceProvider;

class ArPDFServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/arpdf.php', 'arpdf');

        $this->app->bind(PdfEngine::class, function () {
            return new MpdfEngine((array) config('arpdf', []));
        });

        $this->app->bind(ArPDF::class, function ($app) {
            return new ArPDF(
                $app->make(PdfEngine::class),
                (array) config('arpdf', [])
            );
        });

        $this->app->alias(ArPDF::class, 'ArPDF');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                VerifySignatureCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__ . '/../config/arpdf.php' => config_path('arpdf.php'),
        ], 'arpdf-config');

        $this->publishes([
            __DIR__ . '/../resources/fonts' => resource_path('fonts/arpdf'),
        ], 'arpdf-fonts');

        $this->publishes([
            __DIR__ . '/../config/arpdf.php' => config_path('arpdf.php'),
            __DIR__ . '/../resources/fonts' => resource_path('fonts/arpdf'),
        ], 'arpdf');
    }
}
