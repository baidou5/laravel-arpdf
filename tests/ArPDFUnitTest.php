<?php

namespace Baidouabdellah\LaravelArpdf\Tests;

use Baidouabdellah\LaravelArpdf\ArPDF;
use Baidouabdellah\LaravelArpdf\ArPdfDestination;
use Baidouabdellah\LaravelArpdf\ArPdfParserMode;
use Baidouabdellah\LaravelArpdf\Contracts\PdfEngine;
use PHPUnit\Framework\TestCase;

class ArPDFUnitTest extends TestCase
{
    public function testCanCreatePdfInstance(): void
    {
        $pdf = new ArPDF(new FakeEngine(), []);

        $this->assertInstanceOf(ArPDF::class, $pdf);
    }

    public function testHeaderCssModeRoutesToCssCollector(): void
    {
        $engine = new FakeEngine();

        $pdf = new ArPDF($engine, [
            'fonts_path' => __DIR__,
            'fonts' => [],
        ]);

        $output = $pdf
            ->loadHTML('body{color:red;}', ArPdfParserMode::HEADER_CSS)
            ->loadHTML('<p>Hello</p>')
            ->output('doc.pdf', ArPdfDestination::STRING_RETURN);

        $this->assertSame('%PDF-FAKE%', $output);
        $this->assertStringContainsString('body{color:red;}', $engine->lastHtml);
        $this->assertStringContainsString('<p>Hello</p>', $engine->lastHtml);
    }

    public function testLegacyDestinationSReturnsBinaryString(): void
    {
        $pdf = new ArPDF(new FakeEngine(), []);

        $output = $pdf
            ->loadHTML('<h1>مرحبا</h1>')
            ->output('doc.pdf', 'S');

        $this->assertSame('%PDF-FAKE%', $output);
    }
}

class FakeEngine implements PdfEngine
{
    public string $lastHtml = '';

    public function render(string $html, array $options = []): string
    {
        $this->lastHtml = $html;

        return '%PDF-FAKE%';
    }
}
