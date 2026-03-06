<?php

namespace Baidouabdellah\LaravelArpdf\Console\Commands;

use Baidouabdellah\LaravelArpdf\ArPDF;
use Illuminate\Console\Command;
use Throwable;

class VerifySignatureCommand extends Command
{
    protected $signature = 'arpdf:verify-signature
        {pdf : Absolute or relative path to the PDF file}
        {sidecar : Path to the signature sidecar JSON file}
        {--cert= : Path to certificate file (PEM)}
        {--pub= : Path to public key file (PEM)}';

    protected $description = 'Verify an ArPDF signature sidecar against a PDF file';

    public function handle(): int
    {
        $pdf = (string) $this->argument('pdf');
        $sidecar = (string) $this->argument('sidecar');
        $cert = $this->option('cert');
        $pub = $this->option('pub');

        try {
            $result = ArPDF::verifySignature(
                $pdf,
                $sidecar,
                is_string($cert) && $cert !== '' ? $cert : null,
                is_string($pub) && $pub !== '' ? $pub : null
            );
        } catch (Throwable $e) {
            $this->error('Verification failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->line('hash_matches=' . (($result['hash_matches'] ?? false) ? 'true' : 'false'));
        $signatureVerified = $result['signature_verified'] ?? null;
        $this->line('signature_verified=' . ($signatureVerified === null ? 'null' : ($signatureVerified ? 'true' : 'false')));

        if (($result['valid'] ?? false) === true) {
            $this->info('Signature is valid.');

            return self::SUCCESS;
        }

        $reason = (string) ($result['reason'] ?? 'Signature verification failed.');
        $this->warn($reason);

        return self::FAILURE;
    }
}
