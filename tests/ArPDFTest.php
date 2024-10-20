<?php

namespace Baidouabdellah\LaravelArpdf\Tests;

use PHPUnit\Framework\TestCase;
use Baidouabdellah\LaravelArpdf\ArPDF;

class ArPDFTest extends TestCase
{
    public function testCanCreatePDF()
    {
        $pdf = new ArPDF();
        $this->assertInstanceOf(ArPDF::class, $pdf);
    }

    public function testAddText()
    {
        $pdf = new ArPDF();
        $pdf->addText(100, 200, 12, 'Hello World');
        $this->assertTrue(true); // You can assert more details as per your logic
    }

    public function testAddHtml()
    {
        $pdf = new ArPDF();
        $html = '<p style="font-family:Tajawal;">Hello</p>';
        $pdf->addHtml($html);
        $this->assertTrue(true); // You can assert the logic of HTML parsing
    }
}
