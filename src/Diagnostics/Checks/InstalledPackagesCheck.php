<?php

/**
 * Reports which ArtisanPack UI packages are installed and at what version.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Diagnostics\Checks
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Diagnostics\Checks;

use ArtisanPackUI\Core\Diagnostics\DiagnosticResult;
use ArtisanPackUI\Core\Diagnostics\DiagnosticStatus;
use ArtisanPackUI\Core\Diagnostics\Support\ComposerLock;

/**
 * Diagnostic check that surveys the application's `composer.lock` and reports
 * the installed status of every known ArtisanPack UI package.
 *
 * The package list is the canonical inventory consumed by other diagnostics
 * ( {@see \ArtisanPackUI\Core\Updates\UpdateChecker},
 * {@see \ArtisanPackUI\Core\Compatibility\CompatibilityChecker} ) to decide
 * which packages they should consider.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Diagnostics\Checks
 *
 * @since      1.2.0
 */
class InstalledPackagesCheck implements DiagnosticCheck
{
    /**
     * Canonical list of ArtisanPack UI packages we report on.
     *
     * @since 1.2.0
     *
     * @var array<int, string>
     */
    public const KNOWN_PACKAGES = [
        'artisanpack-ui/accessibility',
        'artisanpack-ui/cms-framework',
        'artisanpack-ui/code-style',
        'artisanpack-ui/code-style-pint',
        'artisanpack-ui/core',
        'artisanpack-ui/hooks',
        'artisanpack-ui/icons',
        'artisanpack-ui/livewire-ui-components',
        'artisanpack-ui/media-library',
        'artisanpack-ui/security',
    ];

    /**
     * {@inheritDoc}
     */
    public function id(): string
    {
        return 'packages';
    }

    /**
     * {@inheritDoc}
     */
    public function name(): string
    {
        return 'Installed Packages';
    }

    /**
     * {@inheritDoc}
     */
    public function run(): array
    {
        $installed = $this->getInstalledPackageVersions();
        $results   = [];

        foreach ( self::KNOWN_PACKAGES as $package ) {
            if ( isset( $installed[ $package ] ) ) {
                $version   = $installed[ $package ];
                $results[] = new DiagnosticResult(
                    label: "{$package}: {$version}",
                    status: DiagnosticStatus::Pass,
                    details: ['package' => $package, 'version' => $version, 'installed' => true],
                );
            } else {
                $results[] = new DiagnosticResult(
                    label: "{$package}: not installed",
                    status: DiagnosticStatus::Info,
                    message: 'optional package',
                    details: ['package' => $package, 'installed' => false],
                );
            }
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsFix(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function fix( callable $writer ): bool
    {
        return false;
    }

    /**
     * Read the installed ArtisanPack UI packages from `composer.lock`.
     *
     * @since 1.2.0
     *
     * @return array<string, string> Map of package name → version (leading `v` stripped).
     */
    protected function getInstalledPackageVersions(): array
    {
        $packages = [];

        foreach ( ComposerLock::packages() as $package ) {
            $name = $package['name'] ?? null;

            if ( ! is_string( $name ) || ! str_starts_with( $name, 'artisanpack-ui/' ) ) {
                continue;
            }

            $packages[ $name ] = ltrim( (string) ( $package['version'] ?? '' ), 'v' );
        }

        return $packages;
    }
}
