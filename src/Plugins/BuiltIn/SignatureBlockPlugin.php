<?php

namespace Baidouabdellah\LaravelArpdf\Plugins\BuiltIn;

use Baidouabdellah\LaravelArpdf\ArPDF;
use Baidouabdellah\LaravelArpdf\Contracts\PdfPlugin;

class SignatureBlockPlugin implements PdfPlugin
{
    public function __construct(
        protected string $signer,
        protected ?string $title = null,
        protected ?string $imagePath = null,
        protected bool $rtl = true
    ) {
    }

    public function beforeRender(ArPDF $pdf, string $html, array $options): array
    {
        $align = $this->rtl ? 'right' : 'left';
        $title = $this->title !== null ? '<div style="font-size:11px;color:#555">' . htmlspecialchars($this->title, ENT_QUOTES, 'UTF-8') . '</div>' : '';
        $image = '';

        if ($this->imagePath !== null && is_file($this->imagePath)) {
            $src = 'file://' . str_replace('%2F', '/', rawurlencode(str_replace(DIRECTORY_SEPARATOR, '/', $this->imagePath)));
            $image = '<div style="margin:6px 0"><img src="' . $src . '" style="max-height:50px;max-width:160px" /></div>';
        }

        $block = '<div style="margin-top:20px;text-align:' . $align . ';">'
            . $image
            . '<div style="font-weight:bold">' . htmlspecialchars($this->signer, ENT_QUOTES, 'UTF-8') . '</div>'
            . $title
            . '<div style="margin-top:6px;border-top:1px solid #bbb;width:180px;display:inline-block"></div>'
            . '</div>';

        return ['html' => $html . $block, 'options' => $options];
    }

    public function afterRender(ArPDF $pdf, string $binary, array $context): string
    {
        return $binary;
    }
}
