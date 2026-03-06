<?php

namespace Baidouabdellah\LaravelArpdf\Testing;

use RuntimeException;

class PdfSnapshotManager
{
    public function assertSnapshot(string $name, string $bytes, string $directory, bool $update = false): array
    {
        if ($name === '') {
            throw new RuntimeException('Snapshot name cannot be empty.');
        }

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $path = rtrim($directory, '/\\') . '/' . $this->normalizeName($name) . '.sha256';
        $hash = hash('sha256', $bytes);
        $exists = is_file($path);

        if (! $exists || $update) {
            file_put_contents($path, $hash . PHP_EOL);

            return [
                'matched' => true,
                'created' => ! $exists,
                'updated' => $update,
                'hash' => $hash,
                'path' => $path,
            ];
        }

        $expected = trim((string) file_get_contents($path));

        return [
            'matched' => hash_equals($expected, $hash),
            'created' => false,
            'updated' => false,
            'expected' => $expected,
            'actual' => $hash,
            'path' => $path,
        ];
    }

    protected function normalizeName(string $name): string
    {
        $normalized = preg_replace('/[^a-zA-Z0-9_.-]/', '-', $name) ?? $name;
        $normalized = trim($normalized, '-_.');

        return $normalized === '' ? 'snapshot' : $normalized;
    }
}
