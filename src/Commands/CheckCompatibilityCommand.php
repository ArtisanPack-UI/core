<?php

/**
 * Artisan command that reports compatibility across installed ArtisanPack UI packages.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Commands
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Commands;

use ArtisanPackUI\Core\Compatibility\CompatibilityChecker;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * `artisanpack:check-compatibility` command — runs the
 * {@see CompatibilityChecker} and renders its findings as a human-readable
 * table or JSON payload for scripting.
 *
 * Supports `--package` to focus on a single ArtisanPack UI package and
 * `--strict` to fail when warnings are present.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Commands
 *
 * @since      1.2.0
 */
#[AsCommand( name: 'artisanpack:check-compatibility' )]
class CheckCompatibilityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @since 1.2.0
     *
     * @var string
     */
    protected $signature = 'artisanpack:check-compatibility
                            {--json : Emit the report as JSON for programmatic use.}
                            {--strict : Treat warnings as errors (exit code 1).}
                            {--package= : Restrict the report to a single ArtisanPack UI package (e.g. artisanpack-ui/core).}';

    /**
     * The console command description.
     *
     * @since 1.2.0
     *
     * @var string
     */
    protected $description = 'Analyze installed ArtisanPack UI packages for version, PHP, and Laravel compatibility.';

    /**
     * Execute the command.
     *
     * @since 1.2.0
     *
     * @param  CompatibilityChecker  $checker  The compatibility checker resolved from the container.
     *
     * @return int Command exit code.
     */
    public function handle( CompatibilityChecker $checker ): int
    {
        $packageOption = $this->option( 'package' );
        $filter        = is_string( $packageOption ) && '' !== $packageOption ? $packageOption : null;

        if ( null !== $filter && ! $checker->packageIsInstalled( $filter ) ) {
            $message = "Package '{$filter}' is not an installed ArtisanPack UI package.";

            if ( $this->option( 'json' ) ) {
                $this->line( (string) json_encode( ['error' => $message], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
            } else {
                $this->error( $message );
            }

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
     * Emit the report as JSON.
     *
     * @since 1.2.0
     *
     * @param  array<string, mixed>  $report  The compatibility report.
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

        return $this->resolveExitCode( $report );
    }

    /**
     * Derive the command exit code from the report summary, honouring `--strict`.
     *
     * @since 1.2.0
     *
     * @param  array<string, mixed>  $report  The compatibility report.
     *
     * @return int Command exit code.
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
     * Render the report in human-readable form on the console.
     *
     * @since 1.2.0
     *
     * @param  array<string, mixed>  $report  The compatibility report.
     *
     * @return void
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
     * Render the installed-packages section of the report.
     *
     * @since 1.2.0
     *
     * @param  array<string, mixed>  $report  The compatibility report.
     *
     * @return void
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
     * Render a single titled section of result rows.
     *
     * @since 1.2.0
     *
     * @param  string                            $title         Section heading.
     * @param  array<int, array<string, mixed>>  $results       Result rows in the section.
     * @param  string                            $emptyMessage  Message used when the section is empty.
     *
     * @return void
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
     * Render the summary line ( error / warning counts ).
     *
     * @since 1.2.0
     *
     * @param  array<string, mixed>  $report  The compatibility report.
     *
     * @return void
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
     * Render the remediation recommendations, if any.
     *
     * @since 1.2.0
     *
     * @param  array<string, mixed>  $report  The compatibility report.
     *
     * @return void
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

    /**
     * Map a severity string to a Unicode symbol used in the rendered output.
     *
     * @since 1.2.0
     *
     * @param  string  $severity  One of `error`, `warning`, or `pass`.
     *
     * @return string The corresponding Unicode symbol.
     */
    protected function symbolFor( string $severity ): string
    {
        return match ( $severity ) {
            'error'   => '✗',
            'warning' => '⚠',
            default   => '✓',
        };
    }
}
