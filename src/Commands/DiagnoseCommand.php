<?php

/**
 * Artisan command that produces a health report for the ArtisanPack UI installation.
 *
 * @since 1.2.0
 */

namespace ArtisanPackUI\Core\Commands;

use ArtisanPackUI\Core\Diagnostics\DiagnosticReport;
use ArtisanPackUI\Core\Diagnostics\DiagnosticResult;
use ArtisanPackUI\Core\Diagnostics\DiagnosticRunner;
use ArtisanPackUI\Core\Diagnostics\DiagnosticStatus;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'artisanpack:diagnose')]
class DiagnoseCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'artisanpack:diagnose
                            {--json : Emit the report as JSON for programmatic use.}
                            {--fix : Attempt to fix common issues automatically.}
                            {--package= : Restrict the report to a single check id (e.g. environment, packages).}';

    /**
     * @var string
     */
    protected $description = 'Run diagnostics on the ArtisanPack UI installation and report its health.';

    public function handle(DiagnosticRunner $runner): int
    {
        $only = $this->option('package');

        if ($this->option('fix')) {
            $this->runFixes($runner, is_string($only) ? $only : null);
        }

        $report = $runner->run(is_string($only) ? $only : null);

        if ($this->option('json')) {
            $json = json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            if ($json === false) {
                $this->error('Failed to encode report as JSON: '.json_last_error_msg());

                return self::FAILURE;
            }

            $this->line($json);

            return $report->hasErrors() ? self::FAILURE : self::SUCCESS;
        }

        $this->renderReport($report);

        return $report->hasErrors() ? self::FAILURE : self::SUCCESS;
    }

    protected function runFixes(DiagnosticRunner $runner, ?string $only): void
    {
        $fixable = $runner->fixableChecks($only);

        if (empty($fixable)) {
            $this->line('No fixable checks available.');

            return;
        }

        $writer = fn (string $message): mixed => $this->line("  {$message}");

        foreach ($fixable as $check) {
            $this->info("Attempting to fix: {$check->name()}");

            if (! $check->fix($writer)) {
                $this->line('  Nothing to fix.');
            }
        }

        $this->newLine();
    }

    protected function renderReport(DiagnosticReport $report): void
    {
        $this->line('ArtisanPack UI Diagnostic Report');
        $this->line('================================');
        $this->newLine();

        foreach ($report->sections as $section) {
            $check = $section['check'];
            $this->line("{$check->name()}:");

            /** @var DiagnosticResult $result */
            foreach ($section['results'] as $result) {
                $this->renderResult($result);
            }

            $this->newLine();
        }

        $this->line('Summary:');
        $this->line('  '.DiagnosticStatus::Pass->symbol()." {$report->passedCount()} checks passed");
        $this->line('  '.DiagnosticStatus::Warning->symbol()." {$report->warningCount()} warnings");
        $this->line('  '.DiagnosticStatus::Error->symbol()." {$report->errorCount()} errors");
    }

    protected function renderResult(DiagnosticResult $result): void
    {
        $symbol = $result->status->symbol();
        $message = $result->message === '' ? '' : " ({$result->message})";
        $this->line("  {$symbol} {$result->label}{$message}");

        if (! $this->getOutput()->isVerbose() || empty($result->details)) {
            return;
        }

        foreach ($result->details as $key => $value) {
            if (is_scalar($value)) {
                $printable = (string) $value;
            } else {
                $encoded = json_encode($value);
                $printable = $encoded === false ? '<non-serializable>' : $encoded;
            }

            $this->line("      - {$key}: {$printable}");
        }
    }
}
