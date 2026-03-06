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
        $this->assertStringContainsString('body{color:red;}', (string) ($engine->lastOptions['css'] ?? ''));
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

    public function testAdvancedOptionsAreForwardedToEngine(): void
    {
        $engine = new FakeEngine();

        $pdf = new ArPDF($engine, []);

        $pdf->direction('rtl')
            ->paper('A5', 'landscape')
            ->margins(12, 13, 14, 15, 4, 3)
            ->title('عنوان')
            ->author('كاتب')
            ->header('<div>HEADER</div>')
            ->footer('<div>FOOTER</div>')
            ->watermarkText('سري')
            ->loadHTML('<h1>مرحبا</h1>')
            ->output('doc.pdf', 'S');

        $this->assertSame('rtl', $engine->lastOptions['direction'] ?? null);
        $this->assertSame('A5', $engine->lastOptions['paper'] ?? null);
        $this->assertSame('landscape', $engine->lastOptions['orientation'] ?? null);
        $this->assertSame(12, $engine->lastOptions['margins']['left'] ?? null);
        $this->assertSame('عنوان', $engine->lastOptions['metadata']['title'] ?? null);
        $this->assertSame('كاتب', $engine->lastOptions['metadata']['author'] ?? null);
        $this->assertSame('<div>HEADER</div>', $engine->lastOptions['header_html'] ?? null);
        $this->assertSame('<div>FOOTER</div>', $engine->lastOptions['footer_html'] ?? null);
        $this->assertSame('سري', $engine->lastOptions['watermark_text'] ?? null);
    }
}

class FakeEngine implements PdfEngine
{
    public string $lastHtml = '';
    public array $lastOptions = [];

    public function render(string $html, array $options = []): string
    {
        $this->lastHtml = $html;
        $this->lastOptions = $options;

        return '%PDF-FAKE%';
    }
}
