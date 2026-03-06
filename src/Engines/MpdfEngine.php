<?php

namespace Baidouabdellah\LaravelArpdf\Engines;

use Baidouabdellah\LaravelArpdf\Contracts\PdfEngine;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use RuntimeException;

class MpdfEngine implements PdfEngine
{
    protected array $baseConfig;

    protected ?Mpdf $lastInstance = null;

    public function __construct(array $baseConfig = [])
    {
        $this->baseConfig = $baseConfig;
    }

    public function render(string $html, array $options = []): string
    {
        if (! class_exists(Mpdf::class)) {
            throw new RuntimeException('mpdf/mpdf is not installed. Run: composer require mpdf/mpdf');
        }

        $config = array_replace_recursive($this->baseConfig, $options);
        $direction = strtolower((string) ($config['direction'] ?? 'rtl'));
        $paper = (string) ($config['paper'] ?? 'A4');
        $orientation = $this->normalizeOrientation((string) ($config['orientation'] ?? 'portrait'));
        $tempDir = (string) ($config['temp_dir'] ?? sys_get_temp_dir() . '/laravel-arpdf');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $fontDirs = (new ConfigVariables())->getDefaults()['fontDir'];
        $fontData = (new FontVariables())->getDefaults()['fontdata'];
        $customFonts = $this->buildCustomFonts($config);

        $fontsPath = (string) ($config['fonts_path'] ?? '');
        if ($fontsPath !== '' && is_dir($fontsPath)) {
            $fontDirs[] = $fontsPath;
        }

        $margins = (array) ($config['margins'] ?? []);
        $mpdfOverrides = (array) ($config['mpdf'] ?? []);

        $mpdfConfig = array_replace([
            'mode' => 'utf-8',
            'format' => strtoupper($paper),
            'orientation' => $orientation,
            'tempDir' => $tempDir,
            'fontDir' => array_values(array_unique($fontDirs)),
            'fontdata' => $fontData + $customFonts,
            'default_font' => $this->resolveDefaultFont($config, $customFonts, $fontData),
            'margin_left' => (float) ($margins['left'] ?? 10),
            'margin_right' => (float) ($margins['right'] ?? 10),
            'margin_top' => (float) ($margins['top'] ?? 10),
            'margin_bottom' => (float) ($margins['bottom'] ?? 10),
            'margin_header' => (float) ($margins['header'] ?? 5),
            'margin_footer' => (float) ($margins['footer'] ?? 5),
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
        ], $mpdfOverrides);

        $this->lastInstance = new Mpdf($mpdfConfig);

        $this->lastInstance->SetDirectionality($direction === 'ltr' ? 'ltr' : 'rtl');
        $this->applyMetadata($this->lastInstance, (array) ($config['metadata'] ?? []));

        $headerHtml = (string) ($config['header_html'] ?? '');
        if ($headerHtml !== '') {
            $this->lastInstance->SetHTMLHeader($headerHtml);
        }

        $footerHtml = (string) ($config['footer_html'] ?? '');
        if ($footerHtml !== '') {
            $this->lastInstance->SetHTMLFooter($footerHtml);
        }

        $watermarkText = (string) ($config['watermark_text'] ?? '');
        if ($watermarkText !== '') {
            $this->lastInstance->SetWatermarkText($watermarkText, (float) ($config['watermark_text_alpha'] ?? 0.08));
            $this->lastInstance->showWatermarkText = true;
        }

        $watermarkImage = (string) ($config['watermark_image'] ?? '');
        if ($watermarkImage !== '' && is_file($watermarkImage)) {
            $this->lastInstance->SetWatermarkImage($watermarkImage, (float) ($config['watermark_image_alpha'] ?? 0.2));
            $this->lastInstance->showWatermarkImage = true;
        }

        $css = trim((string) ($config['css'] ?? ''));
        if ($css !== '') {
            $this->lastInstance->WriteHTML($css, HTMLParserMode::HEADER_CSS);
        }

        $this->lastInstance->WriteHTML($html, HTMLParserMode::DEFAULT_MODE);

        return $this->lastInstance->Output('', Destination::STRING_RETURN);
    }

    public function getLastInstance(): ?Mpdf
    {
        return $this->lastInstance;
    }

    protected function buildCustomFonts(array $config): array
    {
        $fonts = (array) ($config['fonts'] ?? []);
        $normalized = [];

        foreach ($fonts as $fontName => $fontFiles) {
            if (! is_array($fontFiles)) {
                continue;
            }

            $key = $this->normalizeFontKey((string) $fontName);
            if ($key === '') {
                continue;
            }

            $normalized[$key] = $fontFiles;
        }

        return $normalized;
    }

    protected function normalizeOrientation(string $orientation): string
    {
        $value = strtolower($orientation);

        return in_array($value, ['landscape', 'l'], true) ? 'L' : 'P';
    }

    protected function resolveDefaultFont(array $config, array $customFonts, array $fontData): string
    {
        $defaultFont = $this->normalizeFontKey((string) ($config['default_font'] ?? ''));
        if ($defaultFont === '') {
            return 'dejavusans';
        }

        if (isset($customFonts[$defaultFont]) || isset($fontData[$defaultFont])) {
            return $defaultFont;
        }

        return 'dejavusans';
    }

    protected function applyMetadata(Mpdf $mpdf, array $metadata): void
    {
        if (isset($metadata['title'])) {
            $mpdf->SetTitle((string) $metadata['title']);
        }

        if (isset($metadata['author'])) {
            $mpdf->SetAuthor((string) $metadata['author']);
        }

        if (isset($metadata['subject'])) {
            $mpdf->SetSubject((string) $metadata['subject']);
        }

        if (isset($metadata['keywords'])) {
            $mpdf->SetKeywords((string) $metadata['keywords']);
        }

        if (isset($metadata['creator'])) {
            $mpdf->SetCreator((string) $metadata['creator']);
        }
    }

    protected function normalizeFontKey(string $font): string
    {
        $font = strtolower(trim($font));

        return str_replace([' ', '-'], '', $font);
    }
}
