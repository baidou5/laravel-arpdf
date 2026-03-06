<?php

namespace Baidouabdellah\LaravelArpdf\Reports;

class ReportBuilder
{
    protected string $direction;

    protected array $blocks = [];

    protected string $fontFamily = "'cairo','DejaVu Sans',sans-serif";

    public function __construct(string $direction = 'rtl')
    {
        $this->direction = strtolower($direction) === 'ltr' ? 'ltr' : 'rtl';
    }

    public static function make(string $direction = 'rtl'): self
    {
        return new self($direction);
    }

    public function fontFamily(string $fontFamily): self
    {
        $this->fontFamily = $fontFamily;

        return $this;
    }

    public function heading(string $text, int $level = 1): self
    {
        $level = max(1, min(6, $level));
        $this->blocks[] = sprintf('<h%d>%s</h%d>', $level, $text, $level);

        return $this;
    }

    public function paragraph(string $text): self
    {
        $this->blocks[] = '<p>' . $text . '</p>';

        return $this;
    }

    public function section(string $title, callable $callback): self
    {
        $nested = new self($this->direction);
        $nested->fontFamily($this->fontFamily);
        $callback($nested);

        $this->blocks[] = '<section><h2>' . $title . '</h2>' . $nested->renderBody() . '</section>';

        return $this;
    }

    public function divider(): self
    {
        $this->blocks[] = '<hr />';

        return $this;
    }

    public function keyValueTable(array $rows): self
    {
        $body = '';
        foreach ($rows as $key => $value) {
            $body .= '<tr><th>' . (string) $key . '</th><td>' . (string) $value . '</td></tr>';
        }

        $this->blocks[] = '<table class="arpdf-kv"><tbody>' . $body . '</tbody></table>';

        return $this;
    }

    public function table(array $headers, array $rows): self
    {
        $head = '';
        foreach ($headers as $header) {
            $head .= '<th>' . (string) $header . '</th>';
        }

        $body = '';
        foreach ($rows as $row) {
            $cells = '';
            foreach ($row as $cell) {
                $cells .= '<td>' . (string) $cell . '</td>';
            }

            $body .= '<tr>' . $cells . '</tr>';
        }

        $this->blocks[] = '<table class="arpdf-table"><thead><tr>' . $head . '</tr></thead><tbody>' . $body . '</tbody></table>';

        return $this;
    }

    public function html(string $html): self
    {
        $this->blocks[] = $html;

        return $this;
    }

    public function render(): string
    {
        $align = $this->direction === 'rtl' ? 'right' : 'left';

        return '<!doctype html><html dir="' . $this->direction . '"><head><meta charset="UTF-8"><style>'
            . 'body{font-family:' . $this->fontFamily . ';direction:' . $this->direction . ';text-align:' . $align . ';}'
            . 'h1,h2,h3,h4,h5,h6{margin:0 0 10px;}'
            . 'p{margin:0 0 10px;line-height:1.6;}'
            . '.arpdf-table,.arpdf-kv{width:100%;border-collapse:collapse;margin:12px 0;}'
            . '.arpdf-table th,.arpdf-table td,.arpdf-kv th,.arpdf-kv td{border:1px solid #ddd;padding:8px;}'
            . '.arpdf-kv th{width:30%;background:#f7f7f7;}'
            . 'hr{border:none;border-top:1px solid #e3e3e3;margin:14px 0;}'
            . '</style></head><body>' . $this->renderBody() . '</body></html>';
    }

    public function renderBody(): string
    {
        return implode("\n", $this->blocks);
    }
}
