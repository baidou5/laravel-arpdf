<?php

namespace Baidouabdellah\LaravelArpdf\Templates;

use InvalidArgumentException;
use RuntimeException;

class TemplateEngine
{
    protected array $templates = [];

    protected array $layouts = [];

    protected array $components = [];

    public function __construct(array $templates = [], array $layouts = [], array $components = [])
    {
        $this->templates = $templates;
        $this->layouts = $layouts;
        $this->components = $components;
    }

    public function registerTemplate(string $name, callable|string $template): self
    {
        $this->templates[$name] = $template;

        return $this;
    }

    public function registerLayout(string $name, callable|string $layout): self
    {
        $this->layouts[$name] = $layout;

        return $this;
    }

    public function registerComponent(string $name, callable|string $component): self
    {
        $this->components[$name] = $component;

        return $this;
    }

    public function render(string $name, array $data = []): string
    {
        if (! array_key_exists($name, $this->templates)) {
            throw new InvalidArgumentException('Unknown template: ' . $name);
        }

        return $this->renderTemplateValue($this->templates[$name], $data);
    }

    public function hasTemplate(string $name): bool
    {
        return array_key_exists($name, $this->templates);
    }

    protected function renderTemplateValue(callable|string $template, array $data): string
    {
        if (is_callable($template)) {
            $result = $template($data, $this);
            if (! is_string($result)) {
                throw new RuntimeException('Template callable must return HTML string.');
            }

            return $this->applyLayoutAndComponents($result, $data);
        }

        $value = $template;
        if (is_file($value)) {
            $value = (string) file_get_contents($value);
        }

        $interpolated = $this->interpolateTemplate($value, $data);

        return $this->applyLayoutAndComponents($interpolated, $data);
    }

    protected function applyLayoutAndComponents(string $html, array $data): string
    {
        [$layoutName, $sections, $content] = $this->extractLayoutData($html);

        $content = $this->renderComponents($content, $data);
        foreach ($sections as $sectionName => $sectionHtml) {
            $sections[$sectionName] = $this->renderComponents($sectionHtml, $data);
        }

        if ($layoutName === null) {
            return $content;
        }

        if (! array_key_exists($layoutName, $this->layouts)) {
            throw new InvalidArgumentException('Unknown layout: ' . $layoutName);
        }

        $layout = $this->renderTemplateValue($this->layouts[$layoutName], $data);
        $layout = str_replace('{{ content }}', $content, $layout);

        foreach ($sections as $name => $sectionHtml) {
            $layout = str_replace('{{ section:' . $name . ' }}', $sectionHtml, $layout);
        }

        return (string) preg_replace('/{{\s*section:[a-zA-Z0-9_.-]+\s*}}/', '', $layout);
    }

    protected function renderComponents(string $html, array $data): string
    {
        return (string) preg_replace_callback('/{{\s*component:([a-zA-Z0-9_.-]+)\s*}}/', function (array $matches) use ($data) {
            $name = $matches[1];
            if (! array_key_exists($name, $this->components)) {
                return '';
            }

            $componentData = (array) ($data['components'][$name] ?? $data);

            return $this->renderTemplateValue($this->components[$name], $componentData);
        }, $html);
    }

    protected function extractLayoutData(string $html): array
    {
        $layoutName = null;

        if (preg_match('/^\s*@layout\(([^)]+)\)\s*/', $html, $layoutMatch) === 1) {
            $layoutName = trim($layoutMatch[1], " \t\n\r\0\x0B'\"");
            $html = (string) preg_replace('/^\s*@layout\(([^)]+)\)\s*/', '', $html, 1);
        }

        $sections = [];
        if (preg_match_all('/@section\(([^)]+)\)(.*?)@endsection/s', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $name = trim($match[1], " \t\n\r\0\x0B'\"");
                $sections[$name] = trim($match[2]);
            }

            $html = (string) preg_replace('/@section\(([^)]+)\)(.*?)@endsection/s', '', $html);
        }

        return [$layoutName, $sections, trim($html)];
    }

    protected function interpolateTemplate(string $template, array $data): string
    {
        return (string) preg_replace_callback('/{{\s*([a-zA-Z0-9_.-]+)\s*}}/', function (array $matches) use ($data) {
            if ($matches[1] === 'content') {
                return $matches[0];
            }

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
}
