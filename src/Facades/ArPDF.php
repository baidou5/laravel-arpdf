<?php

namespace Baidouabdellah\LaravelArpdf\Facades;

use Illuminate\Support\Facades\Facade;

class ArPDF extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        // لازم يكون نفس الـ alias المسجَّل في service provider
        return 'ArPDF';
    }
}
