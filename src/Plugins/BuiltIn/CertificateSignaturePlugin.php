<?php

namespace Baidouabdellah\LaravelArpdf\Plugins\BuiltIn;

use Baidouabdellah\LaravelArpdf\ArPDF;
use Baidouabdellah\LaravelArpdf\Contracts\PdfPlugin;
use RuntimeException;

class CertificateSignaturePlugin implements PdfPlugin
{
    public function __construct(
        protected string $privateKeyPath,
        protected ?string $passphrase = null,
        protected ?string $certificatePath = null,
        protected ?string $sidecarPath = null,
        protected int $algorithm = OPENSSL_ALGO_SHA256
    ) {
    }

    public function beforeRender(ArPDF $pdf, string $html, array $options): array
    {
        return ['html' => $html, 'options' => $options];
    }

    public function afterRender(ArPDF $pdf, string $binary, array $context): string
    {
        if (! extension_loaded('openssl')) {
            throw new RuntimeException('OpenSSL extension is required for certificate_signature plugin.');
        }

        $privateKeyPem = @file_get_contents($this->privateKeyPath);
        if (! is_string($privateKeyPem) || $privateKeyPem === '') {
            throw new RuntimeException('Private key file is missing or unreadable: ' . $this->privateKeyPath);
        }

        $privateKey = openssl_pkey_get_private($privateKeyPem, $this->passphrase ?? '');
        if ($privateKey === false) {
            throw new RuntimeException('Unable to read private key (check passphrase).');
        }

        $signature = '';
        if (! openssl_sign($binary, $signature, $privateKey, $this->algorithm)) {
            throw new RuntimeException('OpenSSL failed to sign PDF bytes.');
        }

        $payload = [
            'algorithm' => $this->algorithm,
            'signature_base64' => base64_encode($signature),
            'pdf_sha256' => hash('sha256', $binary),
            'cert_fingerprint_sha256' => $this->certificateFingerprint(),
            'created_at' => gmdate('c'),
        ];

        $sidecar = $this->resolveSidecarPath($context);
        if ($sidecar !== null) {
            $directory = dirname($sidecar);
            if (! is_dir($directory)) {
                mkdir($directory, 0775, true);
            }

            file_put_contents($sidecar, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        return $binary;
    }

    protected function resolveSidecarPath(array $context): ?string
    {
        if ($this->sidecarPath !== null && $this->sidecarPath !== '') {
            return $this->sidecarPath;
        }

        $cacheFile = $context['cache_file'] ?? null;
        if (is_string($cacheFile) && $cacheFile !== '') {
            return $cacheFile . '.sig.json';
        }

        return null;
    }

    protected function certificateFingerprint(): ?string
    {
        if ($this->certificatePath === null || $this->certificatePath === '') {
            return null;
        }

        $cert = @file_get_contents($this->certificatePath);
        if (! is_string($cert) || $cert === '') {
            return null;
        }

        return hash('sha256', $cert);
    }
}
