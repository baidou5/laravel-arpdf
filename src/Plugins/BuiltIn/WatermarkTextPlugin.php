<?php

namespace Baidouabdellah\LaravelArpdf\Plugins\BuiltIn;

use Baidouabdellah\LaravelArpdf\ArPDF;
use Baidouabdellah\LaravelArpdf\Contracts\PdfPlugin;

class WatermarkTextPlugin implements PdfPlugin
{
    public function __construct(
        protected string $text,
        protected float $alpha = 0.1
    ) {
    }

    public function beforeRender(ArPDF $pdf, string $html, array $options): array
    {
        $options['watermark_text'] = $this->text;
        $options['watermark_text_alpha'] = $this->alpha;

        return ['html' => $html, 'options' => $options];
    }

    public function afterRender(ArPDF $pdf, string $binary, array $context): string
    {
        return $binary;
    }
}
