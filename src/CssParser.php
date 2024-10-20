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
        // Extract font-family from style, extend this to handle more CSS rules
        return strpos($this->style, 'Tajawal') !== false ? 'F2' : 'F1';
    }
}
