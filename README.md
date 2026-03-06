![ArPDF Logo](https://raw.githubusercontent.com/baidou5/laravel-arpdf/main/arpdf.jpg)

# Laravel ArPDF

A Laravel package for generating Arabic-friendly PDF files with UTF-8 + RTL support.

This version is **driver-based** and does **not depend on mPDF**. The default engine is **Dompdf**.

## Features

- Arabic + RTL + UTF-8 support
- Laravel-friendly fluent API
- Driver-based architecture for large projects
- Per-resolution binding (safer for Octane/workers)
- Custom fonts via config (`R`/`B` map)
- Stream, download, save, or return raw PDF bytes

## Installation

```bash
composer require baidouabdellah/laravel-arpdf
```

Publish config/fonts (optional):

```bash
php artisan vendor:publish --provider="Baidouabdellah\LaravelArpdf\ArPDFServiceProvider"
```

## Quick Usage

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

`output($filename, $dest)` supports both legacy and named destinations:

- Legacy: `I`, `D`, `F`, `S`
- Named: `inline`, `download`, `file`, `string`

## Configuration

`config/arpdf.php` includes:

- `direction`, `default_font`
- `temp_dir`, `fonts_path`, `fonts`
- `paper`, `orientation`
- `dompdf_options`
- `enable_remote_assets`, `chroot`

## Testing

```bash
vendor/bin/phpunit -c tests/phpunit.xml
```

## Upgrade Notes

For migration from old mPDF-based versions, see `UPGRADE.md`.

## Extending with Your Own Engine

Implement `Baidouabdellah\LaravelArpdf\Contracts\PdfEngine` and bind it in your app container.

## License

MIT
