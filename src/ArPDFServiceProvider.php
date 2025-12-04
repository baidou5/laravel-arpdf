<?php

namespace Baidouabdellah\LaravelArpdf;

use Illuminate\Support\ServiceProvider;

class ArPDFServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // نسجّل الـ ArPDF كـ singleton في الـ container
        $this->app->singleton(ArPDF::class, function ($app) {
            return new ArPDF();
        });

        // Alias لإسم الخدمة عشان تستخدمه الـ Facade
        $this->app->alias(ArPDF::class, 'ArPDF');
    }

    public function boot(): void
    {
        // في المستقبل تقدر تضيف:
        // - نشر ملف config
        // - نشر خطوط عربية، إلخ...
    }
}
