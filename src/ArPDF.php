<?php

namespace Baidouabdellah\LaravelArpdf;

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\HTMLParserMode;
use Mpdf\Output\Destination;

class ArPDF
{
    protected Mpdf $mpdf;

    /**
     * ArPDF constructor.
     *
     * يمكنك تمرير إعدادات إضافية من عندك عبر $overrideConfig
     */
    public function __construct(array $overrideConfig = [])
    {
        // نقرأ من config (لو مش منشورة يرجع للقيم الافتراضية)
        $direction       = $overrideConfig['directionality'] ?? config('arpdf.direction', 'rtl');
        $defaultFont     = $overrideConfig['default_font']    ?? config('arpdf.default_font', 'cairo');
        $tempDir         = $overrideConfig['tempDir']         ?? config('arpdf.temp_dir', storage_path('app/laravel-arpdf'));
        $publishedFonts  = $overrideConfig['fonts_path']      ?? config('arpdf.fonts_path', resource_path('fonts/arpdf'));

        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        // إعدادات mPDF الافتراضية
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs      = $defaultConfig['fontDir'];

        $fontConfig = (new FontVariables())->getDefaults();
        $fontData   = $fontConfig['fontdata'];

        // الخطوط الإضافية من config/arpdf.php
        $extraFonts = config('arpdf.fonts', []);

        $mpdfConfig = array_merge([
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

            // أهم حاجة: نضيف مجلد الخطوط ونعرّف fontdata
            'fontDir'           => array_merge($fontDirs, [$publishedFonts]),
            'fontdata'          => $fontData + $extraFonts,
            'default_font'      => $defaultFont,
        ], $overrideConfig);

        $this->mpdf = new Mpdf($mpdfConfig);

        // نضمن استعمال الخط الافتراضي
        if ($defaultFont) {
            $this->mpdf->SetFont($defaultFont);
        }
    }

    /**
     * تحميل HTML (سلسلي)
     */
    public function loadHTML(string $html, int $mode = HTMLParserMode::DEFAULT_MODE): self
    {
        $this->mpdf->WriteHTML($html, $mode);
        return $this;
    }

    /**
     * تحميل View Blade مباشرة
     */
    public function loadView(string $view, array $data = [], int $mode = HTMLParserMode::DEFAULT_MODE): self
    {
        $html = view($view, $data)->render();
        return $this->loadHTML($html, $mode);
    }

    /**
     * تحميل CSS منفصل
     */
    public function loadCSS(string $css): self
    {
        $this->mpdf->WriteHTML($css, HTMLParserMode::HEADER_CSS);
        return $this;
    }

    /**
     * تغيير اتجاه الصفحة (RTL أو LTR)
     */
    public function direction(string $dir = 'rtl'): self
    {
        $this->mpdf->SetDirectionality($dir);
        return $this;
    }

    /**
     * حفظ الملف على السيرفر
     */
    public function save(string $path): self
    {
        $this->mpdf->Output($path, Destination::FILE);
        return $this;
    }

    /**
     * عرض PDF داخل المتصفح (inline)
     */
    public function stream(string $filename = 'document.pdf')
    {
        $content = $this->mpdf->Output($filename, Destination::STRING_RETURN);

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
        $content = $this->mpdf->Output($filename, Destination::STRING_RETURN);

        return response($content, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * واجهة مباشرة لـ mPDF::Output (للي يحب يستخدمها)
     */
    public function output(string $filename = 'document.pdf', string $dest = Destination::INLINE)
    {
        return $this->mpdf->Output($filename, $dest);
    }

    /**
     * للتوافق مع الكود القديم اللي يستعمل render()
     */
    public function render(string $html, string $fileName = 'document.pdf', string $dest = 'I')
    {
        $this->mpdf->WriteHTML($html);
        return $this->mpdf->Output($fileName, $dest);
    }

    /**
     * ترجيع كائن mPDF لو حبيت تتعامل معه مباشرة
     */
    public function getMpdf(): Mpdf
    {
        return $this->mpdf;
    }
}
