<?php

namespace Baidouabdellah\LaravelArpdf\Engines;

use Baidouabdellah\LaravelArpdf\Contracts\PdfEngine;
use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;

class DompdfEngine implements PdfEngine
{
    protected array $baseConfig;

    public function __construct(array $baseConfig = [])
    {
        $this->baseConfig = $baseConfig;
    }

    public function render(string $html, array $options = []): string
    {
        if (! class_exists(Dompdf::class)) {
            throw new RuntimeException(
                'dompdf/dompdf is not installed. Run: composer require dompdf/dompdf'
            );
        }

        $config = array_replace_recursive($this->baseConfig, $options);
        $dompdf = new Dompdf($this->buildOptions($config));

        $paper = $config['paper'] ?? 'a4';
        $orientation = $config['orientation'] ?? 'portrait';

        $dompdf->setPaper($paper, $orientation);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        return $dompdf->output();
    }

    protected function buildOptions(array $config): Options
    {
        $options = new Options();

        $dompdfOptions = $config['dompdf_options'] ?? [];
        foreach ($dompdfOptions as $method => $value) {
            $setter = 'set' . ucfirst($method);
            if (method_exists($options, $setter)) {
                $options->{$setter}($value);
            }
        }

        if (! empty($config['temp_dir'])) {
            $options->setTempDir($config['temp_dir']);
        }

        if (! empty($config['fonts_path'])) {
            $options->setFontDir($config['fonts_path']);
            $options->setFontCache($config['fonts_path']);
        }

        if (! isset($dompdfOptions['isRemoteEnabled'])) {
            $options->setIsRemoteEnabled((bool) ($config['enable_remote_assets'] ?? false));
        }

        if (! empty($config['chroot'])) {
            $options->setChroot($config['chroot']);
        }

        return $options;
    }
}
