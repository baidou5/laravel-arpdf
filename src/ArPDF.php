<?php

namespace Baidouabdellah\LaravelArpdf;

use Mpdf\Mpdf;

class ArPDF
{
    protected Mpdf $mpdf;

    public function __construct(array $overrideConfig = [])
    {
<<<<<<< HEAD
        // نقرأ من config (ممكن تكون غير منشورة بعد، فيستخدم الافتراضي من الباكدج)
        $direction          = $overrideConfig['directionality'] ?? config('arpdf.direction', 'rtl');
        $defaultFont        = $overrideConfig['default_font'] ?? config('arpdf.default_font', 'cairo');
        $tempDir            = $overrideConfig['tempDir'] ?? config('arpdf.temp_dir', storage_path('app/laravel-arpdf'));
        $publishedFontsPath = config('arpdf.fonts_path', resource_path('fonts/arpdf'));
=======
        // مجلد مؤقت داخل storage
        $tempDir = storage_path('app/laravel-arpdf');
>>>>>>> ce37a1cbe35ef647e1e6bf6207699cdda59662ad

        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

<<<<<<< HEAD
        // إعدادات mPDF الافتراضية
=======
        // إعدادات افتراضية مناسبة للعربية
>>>>>>> ce37a1cbe35ef647e1e6bf6207699cdda59662ad
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

<<<<<<< HEAD
        // إعدادات mPDF الأصلية
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs      = $defaultConfig['fontDir'];

        $fontConfig = (new FontVariables())->getDefaults();
        $fontData   = $fontConfig['fontdata'];

        // نقرأ الخطوط من config (name => files array)
        $extraFonts = config('arpdf.fonts', []);

        // نبني fontdata من config
        $extraFontData = [];
        foreach ($extraFonts as $name => $files) {
            $extraFontData[$name] = $files;
        }

        // ندمج إعداداتنا مع الافتراضية
        $mpdfConfig = array_merge($default, [
            'fontDir'  => array_merge($fontDirs, [$publishedFontsPath]),
            'fontdata' => $fontData + $extraFontData,
            'default_font' => $defaultFont,
        ]);

        // إنشاء mPDF
        $this->mpdf = new Mpdf($mpdfConfig);

        // إجبار استخدام الخط الافتراضي لو تم تحديده
        if (! empty($defaultFont)) {
            $this->mpdf->SetFont($defaultFont);
        }
    }

    /**
     * تحميل HTML إلى المستند
=======
        $settings = array_merge($default, $config);

        $this->mpdf = new Mpdf($settings);
        // لا نلمس fontDir ولا fontdata مباشرة في mPDF 8
    }

    /**
     * تحميل HTML
>>>>>>> ce37a1cbe35ef647e1e6bf6207699cdda59662ad
     */
    public function loadHTML(string $html, int $mode = \Mpdf\HTMLParserMode::DEFAULT_MODE): self
    {
        $this->mpdf->WriteHTML($html, $mode);
        return $this;
    }

    /**
     * تحميل CSS منفصل
     */
    public function loadCSS(string $css): self
    {
        $this->mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
        return $this;
    }

    /**
     * تغيير اتجاه المستند (rtl / ltr)
     */
    public function direction(string $dir = 'rtl'): self
    {
        $this->mpdf->SetDirectionality($dir);
        return $this;
    }

    /**
     * حفظ الملف على القرص
     */
    public function save(string $path): self
    {
        $this->mpdf->Output($path, \Mpdf\Output\Destination::FILE);
        return $this;
    }

    /**
<<<<<<< HEAD
     * عرض PDF داخل المتصفح (inline)
=======
     * عرض PDF في المتصفح (inline)
>>>>>>> ce37a1cbe35ef647e1e6bf6207699cdda59662ad
     */
    public function stream(string $filename = 'document.pdf')
    {
        $content = $this->mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN);

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * تحميل PDF (download)
     */
    public function download(string $filename = 'document.pdf')
    {
        $content = $this->mpdf->Output($filename, \Mpdf\Output\Destination::STRING_RETURN);

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
