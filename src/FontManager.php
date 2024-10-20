<?php

namespace Baidouabdellah\LaravelArpdf;

class FontManager
{
    public function getDefaultFontObjects()
    {
        return "F1 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n" .
               "F2 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Tajawal >>\nendobj\n";
    }

    public function addFont($name, $details)
    {
        // Handle adding new fonts
    }
}
