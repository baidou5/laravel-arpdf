<?php

namespace Baidouabdellah\LaravelArpdf\Pipelines;

use Baidouabdellah\LaravelArpdf\ArPDF;
use Baidouabdellah\LaravelArpdf\Jobs\RenderPdfFromStateJob;
use Illuminate\Support\Facades\Bus;

class LaravelQueuePipeline
{
    public function dispatch(ArPDF $pdf, string $outputPath, ?string $queue = null): mixed
    {
        $job = new RenderPdfFromStateJob($pdf->exportState(), $outputPath);

        if (class_exists(Bus::class) && function_exists('app') && app()->bound('queue')) {
            $pending = Bus::dispatch($job);

            if ($queue !== null && method_exists($pending, 'onQueue')) {
                $pending->onQueue($queue);
            }

            return $pending;
        }

        // Local fallback for environments without Laravel queue worker.
        $job->handle();

        return null;
    }

    public function dispatchSync(ArPDF $pdf, string $outputPath): void
    {
        $job = new RenderPdfFromStateJob($pdf->exportState(), $outputPath);

        if (class_exists(Bus::class) && function_exists('app') && app()->bound('queue')) {
            Bus::dispatchSync($job);

            return;
        }

        $job->handle();
    }
}
