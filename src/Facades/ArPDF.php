<?php

namespace Baidouabdellah\LaravelArpdf\Facades;

use Illuminate\Support\Facades\Facade;

class ArPDF extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        // Must match the alias registered in the service provider
        return 'ArPDF';
    }
}
