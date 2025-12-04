<?php

namespace Baidouabdellah\LaravelArpdf;

class CssParser
{
    protected $style;

    public function __construct($style)
    {
        $this->style = $style;
    }


    public function getFont()
{
    if (preg_match('/font-family\s*:\s*([^;]+)/', $this->style, $matches)) {
        $fontFamily = trim($matches[1]);
        return $fontFamily === 'Tajawal' ? 'F2' : 'F1';
    }
    return 'F1'; 
}
}
