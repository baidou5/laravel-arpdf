<?php

namespace Baidouabdellah\LaravelArpdf;

use Baidouabdellah\LaravelArpdf\Contracts\PdfEngine;
use Baidouabdellah\LaravelArpdf\Engines\DompdfEngine;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Throwable;

class ArPDF
{
    protected PdfEngine $engine;

    protected array $config;

    protected array $htmlParts = [];

    protected array $cssParts = [];

    protected string $direction;

    protected ?string $cachedPdf = null;

    public function __construct(?PdfEngine $engine = null, array $overrideConfig = [])
    {
        $this->config = $this->resolveConfig($overrideConfig);
        $this->direction = strtolower((string) ($this->config['direction'] ?? 'rtl'));
        $this->engine = $engine ?? new DompdfEngine($this->config);

        $tempDir = (string) ($this->config['temp_dir'] ?? '');
        if ($tempDir !== '' && ! is_dir($tempDir)) {
            mkdir($tempDir, 0775, true);
        }
    }

    public function loadHTML(string $html, int $mode = ArPdfParserMode::DEFAULT_MODE): self
    {
        if ($mode === ArPdfParserMode::HEADER_CSS) {
            return $this->loadCSS($html);
        }

        $this->htmlParts[] = $html;
        $this->cachedPdf = null;

        return $this;
    }

    public function loadView(string $view, array $data = [], int $mode = ArPdfParserMode::DEFAULT_MODE): self
    {
        if (! function_exists('view')) {
            throw new RuntimeException('The view() helper is unavailable in the current context.');
        }

        $html = view($view, $data)->render();

        return $this->loadHTML($html, $mode);
    }

    public function loadCSS(string $css): self
    {
        $this->cssParts[] = $css;
        $this->cachedPdf = null;

        return $this;
    }

    public function direction(string $dir = 'rtl'): self
    {
        $normalized = strtolower($dir);
        if (! in_array($normalized, ['rtl', 'ltr'], true)) {
            throw new InvalidArgumentException('Direction must be either "rtl" or "ltr".');
        }

        $this->direction = $normalized;
        $this->cachedPdf = null;

        return $this;
    }

    public function save(string $path): self
    {
        $this->ensureParentDirectoryExists($path);
        file_put_contents($path, $this->renderBinary());

        return $this;
    }

    public function stream(string $filename = 'document.pdf')
    {
        $content = $this->renderBinary();

        return $this->makeBinaryResponse($content, $filename, false);
    }

    public function download(string $filename = 'document.pdf')
    {
        $content = $this->renderBinary();

        return $this->makeBinaryResponse($content, $filename, true);
    }

    public function output(string $filename = 'document.pdf', string $dest = ArPdfDestination::INLINE)
    {
        $destination = $this->normalizeDestination($dest);
        $content = $this->renderBinary();

        if ($destination === ArPdfDestination::STRING_RETURN) {
            return $content;
        }

        if ($destination === ArPdfDestination::FILE) {
            $this->ensureParentDirectoryExists($filename);
            file_put_contents($filename, $content);

            return null;
        }

        if ($destination === ArPdfDestination::DOWNLOAD) {
            return $this->makeBinaryResponse($content, $filename, true);
        }

        return $this->makeBinaryResponse($content, $filename, false);
    }

    public function render(string $html, string $fileName = 'document.pdf', string $dest = 'I')
    {
        $this->reset();
        $this->loadHTML($html);

        return $this->output($fileName, $dest);
    }

    public function getMpdf()
    {
        throw new RuntimeException(
            'getMpdf() is no longer available because this package no longer depends on mPDF.'
        );
    }

    public function getEngine(): PdfEngine
    {
        return $this->engine;
    }

    public function reset(): self
    {
        $this->htmlParts = [];
        $this->cssParts = [];
        $this->cachedPdf = null;

        return $this;
    }

    protected function renderBinary(): string
    {
        if ($this->cachedPdf !== null) {
            return $this->cachedPdf;
        }

        $html = $this->buildHtmlDocument();
        $this->cachedPdf = $this->engine->render($html, $this->config);

        return $this->cachedPdf;
    }

    protected function buildHtmlDocument(): string
    {
        $bootstrapCss = $this->buildBootstrapCss();
        $customCss = implode("\n", $this->cssParts);
        $content = implode("\n", $this->htmlParts);

        return '<!doctype html><html lang="ar" dir="' . $this->direction . '"><head><meta charset="UTF-8">'
            . '<style>' . $bootstrapCss . "\n" . $customCss . '</style></head><body>'
            . $content
            . '</body></html>';
    }

    protected function buildBootstrapCss(): string
    {
        $css = [];
        $defaultFont = (string) ($this->config['default_font'] ?? 'sans-serif');
        $fontPath = rtrim((string) ($this->config['fonts_path'] ?? ''), '/');
        $fontMap = (array) ($this->config['fonts'] ?? []);

        foreach ($fontMap as $fontName => $fontFiles) {
            if (! is_array($fontFiles)) {
                continue;
            }

            $regularFile = $fontFiles['R'] ?? null;
            $boldFile = $fontFiles['B'] ?? null;

            if (is_string($regularFile) && $fontPath !== '') {
                $fullPath = $fontPath . '/' . ltrim($regularFile, '/');
                if (is_file($fullPath)) {
                    $css[] = "@font-face{font-family:'{$fontName}';font-style:normal;font-weight:400;src:url('"
                        . $this->toCssFileUrl($fullPath)
                        . "') format('truetype');}";
                }
            }

            if (is_string($boldFile) && $fontPath !== '') {
                $fullPath = $fontPath . '/' . ltrim($boldFile, '/');
                if (is_file($fullPath)) {
                    $css[] = "@font-face{font-family:'{$fontName}';font-style:normal;font-weight:700;src:url('"
                        . $this->toCssFileUrl($fullPath)
                        . "') format('truetype');}";
                }
            }
        }

        $css[] = "html,body{direction:{$this->direction};font-family:'{$defaultFont}',sans-serif;}";

        return implode("\n", $css);
    }

    protected function toCssFileUrl(string $path): string
    {
        $normalized = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        $encoded = rawurlencode($normalized);
        $encoded = str_replace('%2F', '/', $encoded);

        return 'file://' . $encoded;
    }

    protected function normalizeDestination(string $dest): string
    {
        $value = strtolower($dest);

        $map = [
            'i' => ArPdfDestination::INLINE,
            'd' => ArPdfDestination::DOWNLOAD,
            'f' => ArPdfDestination::FILE,
            's' => ArPdfDestination::STRING_RETURN,
            ArPdfDestination::INLINE => ArPdfDestination::INLINE,
            ArPdfDestination::DOWNLOAD => ArPdfDestination::DOWNLOAD,
            ArPdfDestination::FILE => ArPdfDestination::FILE,
            ArPdfDestination::STRING_RETURN => ArPdfDestination::STRING_RETURN,
        ];

        if (! isset($map[$value])) {
            throw new InvalidArgumentException('Unsupported output destination: ' . $dest);
        }

        return $map[$value];
    }

    protected function makeBinaryResponse(string $content, string $filename, bool $asAttachment)
    {
        $safeFilename = $this->ensurePdfFilename($filename);
        $dispositionType = $asAttachment ? 'attachment' : 'inline';

        if (class_exists(HeaderUtils::class)) {
            $disposition = HeaderUtils::makeDisposition($dispositionType, $safeFilename);
        } else {
            $disposition = $dispositionType . '; filename="' . addslashes($safeFilename) . '"';
        }

        if (function_exists('response')) {
            return response($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => $disposition,
                'Content-Length' => (string) strlen($content),
            ]);
        }

        return $content;
    }

    protected function ensurePdfFilename(string $filename): string
    {
        return str_ends_with(strtolower($filename), '.pdf') ? $filename : $filename . '.pdf';
    }

    protected function ensureParentDirectoryExists(string $path): void
    {
        $directory = dirname($path);
        if ($directory !== '.' && ! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
    }

    protected function resolveConfig(array $overrideConfig): array
    {
        $defaultConfig = [
            'direction' => 'rtl',
            'default_font' => 'cairo',
            'temp_dir' => sys_get_temp_dir() . '/laravel-arpdf',
            'fonts_path' => '',
            'fonts' => [],
            'paper' => 'a4',
            'orientation' => 'portrait',
            'enable_remote_assets' => false,
            'dompdf_options' => [
                'isHtml5ParserEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'defaultPaperSize' => 'a4',
            ],
        ];

        $frameworkConfig = [];
        if ($this->hasConfigBinding()) {
            $frameworkConfig = (array) config('arpdf', []);
        }

        if (! isset($frameworkConfig['fonts_path'])) {
            $frameworkConfig['fonts_path'] = $this->safeResourcePath('fonts/arpdf');
        }

        if (! isset($frameworkConfig['temp_dir'])) {
            $frameworkConfig['temp_dir'] = $this->safeStoragePath('app/laravel-arpdf');
        }

        $config = array_replace_recursive($defaultConfig, $frameworkConfig, $overrideConfig);

        return $this->normalizeFontConfig($config);
    }

    protected function normalizeFontConfig(array $config): array
    {
        $fontsPath = (string) ($config['fonts_path'] ?? '');
        $fonts = (array) ($config['fonts'] ?? []);

        if (! $this->hasUsableMappedFont($fontsPath, $fonts)) {
            $packageFontsPath = $this->packageFontsPath();
            if ($packageFontsPath !== null) {
                $config['fonts_path'] = $packageFontsPath;

                if (! isset($fonts['cairo'])) {
                    $fonts['cairo'] = [
                        'R' => 'Cairo-Regular.ttf',
                        'B' => 'Cairo-Bold.ttf',
                    ];
                }

                $config['fonts'] = $fonts;
            }
        }

        if (! $this->isDefaultFontAvailable($config)) {
            $config['default_font'] = 'DejaVu Sans';
        }

        return $config;
    }

    protected function hasUsableMappedFont(string $fontsPath, array $fonts): bool
    {
        if ($fontsPath === '' || ! is_dir($fontsPath)) {
            return false;
        }

        foreach ($fonts as $font) {
            if (! is_array($font)) {
                continue;
            }

            $regular = $font['R'] ?? null;
            if (! is_string($regular)) {
                continue;
            }

            $candidate = rtrim($fontsPath, '/\\') . '/' . ltrim($regular, '/\\');
            if (is_file($candidate)) {
                return true;
            }
        }

        return false;
    }

    protected function isDefaultFontAvailable(array $config): bool
    {
        $defaultFont = trim((string) ($config['default_font'] ?? ''));
        if ($defaultFont === '') {
            return false;
        }

        $builtinFonts = [
            'serif',
            'sans-serif',
            'monospace',
            'cursive',
            'fantasy',
            'helvetica',
            'times-roman',
            'courier',
            'dejavu sans',
            'dejavu serif',
            'dejavu sans mono',
        ];

        if (in_array(strtolower($defaultFont), $builtinFonts, true)) {
            return true;
        }

        $fontsPath = (string) ($config['fonts_path'] ?? '');
        $fonts = (array) ($config['fonts'] ?? []);
        $fontConfig = $fonts[$defaultFont] ?? $fonts[strtolower($defaultFont)] ?? null;
        if (! is_array($fontConfig)) {
            return false;
        }

        $regular = $fontConfig['R'] ?? null;
        if (! is_string($regular) || $fontsPath === '') {
            return false;
        }

        $candidate = rtrim($fontsPath, '/\\') . '/' . ltrim($regular, '/\\');

        return is_file($candidate);
    }

    protected function packageFontsPath(): ?string
    {
        $path = dirname(__DIR__) . '/resources/fonts';

        return is_dir($path) ? $path : null;
    }

    protected function hasConfigBinding(): bool
    {
        if (! function_exists('app') || ! function_exists('config')) {
            return false;
        }

        try {
            return app()->bound('config');
        } catch (Throwable) {
            return false;
        }
    }

    protected function safeResourcePath(string $path): string
    {
        if (function_exists('resource_path')) {
            try {
                return resource_path($path);
            } catch (Throwable) {
            }
        }

        return getcwd() . '/resources/' . ltrim($path, '/');
    }

    protected function safeStoragePath(string $path): string
    {
        if (function_exists('storage_path')) {
            try {
                return storage_path($path);
            } catch (Throwable) {
            }
        }

        return sys_get_temp_dir() . '/laravel-arpdf';
    }
}
