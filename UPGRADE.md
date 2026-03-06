# Upgrade Guide

## Upgrading to 2.x (released on March 6, 2026)

Version `2.0.0` removes mPDF and introduces a driver-based architecture.

## Breaking changes

- `mpdf/mpdf` dependency removed.
- `getMpdf()` no longer available.
- mPDF-specific config keys are not used.

## What to change

1. Update your dependencies:

```bash
composer update baidouabdellah/laravel-arpdf
```

2. Re-publish config (or manually merge new keys):

```bash
php artisan vendor:publish --provider="Baidouabdellah\\LaravelArpdf\\ArPDFServiceProvider" --tag=arpdf-config
```

3. Migrate config to Dompdf-oriented keys in `config/arpdf.php`:
- `paper`
- `orientation`
- `dompdf_options`
- `enable_remote_assets`
- `chroot`

4. If you depended on mPDF internals:
- Replace `getMpdf()` usage with either:
  - package fluent API (`loadHTML`, `loadView`, `stream`, `download`, `save`, `output`), or
  - custom engine implementation via `Contracts\\PdfEngine`.

## API compatibility retained

- `loadHTML`, `loadView`, `loadCSS`, `direction`, `stream`, `download`, `save`, `render` remain available.
- `output()` still accepts legacy destinations: `I`, `D`, `F`, `S`.

## Optional: register your own engine

Bind your engine in your app service provider:

```php
use Baidouabdellah\LaravelArpdf\Contracts\PdfEngine;

$this->app->bind(PdfEngine::class, MyPdfEngine::class);
```
