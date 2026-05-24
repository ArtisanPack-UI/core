<?php

/**
 * Analyzes installed ArtisanPack UI packages for cross-package, PHP, Laravel,
 * and PHP extension compatibility.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Compatibility
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Compatibility;

use ArtisanPackUI\Core\Diagnostics\Checks\InstalledPackagesCheck;
use ArtisanPackUI\Core\Diagnostics\Support\ComposerLock;
use Composer\Semver\Semver;
use Illuminate\Contracts\Foundation\Application;
use Throwable;

/**
 * Inspects every installed ArtisanPack UI package's Composer requirements
 * and reports whether the local environment satisfies them.
 *
 * The checker evaluates inter-package dependencies ( does the installed
 * version satisfy the constraint? ), PHP version requirements, Laravel
 * framework requirements, and `ext-*` extension requirements. Override
 * methods are provided so tests can stub each input independently.
 *
 * Consumed by the `artisanpack:check-compatibility` command and registered as
 * a container singleton by {@see \ArtisanPackUI\Core\CoreServiceProvider}.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Compatibility
 *
 * @since      1.2.0
 */
class CompatibilityChecker
{
    /**
     * Optional override of the composer.lock package list, primarily for testing.
     *
     * @since 1.2.0
     *
     * @var array<int, array<string, mixed>>|null
     */
    protected ?array $packagesOverride = null;

    /**
     * Optional override of the PHP version string, primarily for testing.
     *
     * @since 1.2.0
     *
     * @var string|null
     */
    protected ?string $phpVersionOverride = null;

    /**
     * Optional override of the Laravel framework version string, primarily for testing.
     *
     * @since 1.2.0
     *
     * @var string|null
     */
    protected ?string $laravelVersionOverride = null;

    /**
     * Override the loaded composer extensions, primarily for testing.
     *
     * @since 1.2.0
     *
     * @var array<int, string>|null
     */
    protected ?array $loadedExtensionsOverride = null;

    /**
     * Create a new compatibility checker.
     *
     * @since 1.2.0
     *
     * @param  Application  $app  The Laravel application used to resolve the framework version.
     */
    public function __construct( protected Application $app )
    {
    }

    /**
     * Override the composer.lock package list for the next analysis run.
     *
     * @since 1.2.0
     *
     * @param  array<int, array<string, mixed>>  $packages  Stubbed package metadata.
     *
     * @return self For fluent chaining.
     */
    public function setPackages( array $packages ): self
    {
        $this->packagesOverride = $packages;

        return $this;
    }

    /**
     * Override the PHP version string used during analysis.
     *
     * @since 1.2.0
     *
     * @param  string  $version  PHP version (e.g. `8.3.0`).
     *
     * @return self For fluent chaining.
     */
    public function setPhpVersion( string $version ): self
    {
        $this->phpVersionOverride = $version;

        return $this;
    }

    /**
     * Override the Laravel framework version string used during analysis.
     *
     * @since 1.2.0
     *
     * @param  string  $version  Laravel version (e.g. `11.0.0`).
     *
     * @return self For fluent chaining.
     */
    public function setLaravelVersion( string $version ): self
    {
        $this->laravelVersionOverride = $version;

        return $this;
    }

    /**
     * Override the loaded PHP extensions list used during analysis.
     *
     * @since 1.2.0
     *
     * @param  array<int, string>  $extensions  Extension names (without the `ext-` prefix).
     *
     * @return self For fluent chaining.
     */
    public function setLoadedExtensions( array $extensions ): self
    {
        $this->loadedExtensionsOverride = $extensions;

        return $this;
    }

    /**
     * Run the analysis and return a structured report.
     *
     * When $packageFilter is provided, only that ArtisanPack UI package's
     * requirements are evaluated. Installed-package listing is unaffected.
     *
     * @since 1.2.0
     *
     * @param  string|null  $packageFilter  Optional ArtisanPack UI package name to focus on.
     *
     * @return array<string, mixed> The structured report consumed by the command renderer.
     */
    public function analyze( ?string $packageFilter = null ): array
    {
        $installed       = $this->getInstalledArtisanPackPackages();
        $phpVersion      = $this->resolvePhpVersion();
        $laravelVersion  = $this->resolveLaravelVersion();
        $extensions      = $this->resolveLoadedExtensions();

        $packagesToCheck = null === $packageFilter
            ? $installed
            : array_intersect_key( $installed, [$packageFilter => true] );

        $dependencyResults = [];
        $phpResults        = [];
        $laravelResults    = [];
        $extensionResults  = [];

        foreach ( $packagesToCheck as $name => $info ) {
            $require = is_array( $info['require'] ?? null ) ? $info['require'] : [];

            foreach ( $require as $required => $constraint ) {
                $constraint = is_string( $constraint ) ? $constraint : '';

                if ( 'php' === $required ) {
                    $phpResults[] = $this->evaluateConstraint( $name, $required, $constraint, $phpVersion );

                    continue;
                }

                if ( $this->isLaravelRequirement( $required ) ) {
                    $laravelResults[] = $this->evaluateConstraint( $name, $required, $constraint, $laravelVersion );

                    continue;
                }

                if ( str_starts_with( $required, 'ext-' ) ) {
                    $extensionResults[] = $this->evaluateExtension( $name, $required, $extensions );

                    continue;
                }

                if ( ! str_starts_with( $required, 'artisanpack-ui/' ) ) {
                    continue;
                }

                $dependencyResults[] = $this->evaluateDependency( $name, $required, $constraint, $installed );
            }
        }

        $summary = $this->summarize( $dependencyResults, $phpResults, $laravelResults, $extensionResults );

        return [
            'php_version'        => $phpVersion,
            'laravel_version'    => $laravelVersion,
            'installed_packages' => array_map( fn ( array $info ): string => (string) $info['version'], $installed ),
            'filtered_package'   => $packageFilter,
            'dependency_results' => $dependencyResults,
            'php_results'        => $phpResults,
            'laravel_results'    => $laravelResults,
            'extension_results'  => $extensionResults,
            'recommendations'    => $this->buildRecommendations( $dependencyResults, $phpResults, $laravelResults, $extensionResults ),
            'summary'            => $summary,
        ];
    }

    /**
     * Determine whether the given ArtisanPack UI package is installed.
     *
     * @since 1.2.0
     *
     * @param  string  $name  The fully-qualified `artisanpack-ui/*` package name.
     *
     * @return bool True when the package is present in the composer lockfile.
     */
    public function packageIsInstalled( string $name ): bool
    {
        return isset( $this->getInstalledArtisanPackPackages()[ $name ] );
    }

    /**
     * Build the map of installed ArtisanPack UI packages keyed by name.
     *
     * @since 1.2.0
     *
     * @return array<string, array{version: string, require: array<string, string>}>
     *   Version + raw require block per package.
     */
    protected function getInstalledArtisanPackPackages(): array
    {
        $packages = [];

        foreach ( $this->getPackages() as $package ) {
            $name = $package['name'] ?? null;

            if ( ! is_string( $name ) || ! str_starts_with( $name, 'artisanpack-ui/' ) ) {
                continue;
            }

            if ( ! in_array( $name, InstalledPackagesCheck::KNOWN_PACKAGES, true ) ) {
                continue;
            }

            $require = is_array( $package['require'] ?? null ) ? $package['require'] : [];

            $packages[ $name ] = [
                'version' => ltrim( (string) ( $package['version'] ?? '' ), 'v' ),
                'require' => $require,
            ];
        }

        ksort( $packages );

        return $packages;
    }

    /**
     * Return the package list to analyse, honouring the test override.
     *
     * @since 1.2.0
     *
     * @return array<int, array<string, mixed>> The raw composer lockfile entries.
     */
    protected function getPackages(): array
    {
        if ( null !== $this->packagesOverride ) {
            return $this->packagesOverride;
        }

        return ComposerLock::packages();
    }

    /**
     * Resolve the runtime PHP version, honouring the test override.
     *
     * @since 1.2.0
     *
     * @return string The PHP version string to evaluate constraints against.
     */
    protected function resolvePhpVersion(): string
    {
        return $this->phpVersionOverride ?? PHP_VERSION;
    }

    /**
     * Resolve the runtime Laravel framework version, honouring the test override.
     *
     * @since 1.2.0
     *
     * @return string The Laravel version string to evaluate constraints against.
     */
    protected function resolveLaravelVersion(): string
    {
        if ( null !== $this->laravelVersionOverride ) {
            return $this->laravelVersionOverride;
        }

        return (string) $this->app->version();
    }

    /**
     * Resolve the loaded PHP extensions, honouring the test override.
     *
     * @since 1.2.0
     *
     * @return array<int, string> Extension names available at runtime.
     */
    protected function resolveLoadedExtensions(): array
    {
        return $this->loadedExtensionsOverride ?? get_loaded_extensions();
    }

    /**
     * Determine whether a Composer require entry targets the Laravel framework.
     *
     * @since 1.2.0
     *
     * @param  string  $name  Composer package name from the require block.
     *
     * @return bool True when the entry is a Laravel framework constraint.
     */
    protected function isLaravelRequirement( string $name ): bool
    {
        return 'laravel/framework' === $name
            || 'illuminate/support' === $name
            || str_starts_with( $name, 'illuminate/' );
    }

    /**
     * Evaluate a generic version constraint and produce a result row.
     *
     * @since 1.2.0
     *
     * @param  string  $package     The owning package the constraint belongs to.
     * @param  string  $required    The required package name (e.g. `php`, `laravel/framework`).
     * @param  string  $constraint  The Composer constraint string.
     * @param  string  $version     The version to evaluate the constraint against.
     *
     * @return array<string, mixed> The structured result row.
     */
    protected function evaluateConstraint( string $package, string $required, string $constraint, string $version ): array
    {
        $satisfied = $this->satisfies( $version, $constraint );

        return [
            'package'    => $package,
            'required'   => $required,
            'constraint' => $constraint,
            'version'    => $version,
            'satisfied'  => $satisfied,
            'severity'   => $satisfied ? 'pass' : 'error',
            'message'    => $satisfied
                ? "{$required} {$constraint} satisfied by {$version}"
                : "{$package} requires {$required} {$constraint} but found {$version}",
        ];
    }

    /**
     * Evaluate a PHP extension requirement and produce a result row.
     *
     * @since 1.2.0
     *
     * @param  string             $package     The owning package the extension requirement belongs to.
     * @param  string             $required    The fully-qualified `ext-*` requirement.
     * @param  array<int, string> $extensions  Extensions loaded at runtime.
     *
     * @return array<string, mixed> The structured result row.
     */
    protected function evaluateExtension( string $package, string $required, array $extensions ): array
    {
        $extensionName = substr( $required, 4 );
        $available     = in_array( $extensionName, $extensions, true );

        return [
            'package'   => $package,
            'extension' => $extensionName,
            'satisfied' => $available,
            'severity'  => $available ? 'pass' : 'error',
            'message'   => $available
                ? "{$package} extension {$extensionName} available"
                : "{$package} requires PHP extension {$extensionName}",
        ];
    }

    /**
     * Evaluate an inter-package ArtisanPack UI dependency and produce a result row.
     *
     * @since 1.2.0
     *
     * @param  string  $package     Owning package.
     * @param  string  $dependency  Depended-on ArtisanPack UI package.
     * @param  string  $constraint  Composer constraint string.
     * @param  array<string, array{version: string, require: array<string, string>}>  $installed  Installed packages.
     *
     * @return array<string, mixed> The structured result row.
     */
    protected function evaluateDependency( string $package, string $dependency, string $constraint, array $installed ): array
    {
        if ( ! isset( $installed[ $dependency ] ) ) {
            return [
                'package'    => $package,
                'dependency' => $dependency,
                'constraint' => $constraint,
                'version'    => null,
                'satisfied'  => false,
                'severity'   => 'warning',
                'message'    => "{$package} requires {$dependency} {$constraint} but it is not installed",
            ];
        }

        $version   = $installed[ $dependency ]['version'];
        $satisfied = $this->satisfies( $version, $constraint );

        return [
            'package'    => $package,
            'dependency' => $dependency,
            'constraint' => $constraint,
            'version'    => $version,
            'satisfied'  => $satisfied,
            'severity'   => $satisfied ? 'pass' : 'error',
            'message'    => $satisfied
                ? "{$package} requirement on {$dependency} {$constraint} satisfied by {$version}"
                : "{$package} requires {$dependency} {$constraint}, you have {$version}",
        ];
    }

    /**
     * Wrap {@see Semver::satisfies()} so malformed inputs degrade to a soft false.
     *
     * @since 1.2.0
     *
     * @param  string  $version     The version to test.
     * @param  string  $constraint  The Composer constraint to test against.
     *
     * @return bool True when the version satisfies the constraint.
     */
    protected function satisfies( string $version, string $constraint ): bool
    {
        if ( '' === $version || '' === $constraint ) {
            return false;
        }

        try {
            return Semver::satisfies( $version, $constraint );
        } catch ( Throwable $e ) {
            return false;
        }
    }

    /**
     * Roll up the per-row results into an error / warning summary.
     *
     * @since 1.2.0
     *
     * @param  array<int, array<string, mixed>>  $dependencyResults  Inter-package dependency rows.
     * @param  array<int, array<string, mixed>>  $phpResults         PHP version rows.
     * @param  array<int, array<string, mixed>>  $laravelResults     Laravel framework rows.
     * @param  array<int, array<string, mixed>>  $extensionResults   PHP extension rows.
     *
     * @return array<string, int> The aggregated error / warning counts.
     */
    protected function summarize( array $dependencyResults, array $phpResults, array $laravelResults, array $extensionResults ): array
    {
        $errors   = 0;
        $warnings = 0;

        foreach ( [$dependencyResults, $phpResults, $laravelResults, $extensionResults] as $set ) {
            foreach ( $set as $entry ) {
                $severity = $entry['severity'] ?? 'pass';

                if ( 'error' === $severity ) {
                    $errors++;
                } elseif ( 'warning' === $severity ) {
                    $warnings++;
                }
            }
        }

        return [
            'errors'   => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Derive human-readable remediation recommendations from the failing rows.
     *
     * @since 1.2.0
     *
     * @param  array<int, array<string, mixed>>  $dependencyResults  Inter-package dependency rows.
     * @param  array<int, array<string, mixed>>  $phpResults         PHP version rows.
     * @param  array<int, array<string, mixed>>  $laravelResults     Laravel framework rows.
     * @param  array<int, array<string, mixed>>  $extensionResults   PHP extension rows.
     *
     * @return array<int, string> The deduplicated recommendation list.
     */
    protected function buildRecommendations( array $dependencyResults, array $phpResults, array $laravelResults, array $extensionResults ): array
    {
        $recommendations = [];

        foreach ( $dependencyResults as $entry ) {
            if ( false === ( $entry['satisfied'] ?? true ) ) {
                $dependency = $entry['dependency'];
                $constraint = $entry['constraint'];
                $version    = $entry['version'];

                if ( null === $version ) {
                    $recommendations[] = "Install {$dependency} matching {$constraint}.";

                    continue;
                }

                $recommendations[] = "Align {$dependency} with constraint {$constraint} (currently {$version}).";
            }
        }

        foreach ( $phpResults as $entry ) {
            if ( false === ( $entry['satisfied'] ?? true ) ) {
                $recommendations[] = "Use a PHP version that satisfies {$entry['constraint']} (currently {$entry['version']}).";
            }
        }

        foreach ( $laravelResults as $entry ) {
            if ( false === ( $entry['satisfied'] ?? true ) ) {
                $recommendations[] = "Use a Laravel version that satisfies {$entry['required']} {$entry['constraint']} (currently {$entry['version']}).";
            }
        }

        foreach ( $extensionResults as $entry ) {
            if ( false === ( $entry['satisfied'] ?? true ) ) {
                $recommendations[] = "Install the PHP extension {$entry['extension']} required by {$entry['package']}.";
            }
        }

        return array_values( array_unique( $recommendations ) );
    }
}
