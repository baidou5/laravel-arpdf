<?php

namespace Baidouabdellah\LaravelArpdf;

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class ArPDF
{
    protected Mpdf $mpdf;

    public function __construct(array $config = [])
    {
        // مسار مؤقت للملفات – داخل storage
        $tempDir = storage_path('app/laravel-arpdf');

        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        // إعدادات افتراضية مناسبة للعربية
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
            'default_font'      => 'dejavusans',
            'autoLangToFont'    => true,
            'autoScriptToLang'  => true,
            'directionality'    => 'rtl',
        ];

        $settings = array_merge($default, $config);

        $this->mpdf = new Mpdf($settings);

        // دعم إضافي للخطوط العربية (لو حابب تضيف خطوطك)
        $this->bootstrapArabicFonts();
    }

    /**
     * تهيئة الخطوط العربية الافتراضية (يمكن تطويرها لاحقًا)
     */
    protected function bootstrapArabicFonts(): void
    {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs      = $defaultConfig['fontDir'];

        $fontConfig = (new FontVariables())->getDefaults();
        $fontData   = $fontConfig['fontdata'];

        // بإمكانك إضافة مجلد خطوطك داخل الباكدج ونشره، هنا مثال عام
        $this->mpdf->fontdata = $fontData + [
            'cairo' => [
                'R' => 'Cairo-Regular.ttf',
                'B' => 'Cairo-Bold.ttf',
            ],
        ];

        $this->mpdf->fontDir = array_merge($fontDirs, [
            // ضع هنا مسار مجلد الخطوط لو حابب
            // base_path('resources/fonts'),
        ]);
    }

    /**
     * تحميل HTML (مع CSS لو حابب)
     */
    public function loadHTML(string $html, int $mode = \Mpdf\HTMLParserMode::DEFAULT_MODE): self
    {
        $this->mpdf->WriteHTML($html, $mode);

        return $this;
    }

    /**
     * إضافة CSS منفصل
     */
    public function loadCSS(string $css): self
    {
        $this->mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);

        return $this;
    }

    /**
     * تغيير الاتجاه (rtl / ltr)
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
     * إرجاع استجابة Laravel لعرض PDF في المتصفح (inline)
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
     * إرجاع استجابة Laravel لتحميل الملف (download)
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
