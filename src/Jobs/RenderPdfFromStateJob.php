<?php

namespace Baidouabdellah\LaravelArpdf\Jobs;

use Baidouabdellah\LaravelArpdf\ArPDF;

class RenderPdfFromStateJob
{
    public function __construct(
        protected array $state,
        protected string $outputPath,
        protected ?string $disk = null
    ) {
    }

    public function handle(): void
    {
        $pdf = ArPDF::fromState($this->state);

        if ($this->disk !== null && function_exists('storage_path')) {
            $path = storage_path('app/' . trim($this->outputPath, '/'));
            $pdf->save($path);

            return;
        }

        $pdf->save($this->outputPath);
    }
}
