<?php

namespace Baidouabdellah\LaravelArpdf;

class Helpers
{
    public static function flipArabicText($text)
    {
        return implode('', array_reverse(mb_str_split($text)));
    }
}
