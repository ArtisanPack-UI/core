<?php

/**
 * Artisan command that reports compatibility across installed ArtisanPack UI packages.
 *
 * @since 1.2.0
 */

namespace ArtisanPackUI\Core\Commands;

use ArtisanPackUI\Core\Compatibility\CompatibilityChecker;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand( name: 'artisanpack:check-compatibility' )]
class CheckCompatibilityCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'artisanpack:check-compatibility
                            {--json : Emit the report as JSON for programmatic use.}
                            {--strict : Treat warnings as errors (exit code 1).}
                            {--package= : Restrict the report to a single ArtisanPack UI package (e.g. artisanpack-ui/core).}';

    /**
     * @var string
     */
    protected $description = 'Analyze installed ArtisanPack UI packages for version, PHP, and Laravel compatibility.';

    public function handle( CompatibilityChecker $checker ): int
    {
        $packageOption = $this->option( 'package' );
        $filter        = is_string( $packageOption ) && '' !== $packageOption ? $packageOption : null;

        if ( null !== $filter && ! $checker->packageIsInstalled( $filter ) ) {
            $this->error( "Package '{$filter}' is not an installed ArtisanPack UI package." );

            return self::FAILURE;
        }

        $report = $checker->analyze( $filter );

        if ( $this->option( 'json' ) ) {
            return $this->emitJson( $report );
        }

        $this->renderReport( $report );

        return $this->resolveExitCode( $report );
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

        return $this->resolveExitCode( $report );
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function resolveExitCode( array $report ): int
    {
        $errors   = (int) ( $report['summary']['errors'] ?? 0 );
        $warnings = (int) ( $report['summary']['warnings'] ?? 0 );

        if ( $errors > 0 ) {
            return self::FAILURE;
        }

        if ( $this->option( 'strict' ) && $warnings > 0 ) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function renderReport( array $report ): void
    {
        $this->line( 'ArtisanPack UI Compatibility Check' );
        $this->line( '==================================' );
        $this->newLine();

        $this->renderInstalledPackages( $report );
        $this->renderSection( 'Dependency Analysis', $report['dependency_results'] ?? [], 'No inter-package dependencies to report.' );
        $this->renderSection( 'PHP Compatibility', $report['php_results'] ?? [], 'No PHP requirements to report.' );
        $this->renderSection( 'Laravel Compatibility', $report['laravel_results'] ?? [], 'No Laravel requirements to report.' );
        $this->renderSection( 'PHP Extensions', $report['extension_results'] ?? [], 'No PHP extension requirements to report.' );
        $this->renderSummary( $report );
        $this->renderRecommendations( $report );
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function renderInstalledPackages( array $report ): void
    {
        $installed = $report['installed_packages'] ?? [];

        $this->line( 'Installed Packages:' );

        if ( empty( $installed ) ) {
            $this->line( '  (no ArtisanPack UI packages installed)' );
            $this->newLine();

            return;
        }

        foreach ( $installed as $name => $version ) {
            $this->line( "  ✓ {$name}: {$version}" );
        }

        $this->newLine();
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     */
    protected function renderSection( string $title, array $results, string $emptyMessage ): void
    {
        $this->line( "{$title}:" );

        if ( empty( $results ) ) {
            $this->line( "  {$emptyMessage}" );
            $this->newLine();

            return;
        }

        foreach ( $results as $result ) {
            $this->line( '  ' . $this->symbolFor( $result['severity'] ?? 'pass' ) . ' ' . ( $result['message'] ?? '' ) );
        }

        $this->newLine();
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function renderSummary( array $report ): void
    {
        $errors   = (int) ( $report['summary']['errors'] ?? 0 );
        $warnings = (int) ( $report['summary']['warnings'] ?? 0 );

        if ( 0 === $errors && 0 === $warnings ) {
            $this->line( 'Issues Found: none' );
            $this->newLine();

            return;
        }

        $parts = [];

        if ( $errors > 0 ) {
            $parts[] = "{$errors} " . ( 1 === $errors ? 'error' : 'errors' );
        }

        if ( $warnings > 0 ) {
            $parts[] = "{$warnings} " . ( 1 === $warnings ? 'warning' : 'warnings' );
        }

        $this->line( 'Issues Found: ' . implode( ', ', $parts ) );
        $this->newLine();
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function renderRecommendations( array $report ): void
    {
        $recommendations = $report['recommendations'] ?? [];

        if ( empty( $recommendations ) ) {
            return;
        }

        $this->line( 'Recommendations:' );

        foreach ( $recommendations as $recommendation ) {
            $this->line( "  - {$recommendation}" );
        }

        $this->newLine();
    }

    protected function symbolFor( string $severity ): string
    {
        return match ( $severity ) {
            'error'   => '✗',
            'warning' => '⚠',
            default   => '✓',
        };
    }
}
