<?php

/**
 * Analyzes installed ArtisanPack UI packages for cross-package, PHP, Laravel,
 * and PHP extension compatibility.
 *
 * @since 1.2.0
 */

namespace ArtisanPackUI\Core\Compatibility;

use ArtisanPackUI\Core\Diagnostics\Checks\InstalledPackagesCheck;
use ArtisanPackUI\Core\Diagnostics\Support\ComposerLock;
use Composer\Semver\Semver;
use Illuminate\Contracts\Foundation\Application;
use Throwable;

class CompatibilityChecker
{
    /**
     * Optional override of the composer.lock package list, primarily for testing.
     *
     * @var array<int, array<string, mixed>>|null
     */
    protected ?array $packagesOverride = null;

    protected ?string $phpVersionOverride = null;

    protected ?string $laravelVersionOverride = null;

    /**
     * Override the loaded composer extensions, primarily for testing.
     *
     * @var array<int, string>|null
     */
    protected ?array $loadedExtensionsOverride = null;

    public function __construct( protected Application $app )
    {
    }

    /**
     * @param  array<int, array<string, mixed>>  $packages
     */
    public function setPackages( array $packages ): self
    {
        $this->packagesOverride = $packages;

        return $this;
    }

    public function setPhpVersion( string $version ): self
    {
        $this->phpVersionOverride = $version;

        return $this;
    }

    public function setLaravelVersion( string $version ): self
    {
        $this->laravelVersionOverride = $version;

        return $this;
    }

    /**
     * @param  array<int, string>  $extensions
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
     * @return array<string, mixed>
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

    public function packageIsInstalled( string $name ): bool
    {
        return isset( $this->getInstalledArtisanPackPackages()[ $name ] );
    }

    /**
     * @return array<string, array{version: string, require: array<string, string>}>
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
     * @return array<int, array<string, mixed>>
     */
    protected function getPackages(): array
    {
        if ( null !== $this->packagesOverride ) {
            return $this->packagesOverride;
        }

        return ComposerLock::packages();
    }

    protected function resolvePhpVersion(): string
    {
        return $this->phpVersionOverride ?? PHP_VERSION;
    }

    protected function resolveLaravelVersion(): string
    {
        if ( null !== $this->laravelVersionOverride ) {
            return $this->laravelVersionOverride;
        }

        return (string) $this->app->version();
    }

    /**
     * @return array<int, string>
     */
    protected function resolveLoadedExtensions(): array
    {
        return $this->loadedExtensionsOverride ?? get_loaded_extensions();
    }

    protected function isLaravelRequirement( string $name ): bool
    {
        return 'laravel/framework' === $name
            || 'illuminate/support' === $name
            || str_starts_with( $name, 'illuminate/' );
    }

    /**
     * @return array<string, mixed>
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
     * @param  array<int, string>  $extensions
     *
     * @return array<string, mixed>
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
     * @param  array<string, array{version: string, require: array<string, string>}>  $installed
     *
     * @return array<string, mixed>
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
     * @param  array<int, array<string, mixed>>  $dependencyResults
     * @param  array<int, array<string, mixed>>  $phpResults
     * @param  array<int, array<string, mixed>>  $laravelResults
     * @param  array<int, array<string, mixed>>  $extensionResults
     *
     * @return array<string, int>
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
     * @param  array<int, array<string, mixed>>  $dependencyResults
     * @param  array<int, array<string, mixed>>  $phpResults
     * @param  array<int, array<string, mixed>>  $laravelResults
     * @param  array<int, array<string, mixed>>  $extensionResults
     *
     * @return array<int, string>
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
