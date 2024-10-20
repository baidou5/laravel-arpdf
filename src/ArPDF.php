<?php

namespace Baidouabdellah\LaravelArpdf;

use Baidouabdellah\LaravelArpdf\FontManager;
use Baidouabdellah\LaravelArpdf\HtmlParser;
use Baidouabdellah\LaravelArpdf\CssParser;

class ArPDF
{
    protected $content = '';
    protected $objects = [];
    protected $fonts = [];

    public function __construct()
    {
        $this->content = "%PDF-1.7\n";
        $this->addDefaultFonts();
    }

    public function addText($x, $y, $size, $text, $font = 'F1')
    {
        $this->objects[] = "BT /$font $size Tf $x $y Td ($text) Tj ET";
        return $this;
    }

    public function addHtml($html)
    {
        $parser = new HtmlParser($html);
        $elements = $parser->parse();

        foreach ($elements as $element) {
            $css = new CssParser($element['style']);
            $this->addText($element['x'], $element['y'], $element['size'], $element['text'], $css->getFont());
        }
        return $this;
    }

    protected function addDefaultFonts()
    {
        $fontManager = new FontManager();
        $this->content .= $fontManager->getDefaultFontObjects();
    }

    public function renderContent()
    {
        foreach ($this->objects as $object) {
            $this->content .= "3 0 obj\n<< /Length 44 >>\nstream\n$object\nendstream\nendobj\n";
        }
    }

    public function save($path)
    {
        $this->renderContent();
        $this->content .= "%%EOF";
        file_put_contents($path, $this->content);
    }

    public function stream($filename = 'document.pdf')
    {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        echo $this->content;
    }
}
