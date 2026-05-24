<?php

/**
 * Artisan command that produces a health report for the ArtisanPack UI installation.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Commands
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Commands;

use ArtisanPackUI\Core\Diagnostics\DiagnosticReport;
use ArtisanPackUI\Core\Diagnostics\DiagnosticResult;
use ArtisanPackUI\Core\Diagnostics\DiagnosticRunner;
use ArtisanPackUI\Core\Diagnostics\DiagnosticStatus;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * `artisanpack:diagnose` command — executes the {@see DiagnosticRunner} and
 * prints the resulting {@see DiagnosticReport} in either human-readable or
 * JSON form.
 *
 * Supports `--package` to restrict the run to a single check, `--fix` to
 * trigger the auto-fix path for fixable checks, and `--json` to emit a
 * machine-parseable payload for CI consumption.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Commands
 *
 * @since      1.2.0
 */
#[AsCommand( name: 'artisanpack:diagnose' )]
class DiagnoseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @since 1.2.0
     *
     * @var string
     */
    protected $signature = 'artisanpack:diagnose
                            {--json : Emit the report as JSON for programmatic use.}
                            {--fix : Attempt to fix common issues automatically.}
                            {--package= : Restrict the report to a single check id (e.g. environment, packages).}';

    /**
     * The console command description.
     *
     * @since 1.2.0
     *
     * @var string
     */
    protected $description = 'Run diagnostics on the ArtisanPack UI installation and report its health.';

    /**
     * Execute the command.
     *
     * @since 1.2.0
     *
     * @param  DiagnosticRunner  $runner  The diagnostic runner resolved from the container.
     *
     * @return int Command exit code.
     */
    public function handle( DiagnosticRunner $runner ): int
    {
        $packageOption = $this->option( 'package' );
        $only          = is_string( $packageOption ) ? $packageOption : null;

        if ( null !== $only && ! $this->checkExists( $runner, $only ) ) {
            $message = "Unknown package check id: '{$only}'. Available ids: "
                . implode( ', ', $this->availableCheckIds( $runner ) ) . '.';

            if ( $this->option( 'json' ) ) {
                $this->line(
                    (string) json_encode( ['error' => $message], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
                );
            } else {
                $this->error( $message );
            }

            return self::FAILURE;
        }

        $fixSucceeded = true;
        if ( $this->option( 'fix' ) ) {
            $fixSucceeded = $this->runFixes( $runner, $only );
        }

        $report = $runner->run( $only );

        if ( $this->option( 'json' ) ) {
            $json = json_encode( $report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

            if ( false === $json ) {
                $this->error( 'Failed to encode report as JSON: ' . json_last_error_msg() );

                return self::FAILURE;
            }

            $this->line( $json );

            return ( $report->hasErrors() || ! $fixSucceeded ) ? self::FAILURE : self::SUCCESS;
        }

        $this->renderReport( $report );

        return ( $report->hasErrors() || ! $fixSucceeded ) ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Collect the identifiers of the runner's registered checks.
     *
     * @since 1.2.0
     *
     * @param  DiagnosticRunner  $runner  The diagnostic runner.
     *
     * @return array<int, string> The registered check identifiers.
     */
    protected function availableCheckIds( DiagnosticRunner $runner ): array
    {
        return array_map( fn ( $c ) => $c->id(), $runner->checks() );
    }

    /**
     * Determine whether the runner has a check with the given identifier.
     *
     * @since 1.2.0
     *
     * @param  DiagnosticRunner  $runner  The diagnostic runner.
     * @param  string            $id      The check identifier to look up.
     *
     * @return bool True when a matching check is registered.
     */
    protected function checkExists( DiagnosticRunner $runner, string $id ): bool
    {
        return in_array( $id, $this->availableCheckIds( $runner ), true );
    }

    /**
     * Drive the fix flow for every fixable check, optionally filtered.
     *
     * @since 1.2.0
     *
     * @param  DiagnosticRunner  $runner  The diagnostic runner.
     * @param  string|null       $only    Optional check identifier to focus on.
     *
     * @return bool True when every attempted fix succeeded.
     */
    protected function runFixes( DiagnosticRunner $runner, ?string $only ): bool
    {
        $fixable = $runner->fixableChecks( $only );

        if ( empty( $fixable ) ) {
            $this->line( 'No fixable checks available.' );

            return true;
        }

        $writer       = fn ( string $message ): mixed => $this->line( "  {$message}" );
        $allSucceeded = true;

        foreach ( $fixable as $check ) {
            $this->info( "Attempting to fix: {$check->name()}" );

            if ( ! $check->fix( $writer ) ) {
                $this->error( "  Fix failed for: {$check->name()}" );
                $allSucceeded = false;
            }
        }

        $this->newLine();

        return $allSucceeded;
    }

    /**
     * Render the diagnostic report on the console.
     *
     * @since 1.2.0
     *
     * @param  DiagnosticReport  $report  The aggregated report.
     *
     * @return void
     */
    protected function renderReport( DiagnosticReport $report ): void
    {
        $this->line( 'ArtisanPack UI Diagnostic Report' );
        $this->line( '================================' );
        $this->newLine();

        foreach ( $report->sections as $section ) {
            $check = $section['check'];
            $this->line( "{$check->name()}:" );

            /** @var DiagnosticResult $result */
            foreach ( $section['results'] as $result ) {
                $this->renderResult( $result );
            }

            $this->newLine();
        }

        $this->line( 'Summary:' );
        $this->line( '  ' . DiagnosticStatus::Pass->symbol() . " {$report->passedCount()} checks passed" );
        $this->line( '  ' . DiagnosticStatus::Warning->symbol() . " {$report->warningCount()} warnings" );
        $this->line( '  ' . DiagnosticStatus::Error->symbol() . " {$report->errorCount()} errors" );
    }

    /**
     * Render a single diagnostic result row, expanding details in verbose mode.
     *
     * @since 1.2.0
     *
     * @param  DiagnosticResult  $result  The result row to render.
     *
     * @return void
     */
    protected function renderResult( DiagnosticResult $result ): void
    {
        $symbol  = $result->status->symbol();
        $message = '' === $result->message ? '' : " ({$result->message})";
        $this->line( "  {$symbol} {$result->label}{$message}" );

        if ( ! $this->getOutput()->isVerbose() || empty( $result->details ) ) {
            return;
        }

        foreach ( $result->details as $key => $value ) {
            if ( is_scalar( $value ) ) {
                $printable = (string) $value;
            } else {
                $encoded   = json_encode( $value );
                $printable = false === $encoded ? '<non-serializable>' : $encoded;
            }

            $this->line( "      - {$key}: {$printable}" );
        }
    }
}
