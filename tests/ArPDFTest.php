<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Baidouabdellah\LaravelArpdf\ArPDF;

class ArPDFTest extends TestCase
{
    public function testExample()
    {
        $pdf = new ArPDF();
        // ضع هنا اختباراتك للتحقق من أن الكائن تم إنشاؤه بنجاح
        $this->assertInstanceOf(ArPDF::class, $pdf);
    }
}
