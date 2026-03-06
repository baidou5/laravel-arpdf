<?php

namespace Baidouabdellah\LaravelArpdf\Contracts;

interface PdfEngine
{
    /**
     * Render HTML into raw PDF bytes.
     */
    public function render(string $html, array $options = []): string;
}
