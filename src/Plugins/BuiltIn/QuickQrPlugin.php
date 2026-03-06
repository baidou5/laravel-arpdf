<?php

namespace Baidouabdellah\LaravelArpdf\Plugins\BuiltIn;

use Baidouabdellah\LaravelArpdf\ArPDF;
use Baidouabdellah\LaravelArpdf\Contracts\PdfPlugin;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

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
        $svg = $this->generateSvg($this->text);

        [$vertical, $horizontal] = $this->resolvePosition($this->position);
        $block = '<div style="position:fixed;' . $vertical . ':12px;' . $horizontal . ':12px;z-index:999;width:' . $size . 'px;height:' . $size . 'px">'
            . '<div style="width:' . $size . 'px;height:' . $size . 'px">' . $svg . '</div>'
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

    protected function generateSvg(string $text): string
    {
        if (! class_exists(QRCode::class)) {
            return '<div style="border:1px solid #999;padding:6px;font-size:9px">QR: ' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</div>';
        }

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_M,
            'scale' => 4,
            'imageBase64' => false,
            'addQuietzone' => true,
        ]);

        return (new QRCode($options))->render($text);
    }
}
