<?php

namespace Baidouabdellah\LaravelArpdf\Plugins\BuiltIn;

use Baidouabdellah\LaravelArpdf\ArPDF;
use Baidouabdellah\LaravelArpdf\Contracts\PdfPlugin;

class QuickQrPlugin implements PdfPlugin
{
    public function __construct(
        protected string $text,
        protected int $size = 110,
        protected string $position = 'bottom-left'
    ) {
    }

    public function beforeRender(ArPDF $pdf, string $html, array $options): array
    {
        $size = max(40, min(400, $this->size));
        $url = 'https://quickchart.io/qr?text=' . rawurlencode($this->text) . '&size=' . $size;

        [$vertical, $horizontal] = $this->resolvePosition($this->position);
        $block = '<div style="position:fixed;' . $vertical . ':12px;' . $horizontal . ':12px;z-index:999">'
            . '<img src="' . $url . '" width="' . $size . '" height="' . $size . '" />'
            . '</div>';

        return ['html' => $html . $block, 'options' => $options];
    }

    public function afterRender(ArPDF $pdf, string $binary, array $context): string
    {
        return $binary;
    }

    protected function resolvePosition(string $position): array
    {
        return match ($position) {
            'top-left' => ['top', 'left'],
            'top-right' => ['top', 'right'],
            'bottom-right' => ['bottom', 'right'],
            default => ['bottom', 'left'],
        };
    }
}
