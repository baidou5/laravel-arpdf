<?php

namespace Baidouabdellah\LaravelArpdf;

use Baidouabdellah\LaravelArpdf\Contracts\PdfEngine;
use Baidouabdellah\LaravelArpdf\Engines\MpdfEngine;
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

    protected array $runtimeOptions = [];

    protected array $profiles = [];

    protected array $templates = [];

    protected bool $cacheEnabled = false;

    protected ?int $cacheTtlSeconds = null;

    protected ?string $cachedPdf = null;

    public function __construct(?PdfEngine $engine = null, array $overrideConfig = [])
    {
        $this->config = $this->resolveConfig($overrideConfig);
        $this->direction = strtolower((string) ($this->config['direction'] ?? 'rtl'));
        $this->engine = $engine ?? new MpdfEngine($this->config);
        $this->runtimeOptions = [
            'paper' => $this->config['paper'],
            'orientation' => $this->config['orientation'],
            'margins' => $this->config['margins'],
            'metadata' => $this->config['metadata'],
        ];
        $this->profiles = (array) ($this->config['profiles'] ?? []);
        $this->templates = (array) ($this->config['templates'] ?? []);
        $this->cacheEnabled = (bool) (($this->config['cache']['enabled'] ?? false) === true);
        $this->cacheTtlSeconds = isset($this->config['cache']['ttl_seconds'])
            ? (int) $this->config['cache']['ttl_seconds']
            : null;

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

    public function paper(string $format = 'A4', string $orientation = 'portrait'): self
    {
        $orientation = strtolower($orientation);
        if (! in_array($orientation, ['portrait', 'landscape', 'p', 'l'], true)) {
            throw new InvalidArgumentException('Orientation must be portrait/landscape.');
        }

        $this->runtimeOptions['paper'] = strtoupper($format);
        $this->runtimeOptions['orientation'] = in_array($orientation, ['landscape', 'l'], true)
            ? 'landscape'
            : 'portrait';
        $this->cachedPdf = null;

        return $this;
    }

    public function margins(
        int|float $left,
        int|float $right,
        int|float $top,
        int|float $bottom,
        int|float $header = 5,
        int|float $footer = 5
    ): self {
        $this->runtimeOptions['margins'] = [
            'left' => $left,
            'right' => $right,
            'top' => $top,
            'bottom' => $bottom,
            'header' => $header,
            'footer' => $footer,
        ];
        $this->cachedPdf = null;

        return $this;
    }

    public function header(string $html): self
    {
        $this->runtimeOptions['header_html'] = $html;
        $this->cachedPdf = null;

        return $this;
    }

    public function footer(string $html): self
    {
        $this->runtimeOptions['footer_html'] = $html;
        $this->cachedPdf = null;

        return $this;
    }

    public function watermarkText(string $text, float $alpha = 0.08): self
    {
        $this->runtimeOptions['watermark_text'] = $text;
        $this->runtimeOptions['watermark_text_alpha'] = $alpha;
        $this->cachedPdf = null;

        return $this;
    }

    public function watermarkImage(string $path, float $alpha = 0.2): self
    {
        $this->runtimeOptions['watermark_image'] = $path;
        $this->runtimeOptions['watermark_image_alpha'] = $alpha;
        $this->cachedPdf = null;

        return $this;
    }

    public function metadata(array $metadata): self
    {
        $existing = (array) ($this->runtimeOptions['metadata'] ?? []);
        $this->runtimeOptions['metadata'] = array_merge($existing, $metadata);
        $this->cachedPdf = null;

        return $this;
    }

    public function title(string $title): self
    {
        return $this->metadata(['title' => $title]);
    }

    public function author(string $author): self
    {
        return $this->metadata(['author' => $author]);
    }

    public function subject(string $subject): self
    {
        return $this->metadata(['subject' => $subject]);
    }

    public function keywords(string $keywords): self
    {
        return $this->metadata(['keywords' => $keywords]);
    }

    public function creator(string $creator): self
    {
        return $this->metadata(['creator' => $creator]);
    }

    public function option(string $key, mixed $value): self
    {
        $this->runtimeOptions[$key] = $value;
        $this->cachedPdf = null;

        return $this;
    }

    public function options(array $options): self
    {
        $this->runtimeOptions = array_replace_recursive($this->runtimeOptions, $options);
        $this->cachedPdf = null;

        return $this;
    }

    public function pageBreak(): self
    {
        $this->htmlParts[] = '<pagebreak />';
        $this->cachedPdf = null;

        return $this;
    }

    public function profile(string $name): self
    {
        $profile = $this->profiles[$name] ?? null;
        if (! is_array($profile)) {
            throw new InvalidArgumentException('Unknown profile: ' . $name);
        }

        if (isset($profile['direction'])) {
            $this->direction((string) $profile['direction']);
            unset($profile['direction']);
        }

        return $this->options($profile);
    }

    public function registerProfile(string $name, array $options): self
    {
        $this->profiles[$name] = $options;

        return $this;
    }

    public function registerTemplate(string $name, callable|string $template): self
    {
        $this->templates[$name] = $template;

        return $this;
    }

    public function loadTemplate(string $name, array $data = []): self
    {
        if (! array_key_exists($name, $this->templates)) {
            throw new InvalidArgumentException('Unknown template: ' . $name);
        }

        $template = $this->templates[$name];
        $html = $this->renderTemplateValue($template, $data);

        return $this->loadHTML($html);
    }

    public function useCache(bool $enabled = true, ?int $ttlSeconds = null): self
    {
        $this->cacheEnabled = $enabled;
        if ($ttlSeconds !== null) {
            $this->cacheTtlSeconds = $ttlSeconds;
        }

        $this->cachedPdf = null;

        return $this;
    }

    public function clearCache(): self
    {
        $cachePath = $this->cachePath();
        if ($cachePath === null || ! is_dir($cachePath)) {
            return $this;
        }

        foreach (glob($cachePath . '/*.pdf') ?: [] as $file) {
            @unlink($file);
        }

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
        return $this->makeBinaryResponse($this->renderBinary(), $filename, false);
    }

    public function download(string $filename = 'document.pdf')
    {
        return $this->makeBinaryResponse($this->renderBinary(), $filename, true);
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

    public function getMpdf(): ?\Mpdf\Mpdf
    {
        if ($this->engine instanceof MpdfEngine) {
            if ($this->cachedPdf === null) {
                $this->renderBinary();
            }

            return $this->engine->getLastInstance();
        }

        return null;
    }

    public function getEngine(): PdfEngine
    {
        return $this->engine;
    }

    public function useEngine(PdfEngine $engine): self
    {
        $this->engine = $engine;
        $this->cachedPdf = null;

        return $this;
    }

    public function reset(): self
    {
        $this->htmlParts = [];
        $this->cssParts = [];
        $this->runtimeOptions = [
            'paper' => $this->config['paper'],
            'orientation' => $this->config['orientation'],
            'margins' => $this->config['margins'],
            'metadata' => $this->config['metadata'],
        ];
        $this->direction = strtolower((string) ($this->config['direction'] ?? 'rtl'));
        $this->cachedPdf = null;

        return $this;
    }

    protected function renderBinary(): string
    {
        if ($this->cachedPdf !== null) {
            return $this->cachedPdf;
        }

        $options = $this->buildRenderOptions();
        $content = implode("\n", $this->htmlParts);
        $cacheFile = $this->resolveCacheFile($content, $options);
        if ($cacheFile !== null && is_file($cacheFile)) {
            $this->cachedPdf = (string) file_get_contents($cacheFile);

            return $this->cachedPdf;
        }

        $this->cachedPdf = $this->engine->render($content, $options);
        if ($cacheFile !== null) {
            file_put_contents($cacheFile, $this->cachedPdf);
        }

        return $this->cachedPdf;
    }

    protected function buildRenderOptions(): array
    {
        $css = trim($this->buildBootstrapCss() . "\n" . implode("\n", $this->cssParts));

        return array_replace_recursive($this->config, $this->runtimeOptions, [
            'direction' => $this->direction,
            'css' => $css,
        ]);
    }

    protected function renderTemplateValue(callable|string $template, array $data): string
    {
        if (is_callable($template)) {
            $result = $template($data, $this);
            if (! is_string($result)) {
                throw new RuntimeException('Template callable must return HTML string.');
            }

            return $result;
        }

        $value = $template;
        if (is_file($value)) {
            $value = (string) file_get_contents($value);
        } elseif (! str_contains($value, '<') && $this->hasConfigBinding() && function_exists('view')) {
            return (string) view($value, $data)->render();
        }

        return $this->interpolateTemplate($value, $data);
    }

    protected function interpolateTemplate(string $template, array $data): string
    {
        return (string) preg_replace_callback('/{{\s*([a-zA-Z0-9_.-]+)\s*}}/', function (array $matches) use ($data) {
            $value = $this->getArrayValueByPath($data, $matches[1]);

            return is_scalar($value) ? (string) $value : '';
        }, $template);
    }

    protected function getArrayValueByPath(array $data, string $path): mixed
    {
        $current = $data;
        foreach (explode('.', $path) as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }

    protected function buildBootstrapCss(): string
    {
        $css = [];
        $defaultFont = (string) ($this->config['default_font'] ?? 'sans-serif');
        $fontPath = rtrim((string) ($this->config['fonts_path'] ?? ''), '/\\');
        $fontMap = (array) ($this->config['fonts'] ?? []);

        foreach ($fontMap as $fontName => $fontFiles) {
            if (! is_array($fontFiles)) {
                continue;
            }

            $regularFile = $fontFiles['R'] ?? null;
            $boldFile = $fontFiles['B'] ?? null;

            if (is_string($regularFile) && $fontPath !== '') {
                $fullPath = $fontPath . '/' . ltrim($regularFile, '/\\');
                if (is_file($fullPath)) {
                    $css[] = "@font-face{font-family:'{$fontName}';font-style:normal;font-weight:400;src:url('"
                        . $this->toCssFileUrl($fullPath)
                        . "') format('truetype');}";
                }
            }

            if (is_string($boldFile) && $fontPath !== '') {
                $fullPath = $fontPath . '/' . ltrim($boldFile, '/\\');
                if (is_file($fullPath)) {
                    $css[] = "@font-face{font-family:'{$fontName}';font-style:normal;font-weight:700;src:url('"
                        . $this->toCssFileUrl($fullPath)
                        . "') format('truetype');}";
                }
            }
        }

        $align = $this->direction === 'rtl' ? 'right' : 'left';
        $css[] = "html,body{direction:{$this->direction};text-align:{$align};font-family:'{$defaultFont}','DejaVu Sans',sans-serif;}";

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
            'paper' => 'A4',
            'orientation' => 'portrait',
            'margins' => [
                'left' => 10,
                'right' => 10,
                'top' => 10,
                'bottom' => 10,
                'header' => 5,
                'footer' => 5,
            ],
            'metadata' => [
                'creator' => 'laravel-arpdf',
            ],
            'mpdf' => [
                'mode' => 'utf-8',
                'autoLangToFont' => true,
                'autoScriptToLang' => true,
            ],
            'profiles' => [],
            'templates' => [],
            'cache' => [
                'enabled' => false,
                'ttl_seconds' => 3600,
                'path' => '',
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
        if (! isset($frameworkConfig['cache']['path'])) {
            $frameworkConfig['cache']['path'] = $this->safeStoragePath('app/laravel-arpdf/cache');
        }

        $config = array_replace_recursive($defaultConfig, $frameworkConfig, $overrideConfig);

        return $this->normalizeFontConfig($config);
    }

    protected function resolveCacheFile(string $content, array $options): ?string
    {
        if (! $this->cacheEnabled) {
            return null;
        }

        $cachePath = $this->cachePath();
        if ($cachePath === null) {
            return null;
        }

        if (! is_dir($cachePath)) {
            mkdir($cachePath, 0775, true);
        }

        $signature = [
            'engine' => get_class($this->engine),
            'direction' => $this->direction,
            'content' => $content,
            'options' => $this->normalizeForHash($options),
        ];
        $hash = hash('sha256', json_encode($signature, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $file = rtrim($cachePath, '/\\') . '/' . $hash . '.pdf';

        $ttl = $this->cacheTtlSeconds;
        if ($ttl !== null && $ttl > 0 && is_file($file)) {
            $age = time() - (int) filemtime($file);
            if ($age > $ttl) {
                @unlink($file);
            }
        }

        return $file;
    }

    protected function cachePath(): ?string
    {
        $path = (string) ($this->config['cache']['path'] ?? '');

        return $path !== '' ? $path : null;
    }

    protected function normalizeForHash(mixed $value): mixed
    {
        if (is_array($value)) {
            ksort($value);
            foreach ($value as $key => $item) {
                $value[$key] = $this->normalizeForHash($item);
            }

            return $value;
        }

        if (is_object($value)) {
            return $this->normalizeForHash((array) $value);
        }

        if (is_resource($value)) {
            return null;
        }

        return $value;
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
            $config['default_font'] = 'dejavusans';
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

        $fontsPath = (string) ($config['fonts_path'] ?? '');
        $fonts = (array) ($config['fonts'] ?? []);

        $builtinFonts = ['dejavusans', 'dejavuserif', 'dejavusansmono'];
        $normalized = strtolower(str_replace([' ', '-'], '', $defaultFont));

        if (in_array($normalized, $builtinFonts, true)) {
            return true;
        }

        foreach ($fonts as $fontName => $fontConfig) {
            if (! is_array($fontConfig)) {
                continue;
            }

            $key = strtolower(str_replace([' ', '-'], '', (string) $fontName));
            if ($key !== $normalized) {
                continue;
            }

            $regular = $fontConfig['R'] ?? null;
            if (! is_string($regular) || $fontsPath === '') {
                return false;
            }

            return is_file(rtrim($fontsPath, '/\\') . '/' . ltrim($regular, '/\\'));
        }

        return false;
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
