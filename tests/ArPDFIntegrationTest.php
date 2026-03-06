<?php

namespace Baidouabdellah\LaravelArpdf\Tests;

use Baidouabdellah\LaravelArpdf\ArPDF;
use Baidouabdellah\LaravelArpdf\Contracts\PdfEngine;
use Illuminate\Http\Response;
use RuntimeException;

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

    public function testArtisanVerifySignatureCommandReturnsSuccess(): void
    {
        if (! extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension is required for this test.');
        }

        $tmp = sys_get_temp_dir() . '/laravel-arpdf-artisan-' . uniqid('', true);
        if (! is_dir($tmp)) {
            mkdir($tmp, 0775, true);
        }

        $pdfPath = $tmp . '/signed.pdf';
        $sigPath = $tmp . '/signed.sig.json';
        $keyPath = $tmp . '/private.pem';
        $certPath = $tmp . '/cert.pem';

        $pkey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => 2048,
        ]);
        if ($pkey === false) {
            throw new RuntimeException('Failed to create OpenSSL private key for test.');
        }

        $privatePem = '';
        openssl_pkey_export($pkey, $privatePem);
        file_put_contents($keyPath, $privatePem);

        $csr = openssl_csr_new(['commonName' => 'ArPDF Integration'], $pkey);
        if ($csr === false) {
            throw new RuntimeException('Failed to create CSR for test.');
        }

        $cert = openssl_csr_sign($csr, null, $pkey, 1);
        if ($cert === false) {
            throw new RuntimeException('Failed to self-sign certificate for test.');
        }

        $certPem = '';
        openssl_x509_export($cert, $certPem);
        file_put_contents($certPath, $certPem);

        $pdf = new ArPDF(new IntegrationFakeEngine(), []);
        $bytes = $pdf->usePluginNamed('certificate_signature', [
            'private_key' => $keyPath,
            'certificate' => $certPath,
            'sidecar_path' => $sigPath,
        ])->loadHTML('<h1>Signed</h1>')
            ->output('signed.pdf', 'S');

        file_put_contents($pdfPath, (string) $bytes);

        $this->artisan('arpdf:verify-signature', [
            'pdf' => $pdfPath,
            'sidecar' => $sigPath,
            '--cert' => $certPath,
        ])->assertExitCode(0);
    }
}

class IntegrationFakeEngine implements PdfEngine
{
    public function render(string $html, array $options = []): string
    {
        return '%PDF-INTEGRATION%';
    }
}
