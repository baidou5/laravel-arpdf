# Changelog

All notable changes to `baidouabdellah/laravel-arpdf` are documented in this file.

## [3.0.0] - 2026-03-06

### Added
- Complete rewrite around `mPDF` with Arabic-first rendering defaults.
- Advanced API: headers/footers, metadata, page setup, watermarks, profiles, templates, and report builder.
- Template engine with layouts, sections, and components.
- Queue pipelines:
  - File-based queue pipeline.
  - Laravel queue pipeline (`dispatch` / `dispatchSync`).
- Plugin system:
  - Core plugin contracts and manager.
  - Plugin registry with named marketplace entries.
  - Built-in plugins: `watermark_text`, `signature_block`, `quick_qr`, `certificate_signature`.
- Offline QR generation using `chillerlan/php-qrcode`.
- Snapshot testing manager and `assertSnapshot()` support.
- Certificate signature sidecar verification API: `ArPDF::verifySignature(...)`.
- Artisan command: `arpdf:verify-signature`.

### Changed
- `ArPDF` is now a production-focused orchestration layer with plugin hooks (`beforeRender` / `afterRender`).
- Package documentation updated for new v3 architecture and workflows.

### Removed
- Legacy Dompdf-oriented architecture and related internals.

## [2.0.0] - 2026-03-06

### Added
- Driver contract: `Contracts\\PdfEngine`.
- Default Dompdf engine: `Engines\\DompdfEngine`.
- Destination constants: `ArPdfDestination`.
- Parser mode constants: `ArPdfParserMode`.
- Testbench-based integration tests.
- Upgrade guide for migration from mPDF-based versions.

### Changed
- Switched architecture from mPDF wrapper to driver-based rendering.
- Service container binding changed to per-resolution `bind` for safer behavior in long-lived workers.
- `ArPDF` now aggregates HTML/CSS and renders through the selected engine.
- `output()` now supports both legacy (`I`,`D`,`F`,`S`) and named destinations (`inline`,`download`,`file`,`string`).
- Config updated for Dompdf options and rendering defaults.

### Removed
- Direct dependency on `mpdf/mpdf`.

### Compatibility Notes
- `getMpdf()` is no longer supported and throws a runtime exception.
- If your app used mPDF-specific options, migrate them to `dompdf_options` or a custom engine.
