![ArPDF Logo](https://raw.githubusercontent.com/baidou5/laravel-arpdf/main/arpdf.jpg)

# Laravel ArPDF

Generate professional Arabic PDFs in Laravel with reliable **RTL**, **UTF-8**, and Arabic font support.

`laravel-arpdf` helps teams ship invoices, reports, contracts, and official documents without text corruption, broken alignment, or rendering surprises.

## Why ArPDF?

Arabic PDF generation is usually where production issues appear first:

- Arabic letters disconnected or reversed
- Mixed Arabic/English content breaks layout
- Fonts differ between environments
- Output is inconsistent across controllers and queues

ArPDF solves this by giving you a clean Laravel API and stable defaults focused on Arabic-first documents.

## Key Features

- Full Arabic + RTL + UTF-8 support
- Fluent Laravel API
- Clean architecture with configurable rendering engine (default: mPDF)
- Works well in web requests, jobs, and worker environments
- Custom font mapping from configuration
- Multiple output targets: stream, download, save, raw string

## Installation

```bash
composer require baidouabdellah/laravel-arpdf
```

Publish configuration and fonts (optional):

```bash
php artisan vendor:publish --provider="Baidouabdellah\LaravelArpdf\ArPDFServiceProvider"
```

## Quick Example

```php
use ArPDF;

public function invoice()
{
    return ArPDF::direction('rtl')
        ->loadView('pdf.invoice', ['title' => 'فاتورة'])
        ->download('invoice.pdf');
}
```

## Output Destinations

`output($filename, $dest)` supports:

- Legacy: `I`, `D`, `F`, `S`
- Named: `inline`, `download`, `file`, `string`

## Configuration

Main options in `config/arpdf.php`:

- `direction`, `default_font`
- `temp_dir`, `fonts_path`, `fonts`
- `paper`, `orientation`
- `dompdf_options`
- `enable_remote_assets`, `chroot`

## Testing

```bash
vendor/bin/phpunit -c tests/phpunit.xml
```

## License

MIT
