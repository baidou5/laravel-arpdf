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
     * You can pass your own override settings through $overrideConfig
     */
    public function __construct(array $overrideConfig = [])
    {
        // Read values from config (fallback to default values if not published)
        $direction       = $overrideConfig['directionality'] ?? config('arpdf.direction', 'rtl');
        $defaultFont     = $overrideConfig['default_font']    ?? config('arpdf.default_font', 'cairo');
        $tempDir         = $overrideConfig['tempDir']         ?? config('arpdf.temp_dir', storage_path('app/laravel-arpdf'));
        $publishedFonts  = $overrideConfig['fonts_path']      ?? config('arpdf.fonts_path', resource_path('fonts/arpdf'));

        // Ensure temp directory exists
        if (! is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        // Default mPDF configuration
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs      = $defaultConfig['fontDir'];

        $fontConfig = (new FontVariables())->getDefaults();
        $fontData   = $fontConfig['fontdata'];

        // Extra fonts loaded from config/arpdf.php
        $extraFonts = config('arpdf.fonts', []);

        // Final mPDF configuration (merged with overrides)
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

            // Disable auto font detection to force using cairo font
            'autoLangToFont'    => false,
            'autoScriptToLang'  => false,

            'directionality'    => $direction,

            // Merge default fonts directory with published fonts
            'fontDir'           => array_merge($fontDirs, [$publishedFonts]),
            'fontdata'          => $fontData + $extraFonts,
            'default_font'      => $defaultFont,
        ], $overrideConfig);

        // Initialize mPDF
        $this->mpdf = new Mpdf($mpdfConfig);

        // Ensure default font is applied
        if ($defaultFont) {
            $this->mpdf->SetFont($defaultFont);
        }
    }

    /**
     * Load raw HTML content
     */
    public function loadHTML(string $html, int $mode = HTMLParserMode::DEFAULT_MODE): self
    {
        $this->mpdf->WriteHTML($html, $mode);
        return $this;
    }

    /**
     * Load Blade view directly
     */
    public function loadView(string $view, array $data = [], int $mode = HTMLParserMode::DEFAULT_MODE): self
    {
        $html = view($view, $data)->render();
        return $this->loadHTML($html, $mode);
    }

    /**
     * Load CSS separately
     */
    public function loadCSS(string $css): self
    {
        $this->mpdf->WriteHTML($css, HTMLParserMode::HEADER_CSS);
        return $this;
    }

    /**
     * Change document direction (RTL or LTR)
     */
    public function direction(string $dir = 'rtl'): self
    {
        $this->mpdf->SetDirectionality($dir);
        return $this;
    }

    /**
     * Save PDF file on the server
     */
    public function save(string $path): self
    {
        $this->mpdf->Output($path, Destination::FILE);
        return $this;
    }

    /**
     * Stream PDF inline in browser
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
     * Force PDF download
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
     * Direct interface for mPDF::Output (for advanced users)
     */
    public function output(string $filename = 'document.pdf', string $dest = Destination::INLINE)
    {
        return $this->mpdf->Output($filename, $dest);
    }

    /**
     * Compatibility with older code using render()
     */
    public function render(string $html, string $fileName = 'document.pdf', string $dest = 'I')
    {
        $this->mpdf->WriteHTML($html);
        return $this->mpdf->Output($fileName, $dest);
    }

    /**
     * Return the internal mPDF instance for direct manipulation
     */
    public function getMpdf(): Mpdf
    {
        return $this->mpdf;
    }
}
