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

    public function __construct(array $baseConfig = [])
    {
        $this->baseConfig = $baseConfig;
    }

    public function render(string $html, array $options = []): string
    {
        if (! class_exists(Mpdf::class)) {
            throw new RuntimeException(
                'mpdf/mpdf is not installed. Run: composer require mpdf/mpdf'
            );
        }

        $config = array_replace_recursive($this->baseConfig, $options);
        $direction = strtolower((string) ($config['direction'] ?? 'rtl'));
        $paper = (string) ($config['paper'] ?? 'A4');
        $orientation = strtolower((string) ($config['orientation'] ?? 'portrait')) === 'landscape' ? 'L' : 'P';
        $tempDir = (string) ($config['temp_dir'] ?? sys_get_temp_dir() . '/laravel-arpdf');

        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }

        $fontDirs = (new ConfigVariables())->getDefaults()['fontDir'];
        $fontData = (new FontVariables())->getDefaults()['fontdata'];
        $custom = $this->buildCustomFonts($config);

        $fontsPath = (string) ($config['fonts_path'] ?? '');
        if ($fontsPath !== '' && is_dir($fontsPath)) {
            $fontDirs[] = $fontsPath;
        }

        $defaultFont = $this->normalizeDefaultFont((string) ($config['default_font'] ?? 'dejavusans'));
        if (! isset($custom[$defaultFont]) && ! isset($fontData[$defaultFont])) {
            $defaultFont = 'dejavusans';
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => strtoupper($paper),
            'orientation' => $orientation,
            'tempDir' => $tempDir,
            'fontDir' => array_values(array_unique($fontDirs)),
            'fontdata' => $fontData + $custom,
            'default_font' => $defaultFont,
        ]);

        $mpdf->SetDirectionality($direction === 'ltr' ? 'ltr' : 'rtl');
        $mpdf->WriteHTML($html, HTMLParserMode::DEFAULT_MODE);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }

    protected function buildCustomFonts(array $config): array
    {
        $fonts = (array) ($config['fonts'] ?? []);
        $normalized = [];

        foreach ($fonts as $fontName => $fontFiles) {
            if (! is_array($fontFiles)) {
                continue;
            }

            $key = $this->normalizeDefaultFont((string) $fontName);
            if ($key === '') {
                continue;
            }

            $normalized[$key] = $fontFiles;
        }

        return $normalized;
    }

    protected function normalizeDefaultFont(string $font): string
    {
        $font = strtolower(trim($font));
        $font = str_replace([' ', '-'], '', $font);

        return $font;
    }
}
