<?php

/**
 * Verifies the unified ArtisanPack UI configuration file exists and has entries
 * matching the installed packages.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Diagnostics\Checks
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Diagnostics\Checks;

// PHP requires the lowercase __construct magic method; the
// MagicMethodNotUppercase sniff misfires on the constructor here.
// phpcs:disable ArtisanPackUI.TypeHints.TypeDeclaration.MagicMethodNotUppercase

use ArtisanPackUI\Core\Diagnostics\DiagnosticResult;
use ArtisanPackUI\Core\Diagnostics\DiagnosticStatus;
use ArtisanPackUI\Core\Diagnostics\Support\ComposerLock;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;

/**
 * Diagnostic check that confirms the unified `config/artisanpack.php` exists
 * and contains a section for every installed ArtisanPack UI package.
 *
 * Supports auto-fix: when the configuration file is missing or out of date the
 * check can invoke `artisanpack:scaffold-config` to repair it without manual
 * intervention.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Diagnostics\Checks
 *
 * @since      1.2.0
 */
class ConfigurationCheck implements DiagnosticCheck
{
    /**
     * Create a new configuration check.
     *
     * @since 1.2.0
     *
     * @param  Application  $app  The Laravel application used to read the config repository.
     */
    public function __construct( protected Application $app )
    {
    }

    /**
     * {@inheritDoc}
     */
    public function id(): string
    {
        return 'configuration';
    }

    /**
     * {@inheritDoc}
     */
    public function name(): string
    {
        return 'Configuration';
    }

    /**
     * {@inheritDoc}
     */
    public function run(): array
    {
        $results    = [];
        $configPath = config_path( 'artisanpack.php' );

        if ( ! file_exists( $configPath ) ) {
            $results[] = new DiagnosticResult(
                label: 'config/artisanpack.php exists',
                status: DiagnosticStatus::Error,
                message: 'Run php artisan artisanpack:scaffold-config to publish it.',
                details: [
                    'path'    => $configPath,
                    'exists'  => false,
                    'fixable' => true,
                ],
            );

            return $results;
        }

        $results[] = new DiagnosticResult(
            label: 'config/artisanpack.php exists',
            status: DiagnosticStatus::Pass,
            details: [
                'path'   => $configPath,
                'exists' => true,
            ],
        );

        $config              = $this->app['config']->get( 'artisanpack', [] );
        $config              = is_array( $config ) ? $config : [];
        $installedKeys       = $this->installedPackageKeys();
        $configKeys          = array_keys( $config );
        $missingForInstalled = array_diff( $installedKeys, $configKeys );
        $orphanedKeys        = array_diff( $configKeys, $installedKeys, ['core'] );

        if ( empty( $missingForInstalled ) ) {
            $results[] = new DiagnosticResult(
                label: 'All installed packages have configuration',
                status: DiagnosticStatus::Pass,
                details: ['installed_keys' => $installedKeys],
            );
        } else {
            foreach ( $missingForInstalled as $packageKey ) {
                $results[] = new DiagnosticResult(
                    label: "Missing configuration for installed package: {$packageKey}",
                    status: DiagnosticStatus::Warning,
                    message: 'Run php artisan artisanpack:scaffold-config to add it.',
                    details: [
                        'package_key' => $packageKey,
                        'fixable'     => true,
                    ],
                );
            }
        }

        foreach ( $orphanedKeys as $packageKey ) {
            $results[] = new DiagnosticResult(
                label: "{$packageKey} config found but package not installed",
                status: DiagnosticStatus::Warning,
                details: ['package_key' => $packageKey],
            );
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsFix(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function fix( callable $writer ): bool
    {
        $configPath = config_path( 'artisanpack.php' );
        $missing    = ! file_exists( $configPath );

        $config              = $this->app['config']->get( 'artisanpack', [] );
        $config              = is_array( $config ) ? $config : [];
        $missingForInstalled = array_diff( $this->installedPackageKeys(), array_keys( $config ) );

        if ( ! $missing && empty( $missingForInstalled ) ) {
            $writer( 'Nothing to fix — configuration is up to date.' );

            return true;
        }

        $writer( 'Running artisanpack:scaffold-config to repair configuration...' );
        $exitCode = $this->app->make( \Illuminate\Contracts\Console\Kernel::class )
            ->call( 'artisanpack:scaffold-config' );

        if ( 0 !== $exitCode ) {
            $writer( "artisanpack:scaffold-config exited with code {$exitCode}." );

            return false;
        }

        return true;
    }

    /**
     * Build the kebab-case package keys we'd expect to find in the config.
     *
     * @since 1.2.0
     *
     * @return array<int, string> The expected config keys for installed packages.
     */
    protected function installedPackageKeys(): array
    {
        $keys = [];

        foreach ( ComposerLock::packages() as $package ) {
            $name = $package['name'] ?? null;

            if ( ! is_string( $name ) || ! str_starts_with( $name, 'artisanpack-ui/' ) ) {
                continue;
            }

            $shortName = str_replace( 'artisanpack-ui/', '', $name );

            // Skip code-style tooling packages — they don't publish config.
            if ( in_array( $shortName, ['code-style', 'code-style-pint'], true ) ) {
                continue;
            }

            $keys[] = Str::kebab( $shortName );
        }

        return array_values( array_unique( $keys ) );
    }
}
