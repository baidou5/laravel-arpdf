![ArPDF Logo](https://raw.githubusercontent.com/baidou5/laravel-arpdf/main/arpdf.jpg)

# Laravel ArPDF

Arabic-first PDF generation for Laravel, rebuilt from scratch on top of **mPDF**.

`laravel-arpdf` is designed for production-grade Arabic documents (invoices, contracts, reports) with stable RTL rendering, Arabic shaping, and robust font handling.

## Why This Is Stronger Than Dompdf

- Native mPDF engine focused on complex scripts (Arabic/RTL)
- Better Arabic glyph shaping and bidirectional text handling
- Reliable custom font loading and fallback strategy
- Rich PDF controls: metadata, margins, header/footer, watermark

## Core Features

- Full Arabic + RTL + UTF-8 support
- Fluent Laravel API
- Custom Arabic fonts via config map
- `stream`, `download`, `save`, and raw `string` output
- Header / footer HTML
- Text or image watermark
- Metadata API (`title`, `author`, `subject`, `keywords`, `creator`)
- Reusable document profiles (`profile('invoice_ar')`)
- Named templates with variable interpolation
- Layouts and reusable components for templates
- Report Builder DSL for business reports
- File queue pipeline for deferred rendering
- Laravel queue pipeline (`dispatch` / `dispatchSync`)
- Plugin API (before/after render hooks)
- Snapshot testing workflow for PDF regression checks
- PDF render cache for repeated documents

## Installation

```bash
composer require baidouabdellah/laravel-arpdf
```

Publish config and fonts (optional but recommended):

```bash
php artisan vendor:publish --provider="Baidouabdellah\LaravelArpdf\ArPDFServiceProvider"
```

## Quick Example

```php
use ArPDF;

public function invoice()
{
    return ArPDF::direction('rtl')
        ->title('فاتورة')
        ->author('My Company')
        ->header('<div style="text-align:right">رأس الصفحة</div>')
        ->footer('<div style="text-align:center">{PAGENO}</div>')
        ->watermarkText('سري')
        ->loadView('pdf.invoice', ['title' => 'فاتورة'])
        ->download('invoice.pdf');
}
```

## Production Features

```php
ArPDF::profile('invoice_ar')
    ->registerTemplate('invoice_basic', '<h1>{{ title }}</h1><p>{{ customer.name }}</p>')
    ->loadTemplate('invoice_basic', [
        'title' => 'فاتورة',
        'customer' => ['name' => 'أحمد'],
    ])
    ->useCache(true, 3600)
    ->download('invoice.pdf');
```

## Layouts, Components, Reports, Queue

```php
$pdf = app(\Baidouabdellah\LaravelArpdf\ArPDF::class);

$pdf->registerLayout('base', '<html><body>{{ section:header }}{{ content }}{{ component:footer }}</body></html>')
    ->registerComponent('footer', '<footer>{{ company }}</footer>')
    ->registerTemplate('invoice', \"@layout('base')\\n@section('header')<h1>{{ title }}</h1>@endsection\\n<p>{{ customer.name }}</p>\")
    ->loadTemplate('invoice', [
        'title' => 'فاتورة',
        'customer' => ['name' => 'أحمد'],
        'components' => ['footer' => ['company' => 'My Co']],
    ]);

$pdf->report(function ($r) {
    $r->heading('تقرير شهري')->table(['البند', 'القيمة'], [['المبيعات', 15000]]);
});

$pipeline = $pdf->queuePipeline(); // file-based queue
$jobId = $pipeline->enqueue($pdf, storage_path('app/reports/monthly.pdf'));
$pipeline->processNext();

$pdf->laravelQueuePipeline()->dispatchSync($pdf, storage_path('app/reports/sync.pdf'));
```

## Plugins & Snapshots

```php
use Baidouabdellah\LaravelArpdf\Contracts\PdfPlugin;

class FooterPlugin implements PdfPlugin {
    public function beforeRender($pdf, string $html, array $options): array {
        return ['html' => $html . '<footer>Signed</footer>', 'options' => $options];
    }
    public function afterRender($pdf, string $binary, array $context): string {
        return $binary;
    }
}

$pdf->usePlugin(new FooterPlugin())->loadHTML('<h1>Doc</h1>');

$result = $pdf->assertSnapshot('doc-v1'); // stores/compares sha256 snapshot
if (! $result['matched']) {
    // regression detected
}
```

## Output Destinations

`output($filename, $dest)` supports:

- Legacy: `I`, `D`, `F`, `S`
- Named: `inline`, `download`, `file`, `string`

## Configuration

Main options in `config/arpdf.php`:

- `direction`, `default_font`, `fonts_path`, `fonts`
- `paper`, `orientation`, `margins`
- `metadata`
- `mpdf` (native mPDF overrides)

## Testing

```bash
vendor/bin/phpunit -c tests/phpunit.xml
```

## License

MIT
