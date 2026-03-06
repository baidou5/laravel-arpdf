<?php

namespace Baidouabdellah\LaravelArpdf\Contracts;

use Baidouabdellah\LaravelArpdf\ArPDF;

interface PdfPlugin
{
    public function beforeRender(ArPDF $pdf, string $html, array $options): array;

    public function afterRender(ArPDF $pdf, string $binary, array $context): string;
}
