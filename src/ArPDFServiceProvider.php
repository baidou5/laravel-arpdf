<?php

namespace Baidouabdellah\LaravelArpdf;

use Illuminate\Support\ServiceProvider;

class ArPDFServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('ArPDF', function ($app) {
            return new ArPDF();
        });
    }

    public function boot()
    {
        // You can add any actions you need while loading the package.

    }
}
