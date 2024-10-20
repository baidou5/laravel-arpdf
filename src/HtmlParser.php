<?php

namespace Baidouabdellah\LaravelArpdf;

class HtmlParser
{
    protected $html;

    public function __construct($html)
    {
        $this->html = $html;
    }

    public function parse()
    {
        // Parsing HTML elements, you can extend this to support more tags
        return [
            ['x' => 100, 'y' => 200, 'size' => 12, 'text' => 'Sample Text', 'style' => 'font-family: Tajawal;']
        ];
    }
}
