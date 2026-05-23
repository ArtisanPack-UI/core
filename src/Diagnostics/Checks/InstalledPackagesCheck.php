<?php

/**
 * Reports which ArtisanPack UI packages are installed and at what version.
 *
 * @since 1.2.0
 */

namespace ArtisanPackUI\Core\Diagnostics\Checks;

use ArtisanPackUI\Core\Diagnostics\DiagnosticResult;
use ArtisanPackUI\Core\Diagnostics\DiagnosticStatus;
use ArtisanPackUI\Core\Diagnostics\Support\ComposerLock;

class InstalledPackagesCheck implements DiagnosticCheck
{
    /**
     * Canonical list of ArtisanPack UI packages we report on.
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

    public function id(): string
    {
        return 'packages';
    }

    public function name(): string
    {
        return 'Installed Packages';
    }

    public function run(): array
    {
        $installed = $this->getInstalledPackageVersions();
        $results = [];

        foreach (self::KNOWN_PACKAGES as $package) {
            if (isset($installed[$package])) {
                $version = $installed[$package];
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

    public function supportsFix(): bool
    {
        return false;
    }

    public function fix(callable $writer): bool
    {
        return false;
    }

    /**
     * @return array<string, string>
     */
    protected function getInstalledPackageVersions(): array
    {
        $packages = [];

        foreach (ComposerLock::packages() as $package) {
            $name = $package['name'] ?? null;

            if (! is_string($name) || ! str_starts_with($name, 'artisanpack-ui/')) {
                continue;
            }

            $packages[$name] = ltrim((string) ($package['version'] ?? ''), 'v');
        }

        return $packages;
    }
}
