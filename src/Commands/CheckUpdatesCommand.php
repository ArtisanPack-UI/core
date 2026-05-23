<?php

/**
 * Artisan command that reports available updates for installed ArtisanPack UI packages.
 *
 * @since 1.2.0
 */

namespace ArtisanPackUI\Core\Commands;

use ArtisanPackUI\Core\Updates\UpdateChecker;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand( name: 'artisanpack:check-updates' )]
class CheckUpdatesCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'artisanpack:check-updates
                            {--json : Emit the report as JSON for programmatic use.}
                            {--minor-only : Only show minor and patch updates (skip majors).}
                            {--security-only : Only show updates flagged as security-related.}
                            {--changelog : Include changelog excerpts and source URLs in the output.}';

    /**
     * @var string
     */
    protected $description = 'Check for available updates to installed ArtisanPack UI packages.';

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
     * @param  array<string, mixed>  $report
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
     * @param  array<string, mixed>  $report
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
     * @param  array<int, array<string, mixed>>  $updates
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
     * @param  array<string, mixed>  $update
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
     * @param  array<string, string>  $packages
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
     * @param  array<int, array<string, mixed>>  $errors
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
     * @param  array<string, int>  $summary
     */
    protected function renderSummary( array $summary ): void
    {
        $updates  = (int) ( $summary['updates_available'] ?? 0 );
        $major    = (int) ( $summary['major_updates'] ?? 0 );
        $security = (int) ( $summary['security_updates'] ?? 0 );

        $this->line( 'Summary:' );

        if ( 0 === $updates ) {
            $this->line( '  All packages are up to date' );
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
     * @param  array<int, array<string, mixed>>  $updates
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
