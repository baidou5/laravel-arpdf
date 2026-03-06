<?php

namespace Baidouabdellah\LaravelArpdf\Plugins;

use Baidouabdellah\LaravelArpdf\ArPDF;
use Baidouabdellah\LaravelArpdf\Contracts\PdfPlugin;
use InvalidArgumentException;

class PluginManager
{
    /** @var array<int, PdfPlugin> */
    protected array $plugins = [];

    public function register(PdfPlugin $plugin): self
    {
        $this->plugins[] = $plugin;

        return $this;
    }

    public function beforeRender(ArPDF $pdf, string $html, array $options): array
    {
        $currentHtml = $html;
        $currentOptions = $options;

        foreach ($this->plugins as $plugin) {
            $result = $plugin->beforeRender($pdf, $currentHtml, $currentOptions);
            if (! is_array($result)) {
                throw new InvalidArgumentException('Plugin beforeRender must return array with html/options.');
            }

            if (array_key_exists('html', $result) && is_string($result['html'])) {
                $currentHtml = $result['html'];
            }

            if (array_key_exists('options', $result) && is_array($result['options'])) {
                $currentOptions = $result['options'];
            }
        }

        return ['html' => $currentHtml, 'options' => $currentOptions];
    }

    public function afterRender(ArPDF $pdf, string $binary, array $context = []): string
    {
        $result = $binary;

        foreach ($this->plugins as $plugin) {
            $result = $plugin->afterRender($pdf, $result, $context);
        }

        return $result;
    }
}
