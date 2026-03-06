<?php

namespace Baidouabdellah\LaravelArpdf\Security;

use RuntimeException;

class SignatureVerifier
{
    public function verifyFiles(
        string $pdfPath,
        string $sidecarPath,
        ?string $certificatePath = null,
        ?string $publicKeyPath = null
    ): array {
        if (! is_file($pdfPath)) {
            throw new RuntimeException('PDF file not found: ' . $pdfPath);
        }

        if (! is_file($sidecarPath)) {
            throw new RuntimeException('Signature sidecar file not found: ' . $sidecarPath);
        }

        $payload = json_decode((string) file_get_contents($sidecarPath), true);
        if (! is_array($payload)) {
            throw new RuntimeException('Invalid signature sidecar JSON: ' . $sidecarPath);
        }

        return $this->verifyBytes(
            (string) file_get_contents($pdfPath),
            $payload,
            $certificatePath,
            $publicKeyPath
        );
    }

    public function verifyBytes(
        string $pdfBytes,
        array $payload,
        ?string $certificatePath = null,
        ?string $publicKeyPath = null
    ): array {
        if (! extension_loaded('openssl')) {
            throw new RuntimeException('OpenSSL extension is required for signature verification.');
        }

        $actualHash = hash('sha256', $pdfBytes);
        $expectedHash = (string) ($payload['pdf_sha256'] ?? '');
        $hashMatches = $expectedHash !== '' && hash_equals($expectedHash, $actualHash);

        $signatureBase64 = (string) ($payload['signature_base64'] ?? '');
        if ($signatureBase64 === '') {
            return [
                'valid' => false,
                'hash_matches' => $hashMatches,
                'signature_verified' => false,
                'reason' => 'Missing signature_base64 in payload',
            ];
        }

        $signature = base64_decode($signatureBase64, true);
        if ($signature === false) {
            return [
                'valid' => false,
                'hash_matches' => $hashMatches,
                'signature_verified' => false,
                'reason' => 'Invalid base64 signature',
            ];
        }

        $algo = (int) ($payload['algorithm'] ?? OPENSSL_ALGO_SHA256);
        $publicKey = $this->resolvePublicKey($certificatePath, $publicKeyPath);

        if ($publicKey === null) {
            return [
                'valid' => $hashMatches,
                'hash_matches' => $hashMatches,
                'signature_verified' => null,
                'reason' => 'No certificate/public key provided for cryptographic verification',
            ];
        }

        $verify = openssl_verify($pdfBytes, $signature, $publicKey, $algo);
        $signatureVerified = ($verify === 1);

        return [
            'valid' => $hashMatches && $signatureVerified,
            'hash_matches' => $hashMatches,
            'signature_verified' => $signatureVerified,
            'reason' => $signatureVerified ? null : 'openssl_verify failed',
        ];
    }

    protected function resolvePublicKey(?string $certificatePath, ?string $publicKeyPath): mixed
    {
        if ($certificatePath !== null && $certificatePath !== '' && is_file($certificatePath)) {
            $certificate = (string) file_get_contents($certificatePath);

            return openssl_pkey_get_public($certificate) ?: null;
        }

        if ($publicKeyPath !== null && $publicKeyPath !== '' && is_file($publicKeyPath)) {
            $publicKey = (string) file_get_contents($publicKeyPath);

            return openssl_pkey_get_public($publicKey) ?: null;
        }

        return null;
    }
}
