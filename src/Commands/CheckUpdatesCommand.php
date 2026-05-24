<?php

/**
 * Artisan command that reports available updates for installed ArtisanPack UI packages.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Commands
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Commands;

use ArtisanPackUI\Core\Updates\UpdateChecker;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * `artisanpack:check-updates` command — runs the {@see UpdateChecker} and
 * renders its findings as a human-readable report or JSON payload.
 *
 * Supports `--minor-only`, `--security-only`, `--json`, and `--changelog`
 * to tailor the output to interactive use or CI consumption.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Commands
 *
 * @since      1.2.0
 */
#[AsCommand( name: 'artisanpack:check-updates' )]
class CheckUpdatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @since 1.2.0
     *
     * @var string
     */
    protected $signature = 'artisanpack:check-updates
                            {--json : Emit the report as JSON for programmatic use.}
                            {--minor-only : Only show minor and patch updates (skip majors).}
                            {--security-only : Only show updates flagged as security-related.}
                            {--changelog : Include changelog excerpts and source URLs in the output.}';

    /**
     * The console command description.
     *
     * @since 1.2.0
     *
     * @var string
     */
    protected $description = 'Check for available updates to installed ArtisanPack UI packages.';

    /**
     * Execute the command.
     *
     * @since 1.2.0
     *
     * @param  UpdateChecker  $checker  The update checker resolved from the container.
     *
     * @return int Command exit code.
     */
    public function handle( UpdateChecker $checker ): int
    {
        $report = $checker->check( [
            'minor_only'    => (bool) $this->option( 'minor-only' ),
            'security_only' => (bool) $this->option( 'security-only' ),
        ] );

        if ( $this->option( 'json' ) ) {
            return $this->emitJson( $report );
        }

        $this->renderReport( $report );

        return self::SUCCESS;
    }

    /**
     * Emit the report as JSON.
     *
     * @since 1.2.0
     *
     * @param  array<string, mixed>  $report  The update report.
     *
     * @return int Command exit code.
     */
    protected function emitJson( array $report ): int
    {
        $json = json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

        if ( false === $json ) {
            $this->error( 'Failed to encode report as JSON: ' . json_last_error_msg() );

            return self::FAILURE;
        }

        $this->line( $json );

        return self::SUCCESS;
    }

    /**
     * Render the report in human-readable form on the console.
     *
     * @since 1.2.0
     *
     * @param  array<string, mixed>  $report  The update report.
     *
     * @return void
     */
    protected function renderReport( array $report ): void
    {
        $this->line( 'ArtisanPack UI Update Check' );
        $this->line( '===========================' );
        $this->newLine();
        $this->line( 'Checking for updates...' );
        $this->newLine();

        $updates = $report['updates'] ?? [];

        if ( empty( $updates ) ) {
            $this->line( 'Package Updates Available:' );
            $this->line( '  (none)' );
            $this->newLine();
        } else {
            $this->renderUpdates( $updates );
        }

        $this->renderUpToDate( $report['up_to_date'] ?? [] );
        $this->renderErrors( $report['errors'] ?? [] );
        $this->renderSummary( $report['summary'] ?? [] );
        $this->renderUpgradeHint( $updates );
    }

    /**
     * Render the per-package update rows.
     *
     * @since 1.2.0
     *
     * @param  array<int, array<string, mixed>>  $updates  Update rows from the report.
     *
     * @return void
     */
    protected function renderUpdates( array $updates ): void
    {
        $this->line( 'Package Updates Available:' );

        foreach ( $updates as $update ) {
            $type    = (string) ( $update['type'] ?? 'unknown' );
            $package = (string) ( $update['package'] ?? '' );
            $current = (string) ( $update['current_version'] ?? '' );
            $latest  = (string) ( $update['latest_version'] ?? '' );

            $this->line( "  {$package}: {$current} → {$latest} ({$type})" );

            if ( true === ( $update['security'] ?? false ) ) {
                $this->line( '    🔒 Security-related release' );
            }

            if ( true === ( $update['breaking'] ?? false ) ) {
                $this->line( '    ⚠ Breaking changes - review changelog before updating' );
            }

            if ( $this->option( 'changelog' ) ) {
                $this->renderReleaseNotes( $update );
            }
        }

        $this->newLine();
    }

    /**
     * Render the release notes attached to a single update row when `--changelog` is set.
     *
     * @since 1.2.0
     *
     * @param  array<string, mixed>  $update  A single update row.
     *
     * @return void
     */
    protected function renderReleaseNotes( array $update ): void
    {
        $notes = $update['notes'] ?? [];

        if ( is_array( $notes ) ) {
            foreach ( $notes as $note ) {
                if ( is_string( $note ) && '' !== trim( $note ) ) {
                    $this->line( '    - ' . trim( $note ) );
                }
            }
        }

        $source = $update['source_url'] ?? null;

        if ( is_string( $source ) && '' !== $source ) {
            $this->line( '    Source: ' . $source );
        }
    }

    /**
     * Render the up-to-date packages section.
     *
     * @since 1.2.0
     *
     * @param  array<string, string>  $packages  Package name → installed version.
     *
     * @return void
     */
    protected function renderUpToDate( array $packages ): void
    {
        if ( empty( $packages ) ) {
            return;
        }

        $this->line( 'Up to Date:' );

        foreach ( $packages as $name => $version ) {
            $this->line( "  ✓ {$name}: {$version}" );
        }

        $this->newLine();
    }

    /**
     * Render any per-package errors collected during the lookup.
     *
     * @since 1.2.0
     *
     * @param  array<int, array<string, mixed>>  $errors  Error rows from the report.
     *
     * @return void
     */
    protected function renderErrors( array $errors ): void
    {
        if ( empty( $errors ) ) {
            return;
        }

        $this->line( 'Errors:' );

        foreach ( $errors as $error ) {
            $package = (string) ( $error['package'] ?? '' );
            $message = (string) ( $error['message'] ?? '' );
            $this->line( "  ✗ {$package}: {$message}" );
        }

        $this->newLine();
    }

    /**
     * Render the summary line ( updates available / major / security counts ).
     *
     * @since 1.2.0
     *
     * @param  array<string, int>  $summary  The summary section of the report.
     *
     * @return void
     */
    protected function renderSummary( array $summary ): void
    {
        $updates  = (int) ( $summary['updates_available'] ?? 0 );
        $major    = (int) ( $summary['major_updates'] ?? 0 );
        $security = (int) ( $summary['security_updates'] ?? 0 );
        $errors   = (int) ( $summary['errors'] ?? 0 );

        $this->line( 'Summary:' );

        if ( 0 === $updates && 0 === $errors ) {
            $this->line( '  All packages are up to date' );
        } elseif ( 0 === $updates ) {
            $this->line( '  No updates found for checked packages' );
            $this->line( "  {$errors} " . ( 1 === $errors ? 'package could not be checked' : 'packages could not be checked' ) );
        } else {
            $this->line( "  {$updates} " . ( 1 === $updates ? 'package has' : 'packages have' ) . ' updates available' );
        }

        if ( $major > 0 ) {
            $this->line( "  {$major} " . ( 1 === $major ? 'package has' : 'packages have' ) . ' a major update (review changelog)' );
        }

        if ( $security > 0 ) {
            $this->line( "  {$security} security-related " . ( 1 === $security ? 'update' : 'updates' ) . ' available' );
        }

        $this->newLine();
    }

    /**
     * Print the suggested `composer update` command for the available updates.
     *
     * @since 1.2.0
     *
     * @param  array<int, array<string, mixed>>  $updates  Update rows from the report.
     *
     * @return void
     */
    protected function renderUpgradeHint( array $updates ): void
    {
        if ( empty( $updates ) ) {
            return;
        }

        $names = array_map( fn ( array $u ): string => (string) ( $u['package'] ?? '' ), $updates );
        $names = array_filter( $names, fn ( string $n ): bool => '' !== $n );

        if ( empty( $names ) ) {
            return;
        }

        $this->line( 'Commands:' );
        $this->line( '  composer update ' . implode( ' ', $names ) );
    }
}
