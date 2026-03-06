<?php

namespace Baidouabdellah\LaravelArpdf\Tests;

use Baidouabdellah\LaravelArpdf\ArPDF;
use Baidouabdellah\LaravelArpdf\Contracts\PdfEngine;
use Illuminate\Http\Response;

class ArPDFIntegrationTest extends TestCase
{
    public function testServiceContainerResolvesFreshInstances(): void
    {
        $first = $this->app->make(ArPDF::class);
        $second = $this->app->make(ArPDF::class);

        $this->assertNotSame($first, $second);
    }

    public function testDownloadReturnsPdfResponseWithAttachmentDisposition(): void
    {
        $this->app->bind(PdfEngine::class, fn () => new IntegrationFakeEngine());

        $pdf = $this->app->make(ArPDF::class);
        $response = $pdf->loadHTML('<h1>Invoice</h1>')->download('invoice-ar');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('invoice-ar.pdf', (string) $response->headers->get('Content-Disposition'));
    }
}

class IntegrationFakeEngine implements PdfEngine
{
    public function render(string $html, array $options = []): string
    {
        return '%PDF-INTEGRATION%';
    }
}
