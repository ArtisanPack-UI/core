<?php

/**
 * Verifies the unified ArtisanPack UI configuration file exists and has entries
 * matching the installed packages.
 *
 * @since 1.2.0
 */

namespace ArtisanPackUI\Core\Diagnostics\Checks;

use ArtisanPackUI\Core\Diagnostics\DiagnosticResult;
use ArtisanPackUI\Core\Diagnostics\DiagnosticStatus;
use ArtisanPackUI\Core\Diagnostics\Support\ComposerLock;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;

class ConfigurationCheck implements DiagnosticCheck
{
    public function __construct(protected Application $app) {}

    public function id(): string
    {
        return 'configuration';
    }

    public function name(): string
    {
        return 'Configuration';
    }

    public function run(): array
    {
        $results = [];
        $configPath = config_path('artisanpack.php');

        if (! file_exists($configPath)) {
            $results[] = new DiagnosticResult(
                label: 'config/artisanpack.php exists',
                status: DiagnosticStatus::Error,
                message: 'Run php artisan artisanpack:scaffold-config to publish it.',
                details: ['path' => $configPath, 'exists' => false, 'fixable' => true],
            );

            return $results;
        }

        $results[] = new DiagnosticResult(
            label: 'config/artisanpack.php exists',
            status: DiagnosticStatus::Pass,
            details: ['path' => $configPath, 'exists' => true],
        );

        $config = $this->app['config']->get('artisanpack', []);
        $installedKeys = $this->installedPackageKeys();
        $configKeys = array_keys($config);
        $missingForInstalled = array_diff($installedKeys, $configKeys);
        $orphanedKeys = array_diff($configKeys, $installedKeys, ['core']);

        if (empty($missingForInstalled)) {
            $results[] = new DiagnosticResult(
                label: 'All installed packages have configuration',
                status: DiagnosticStatus::Pass,
                details: ['installed_keys' => $installedKeys],
            );
        } else {
            foreach ($missingForInstalled as $packageKey) {
                $results[] = new DiagnosticResult(
                    label: "Missing configuration for installed package: {$packageKey}",
                    status: DiagnosticStatus::Warning,
                    message: 'Run php artisan artisanpack:scaffold-config to add it.',
                    details: ['package_key' => $packageKey, 'fixable' => true],
                );
            }
        }

        foreach ($orphanedKeys as $packageKey) {
            $results[] = new DiagnosticResult(
                label: "{$packageKey} config found but package not installed",
                status: DiagnosticStatus::Warning,
                details: ['package_key' => $packageKey],
            );
        }

        return $results;
    }

    public function supportsFix(): bool
    {
        return true;
    }

    public function fix(callable $writer): bool
    {
        $configPath = config_path('artisanpack.php');
        $missing = ! file_exists($configPath);

        $config = $this->app['config']->get('artisanpack', []);
        $missingForInstalled = array_diff($this->installedPackageKeys(), array_keys($config));

        if (! $missing && empty($missingForInstalled)) {
            return false;
        }

        $writer('Running artisanpack:scaffold-config to repair configuration...');
        $exitCode = $this->app->make(\Illuminate\Contracts\Console\Kernel::class)
            ->call('artisanpack:scaffold-config');

        if ($exitCode !== 0) {
            $writer("artisanpack:scaffold-config exited with code {$exitCode}.");

            return false;
        }

        return true;
    }

    /**
     * Build the kebab-case package keys we'd expect to find in the config.
     *
     * @return array<int, string>
     */
    protected function installedPackageKeys(): array
    {
        $keys = [];

        foreach (ComposerLock::packages() as $package) {
            $name = $package['name'] ?? null;

            if (! is_string($name) || ! str_starts_with($name, 'artisanpack-ui/')) {
                continue;
            }

            $shortName = str_replace('artisanpack-ui/', '', $name);

            // Skip code-style tooling packages — they don't publish config.
            if (in_array($shortName, ['code-style', 'code-style-pint'], true)) {
                continue;
            }

            $keys[] = Str::kebab($shortName);
        }

        return array_values(array_unique($keys));
    }
}
