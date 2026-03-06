# Laravel ArPDF v2.0.0

Release date: March 6, 2026

## Summary

`v2.0.0` is a major architecture upgrade focused on scalability and maintainability in large Laravel projects.

This release removes the hard dependency on mPDF and introduces a driver-based design with Dompdf as the default engine.

## Highlights

- Removed `mpdf/mpdf` dependency.
- Introduced `Contracts\\PdfEngine` for pluggable rendering engines.
- Added `Engines\\DompdfEngine` as the default engine.
- Switched service container registration to per-resolution `bind` for safer worker environments.
- Added destination constants (`ArPdfDestination`) and parser mode constants (`ArPdfParserMode`).
- Kept fluent API compatibility for core methods.
- Added package tests (unit + Laravel integration via Testbench).

## Breaking Changes

- `getMpdf()` is no longer available and now throws a runtime exception.
- mPDF-specific options are no longer used.

## Migration

Please read `UPGRADE.md` before upgrading.

Quick migration checklist:

1. Update package:
   - `composer update baidouabdellah/laravel-arpdf`
2. Re-publish config:
   - `php artisan vendor:publish --provider="Baidouabdellah\\LaravelArpdf\\ArPDFServiceProvider" --tag=arpdf-config`
3. Move old options to new config keys:
   - `paper`, `orientation`, `dompdf_options`, `enable_remote_assets`, `chroot`

## Compatibility

- Supports Laravel `8` to `12`.
- `output()` still supports legacy destinations: `I`, `D`, `F`, `S`.
- Named destinations are also supported: `inline`, `download`, `file`, `string`.

## Full Change Set

See `CHANGELOG.md`.
