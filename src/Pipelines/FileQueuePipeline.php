<?php

namespace Baidouabdellah\LaravelArpdf\Pipelines;

use Baidouabdellah\LaravelArpdf\ArPDF;
use RuntimeException;

class FileQueuePipeline
{
    protected string $queuePath;

    public function __construct(string $queuePath)
    {
        $this->queuePath = rtrim($queuePath, '/\\');
        $this->ensureDirectory($this->pendingPath());
        $this->ensureDirectory($this->donePath());
        $this->ensureDirectory($this->failedPath());
    }

    public function enqueue(ArPDF $pdf, string $outputPath): string
    {
        $id = date('YmdHis') . '-' . bin2hex(random_bytes(6));
        $payload = [
            'id' => $id,
            'output_path' => $outputPath,
            'state' => $pdf->exportState(),
            'created_at' => time(),
        ];

        $file = $this->pendingPath() . '/' . $id . '.json';
        file_put_contents($file, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $id;
    }

    public function processNext(): ?array
    {
        $jobs = glob($this->pendingPath() . '/*.json') ?: [];
        sort($jobs);

        if ($jobs === []) {
            return null;
        }

        $file = $jobs[0];
        $payload = json_decode((string) file_get_contents($file), true);

        if (! is_array($payload)) {
            rename($file, $this->failedPath() . '/' . basename($file));
            throw new RuntimeException('Invalid queue payload: ' . basename($file));
        }

        try {
            $pdf = ArPDF::fromState((array) ($payload['state'] ?? []));
            $pdf->save((string) ($payload['output_path'] ?? 'document.pdf'));
            $payload['processed_at'] = time();
            $doneFile = $this->donePath() . '/' . basename($file);
            file_put_contents($doneFile, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            unlink($file);

            return $payload;
        } catch (\Throwable $e) {
            $payload['error'] = $e->getMessage();
            $payload['failed_at'] = time();
            $failedFile = $this->failedPath() . '/' . basename($file);
            file_put_contents($failedFile, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            unlink($file);

            throw $e;
        }
    }

    public function pendingCount(): int
    {
        return count(glob($this->pendingPath() . '/*.json') ?: []);
    }

    public function pendingPath(): string
    {
        return $this->queuePath . '/pending';
    }

    public function donePath(): string
    {
        return $this->queuePath . '/done';
    }

    public function failedPath(): string
    {
        return $this->queuePath . '/failed';
    }

    protected function ensureDirectory(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }
}
