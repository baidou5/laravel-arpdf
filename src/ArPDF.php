<?php
namespace Baidouabdellah\LaravelArpdf;

class ArPDF
{
    protected $content = '';
    protected $objects = [];
    protected $fonts = [];

    public function __construct()
    {
        $this->content = "%PDF-1.7\n"; //start PDF file
        $this->addDefaultFonts();
    }

    public function addText($x, $y, $size, $text, $font = 'F1')
    {
        $this->objects[] = "BT /$font $size Tf $x $y Td ($text) Tj ET";
        return $this;
    }

    public function addArabicText($x, $y, $size, $text, $font = 'F2')
    {
        $text = $this->flipArabicText($text);
        $this->addText($x, $y, $size, $text, $font);
        return $this;
    }

    protected function flipArabicText($text)
    {
        return implode('', array_reverse(mb_str_split($text)));
    }

    protected function addDefaultFonts()
    {
        $this->content .= "F1 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        $this->content .= "F2 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>\nendobj\n"; // للخطوط العربية
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
