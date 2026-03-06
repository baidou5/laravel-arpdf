<?php

namespace Baidouabdellah\LaravelArpdf\Plugins;

use Baidouabdellah\LaravelArpdf\Contracts\PdfPlugin;
use InvalidArgumentException;

class PluginRegistry
{
    /** @var array<string, callable|PdfPlugin> */
    protected array $factories = [];

    public function register(string $name, callable|PdfPlugin $factory): self
    {
        $this->factories[$name] = $factory;

        return $this;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->factories);
    }

    public function resolve(string $name, array $options = []): PdfPlugin
    {
        if (! array_key_exists($name, $this->factories)) {
            throw new InvalidArgumentException('Unknown plugin: ' . $name);
        }

        $factory = $this->factories[$name];
        if ($factory instanceof PdfPlugin) {
            return $factory;
        }

        $plugin = $factory($options);
        if (! $plugin instanceof PdfPlugin) {
            throw new InvalidArgumentException('Plugin factory must return PdfPlugin instance.');
        }

        return $plugin;
    }
}
