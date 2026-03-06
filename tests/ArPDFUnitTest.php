<?php

namespace Baidouabdellah\LaravelArpdf\Tests;

use Baidouabdellah\LaravelArpdf\ArPDF;
use Baidouabdellah\LaravelArpdf\ArPdfDestination;
use Baidouabdellah\LaravelArpdf\ArPdfParserMode;
use Baidouabdellah\LaravelArpdf\Contracts\PdfEngine;
use Baidouabdellah\LaravelArpdf\Contracts\PdfPlugin;
use Baidouabdellah\LaravelArpdf\Pipelines\LaravelQueuePipeline;
use Baidouabdellah\LaravelArpdf\Reports\ReportBuilder;
use PHPUnit\Framework\TestCase;

class ArPDFUnitTest extends TestCase
{
    public function testCanCreatePdfInstance(): void
    {
        $pdf = new ArPDF(new FakeEngine(), []);

        $this->assertInstanceOf(ArPDF::class, $pdf);
    }

    public function testHeaderCssModeRoutesToCssCollector(): void
    {
        $engine = new FakeEngine();

        $pdf = new ArPDF($engine, [
            'fonts_path' => __DIR__,
            'fonts' => [],
        ]);

        $output = $pdf
            ->loadHTML('body{color:red;}', ArPdfParserMode::HEADER_CSS)
            ->loadHTML('<p>Hello</p>')
            ->output('doc.pdf', ArPdfDestination::STRING_RETURN);

        $this->assertSame('%PDF-FAKE%', $output);
        $this->assertStringContainsString('body{color:red;}', (string) ($engine->lastOptions['css'] ?? ''));
        $this->assertStringContainsString('<p>Hello</p>', $engine->lastHtml);
    }

    public function testLegacyDestinationSReturnsBinaryString(): void
    {
        $pdf = new ArPDF(new FakeEngine(), []);

        $output = $pdf
            ->loadHTML('<h1>مرحبا</h1>')
            ->output('doc.pdf', 'S');

        $this->assertSame('%PDF-FAKE%', $output);
    }

    public function testAdvancedOptionsAreForwardedToEngine(): void
    {
        $engine = new FakeEngine();

        $pdf = new ArPDF($engine, []);

        $pdf->direction('rtl')
            ->paper('A5', 'landscape')
            ->margins(12, 13, 14, 15, 4, 3)
            ->title('عنوان')
            ->author('كاتب')
            ->header('<div>HEADER</div>')
            ->footer('<div>FOOTER</div>')
            ->watermarkText('سري')
            ->loadHTML('<h1>مرحبا</h1>')
            ->output('doc.pdf', 'S');

        $this->assertSame('rtl', $engine->lastOptions['direction'] ?? null);
        $this->assertSame('A5', $engine->lastOptions['paper'] ?? null);
        $this->assertSame('landscape', $engine->lastOptions['orientation'] ?? null);
        $this->assertSame(12, $engine->lastOptions['margins']['left'] ?? null);
        $this->assertSame('عنوان', $engine->lastOptions['metadata']['title'] ?? null);
        $this->assertSame('كاتب', $engine->lastOptions['metadata']['author'] ?? null);
        $this->assertSame('<div>HEADER</div>', $engine->lastOptions['header_html'] ?? null);
        $this->assertSame('<div>FOOTER</div>', $engine->lastOptions['footer_html'] ?? null);
        $this->assertSame('سري', $engine->lastOptions['watermark_text'] ?? null);
    }

    public function testProfileCanBeApplied(): void
    {
        $engine = new FakeEngine();
        $pdf = new ArPDF($engine, [
            'profiles' => [
                'invoice_ar' => [
                    'direction' => 'rtl',
                    'paper' => 'A5',
                    'orientation' => 'landscape',
                ],
            ],
        ]);

        $pdf->profile('invoice_ar')
            ->loadHTML('<p>ok</p>')
            ->output('doc.pdf', 'S');

        $this->assertSame('rtl', $engine->lastOptions['direction'] ?? null);
        $this->assertSame('A5', $engine->lastOptions['paper'] ?? null);
        $this->assertSame('landscape', $engine->lastOptions['orientation'] ?? null);
    }

    public function testNamedTemplateInterpolationWorks(): void
    {
        $engine = new FakeEngine();
        $pdf = new ArPDF($engine, []);

        $pdf->registerTemplate('invoice', '<h1>{{ title }}</h1><p>{{ customer.name }}</p>')
            ->loadTemplate('invoice', [
                'title' => 'فاتورة',
                'customer' => ['name' => 'أحمد'],
            ])
            ->output('doc.pdf', 'S');

        $this->assertStringContainsString('<h1>فاتورة</h1>', $engine->lastHtml);
        $this->assertStringContainsString('<p>أحمد</p>', $engine->lastHtml);
    }

    public function testCacheAvoidsSecondEngineRender(): void
    {
        $engine = new FakeEngine();
        $cacheDir = sys_get_temp_dir() . '/laravel-arpdf-test-cache-' . uniqid('', true);
        mkdir($cacheDir, 0775, true);

        $pdf = new ArPDF($engine, [
            'cache' => [
                'enabled' => true,
                'ttl_seconds' => 3600,
                'path' => $cacheDir,
            ],
        ]);

        $pdf->loadHTML('<h1>مرحبا</h1>')->output('doc.pdf', 'S');
        $pdf->reset()->loadHTML('<h1>مرحبا</h1>')->output('doc.pdf', 'S');

        $this->assertSame(1, $engine->renderCalls);
    }

    public function testTemplateLayoutAndComponentRendering(): void
    {
        $engine = new FakeEngine();
        $pdf = new ArPDF($engine, []);

        $pdf->registerLayout('base', '<html><body>{{ section:header }}{{ content }}{{ component:footer }}</body></html>')
            ->registerComponent('footer', '<footer>{{ company }}</footer>')
            ->registerTemplate('invoice', "@layout('base')\n@section('header')<h1>{{ title }}</h1>@endsection\n<p>{{ customer.name }}</p>")
            ->loadTemplate('invoice', [
                'title' => 'فاتورة',
                'customer' => ['name' => 'أحمد'],
                'components' => ['footer' => ['company' => 'Acme']],
            ])
            ->output('doc.pdf', 'S');

        $this->assertStringContainsString('<h1>فاتورة</h1>', $engine->lastHtml);
        $this->assertStringContainsString('<p>أحمد</p>', $engine->lastHtml);
        $this->assertStringContainsString('<footer>Acme</footer>', $engine->lastHtml);
    }

    public function testReportBuilderCanBeLoadedIntoPdf(): void
    {
        $engine = new FakeEngine();
        $pdf = new ArPDF($engine, []);

        $report = ReportBuilder::make('rtl')
            ->heading('تقرير مبيعات')
            ->paragraph('ملخص شهري')
            ->table(['المنتج', 'الكمية'], [['A', 10], ['B', 20]]);

        $pdf->report($report)->output('doc.pdf', 'S');

        $this->assertStringContainsString('تقرير مبيعات', $engine->lastHtml);
        $this->assertStringContainsString('الكمية', $engine->lastHtml);
    }

    public function testStateExportImportAndFileQueuePipeline(): void
    {
        $engine = new FakeEngine();
        $queueDir = sys_get_temp_dir() . '/laravel-arpdf-test-queue-' . uniqid('', true);
        $outputPath = sys_get_temp_dir() . '/laravel-arpdf-out-' . uniqid('', true) . '.pdf';

        $pdf = new ArPDF($engine, [
            'queue' => ['path' => $queueDir],
        ]);
        $pdf->loadHTML('<h1>queued</h1>');

        $state = $pdf->exportState();
        $restored = ArPDF::fromState($state)->useEngine($engine);
        $restored->output('doc.pdf', 'S');
        $this->assertStringContainsString('queued', $engine->lastHtml);

        $pipeline = $pdf->queuePipeline($queueDir);
        $pipeline->enqueue($pdf, $outputPath);
        $processed = $pipeline->processNext();

        $this->assertIsArray($processed);
        $this->assertFileExists($outputPath);
    }

    public function testPluginHooksCanModifyHtmlAndOutput(): void
    {
        $engine = new FakeEngine();
        $pdf = new ArPDF($engine, []);

        $output = $pdf->usePlugin(new TestPlugin())
            ->loadHTML('<p>body</p>')
            ->output('doc.pdf', 'S');

        $this->assertStringContainsString('plugin-before', $engine->lastHtml);
        $this->assertStringContainsString('%PLUGIN-AFTER%', $output);
    }

    public function testSnapshotManagerFlow(): void
    {
        $engine = new FakeEngine();
        $snapshotDir = sys_get_temp_dir() . '/laravel-arpdf-snapshots-' . uniqid('', true);
        $pdf = new ArPDF($engine, [
            'snapshots' => [
                'path' => $snapshotDir,
            ],
        ]);

        $pdf->loadHTML('<h1>snap</h1>');
        $first = $pdf->assertSnapshot('invoice');
        $second = $pdf->assertSnapshot('invoice');

        $this->assertTrue($first['matched']);
        $this->assertTrue($second['matched']);
        $this->assertFileExists($snapshotDir . '/invoice.sha256');
    }

    public function testLaravelQueuePipelineFallbackDispatch(): void
    {
        $engine = new FakeEngine();
        $pdf = new ArPDF($engine, []);
        $pdf->loadHTML('<h1>queue</h1>');
        $output = sys_get_temp_dir() . '/laravel-arpdf-lq-' . uniqid('', true) . '.pdf';

        $pipeline = new LaravelQueuePipeline();
        $pipeline->dispatchSync($pdf, $output);

        $this->assertFileExists($output);
    }
}

class FakeEngine implements PdfEngine
{
    public string $lastHtml = '';
    public array $lastOptions = [];
    public int $renderCalls = 0;
    public string $lastBinary = '';

    public function render(string $html, array $options = []): string
    {
        $this->renderCalls++;
        $this->lastHtml = $html;
        $this->lastOptions = $options;
        $this->lastBinary = '%PDF-FAKE%';

        return $this->lastBinary;
    }
}

class TestPlugin implements PdfPlugin
{
    public function beforeRender(ArPDF $pdf, string $html, array $options): array
    {
        return [
            'html' => $html . '<div>plugin-before</div>',
            'options' => $options,
        ];
    }

    public function afterRender(ArPDF $pdf, string $binary, array $context): string
    {
        return $binary . '%PLUGIN-AFTER%';
    }
}
