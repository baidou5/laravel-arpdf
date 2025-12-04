<?php

namespace Baidouabdellah\LaravelArpdf;

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class ArPDF
{
    protected Mpdf $mpdf;

    public function __construct(array $overrideConfig = [])
    {
        // نقرأ من config (ممكن تكون غير منشورة بعد، فيستخدم الافتراضي من الباكدج)
        $direction  = $overrideConfig['directionality'] ?? config('arpdf.direction', 'rtl');
        $defaultFont = $overrideConfig['default_font'] ?? config('arpdf.default_font', 'cairo');
        $tempDir    = $overrideConfig['tempDir'] ?? config('arpdf.temp_dir', storage_path('app/laravel-arpdf'));
        $publishedFontsPath = config('arpdf.fonts_path', resource_path('fonts/arpdf'));

        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        // إعدادات mPDF الافتراضية
        $default = [
            'mode'              => 'utf-8',
            'format'            => 'A4',
            'orientation'       => 'P',
            'tempDir'           => $tempDir,
            'margin_left'       => 10,
            'margin_right'      => 10,
            'margin_top'        => 10,
            'margin_bottom'     => 10,
            'margin_header'     => 5,
            'margin_footer'     => 5,
            'default_font_size' => 12,
            'autoLangToFont'    => true,
            'autoScriptToLang'  => true,
            'directionality'    => $direction,
        ];

        // إعدادات mPDF الأصلية
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs      = $defaultConfig['fontDir'];

        $fontConfig = (new FontVariables())->getDefaults();
        $fontData   = $fontConfig['fontdata'];

        // نقرأ الخطوط من config (names => files)
        $extraFonts = config('arpdf.fonts', []);

        // نبني fontdata من config
        $extraFontData = [];
        foreach ($extraFonts as $name => $files) {
            $extraFontData[$name] = $files;
        }

        // ندمج إعداداتنا
        $mpdfConfig = array_merge($default, [
            'fontDir'  => array_merge($fontDirs, [$publishedFontsPath]),
            'fontdata' => $fontData + $extraFontData,
            'default_font' => $defaultFont,
        ]);

        // إنشاء mPDF
        $this->mpdf = new Mpdf($mpdfConfig);

        // إجبار استخدام الخط الافتراضي
        if ($defaultFont) {
            $this->mpdf->SetFont($defaultFont);
        }
    }

    public function loadHTML(string $html, int $mode = \Mpdf\HTMLParserMode::DEFAULT_MODE): self
    {
        $this->mpdf->WriteHTML($html, $mode);
        return $this;
    }

    public function loadCSS(string $css): self
    {
        $this->mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
        return $this;
    }

    public function direction(string $dir = 'rtl'): self
    {
        $this->mpdf->SetDirectionality($dir);
        return $this;
    }

    public function save(string $path): self
    {
        $this->mpdf->Output($path, \Mpdf\Output\Destination::FILE);
        return $this;
    }

    public function stream(string $filename = 'document.pdf')
    {
        $content = $this->mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN);

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    public function download(string $filename = 'document.pdf')
    {
        $content = $this->mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN);

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
