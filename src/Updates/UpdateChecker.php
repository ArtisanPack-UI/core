<?php

/**
 * Compares installed ArtisanPack UI packages against Packagist to determine
 * which packages have updates available.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Updates
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Updates;

use ArtisanPackUI\Core\Diagnostics\Checks\InstalledPackagesCheck;
use ArtisanPackUI\Core\Diagnostics\Support\ComposerLock;
use Composer\Semver\Comparator;
use Throwable;

/**
 * Reads the application's `composer.lock`, asks Packagist for the latest
 * release of every installed ArtisanPack UI package, and produces a
 * structured update report.
 *
 * Reports classify each update as a `major`, `minor`, or `patch` bump,
 * flag security-related releases by scanning notes for {@see self::SECURITY_KEYWORDS},
 * and support filtering via `minor_only` and `security_only` flags. Consumed
 * by the `artisanpack:check-updates` command and registered as a container
 * singleton by {@see \ArtisanPackUI\Core\CoreServiceProvider}.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Updates
 *
 * @since      1.2.0
 */
class UpdateChecker
{
    /**
     * Keywords used to flag a release as security-related.
     *
     * @since 1.2.0
     *
     * @var array<int, string>
     */
    protected const SECURITY_KEYWORDS = [
        'security',
        'vulnerability',
        'vulnerabilities',
        'cve-',
        'xss',
        'csrf',
        'sql injection',
        'remote code execution',
    ];

    /**
     * Optional override of the composer.lock package list, primarily for testing.
     *
     * @since 1.2.0
     *
     * @var array<int, array<string, mixed>>|null
     */
    protected ?array $packagesOverride = null;

    /**
     * Create a new update checker.
     *
     * @since 1.2.0
     *
     * @param  PackagistClient  $client  Packagist client used to look up latest release metadata.
     */
    public function __construct( protected PackagistClient $client )
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
     * Run the check and return a structured report.
     *
     * Filters:
     *   - minor_only:    only include minor or patch updates
     *   - security_only: only include security-related updates
     *
     * @since 1.2.0
     *
     * @param  array{minor_only?: bool, security_only?: bool}  $filters  Optional filter flags.
     *
     * @return array<string, mixed> The structured update report consumed by the command renderer.
     */
    public function check( array $filters = [] ): array
    {
        $installed    = $this->getInstalledArtisanPackPackages();
        $minorOnly    = (bool) ( $filters['minor_only'] ?? false );
        $securityOnly = (bool) ( $filters['security_only'] ?? false );

        $updates  = [];
        $upToDate = [];
        $errors   = [];

        foreach ( $installed as $name => $currentVersion ) {
            try {
                $latest = $this->client->getLatestRelease( $name );
            } catch ( Throwable $e ) {
                $errors[] = [
                    'package' => $name,
                    'message' => $e->getMessage(),
                ];

                continue;
            }

            if ( null === $latest ) {
                $errors[] = [
                    'package' => $name,
                    'message' => 'No release information available from Packagist.',
                ];

                continue;
            }

            $latestVersion = ltrim( (string) ( $latest['version'] ?? '' ), 'v' );

            if ( '' === $latestVersion ) {
                $errors[] = [
                    'package' => $name,
                    'message' => 'Packagist response did not include a version string.',
                ];

                continue;
            }

            if ( ! $this->isNewer( $currentVersion, $latestVersion ) ) {
                $upToDate[ $name ] = $currentVersion;

                continue;
            }

            $type     = $this->classifyChange( $currentVersion, $latestVersion );
            $notes    = $this->extractNotes( $latest );
            $security = $this->isSecurityRelease( $notes );

            $entry = [
                'package'         => $name,
                'current_version' => $currentVersion,
                'latest_version'  => $latestVersion,
                'type'            => $type,
                'security'        => $security,
                'breaking'        => 'major' === $type,
                'notes'           => $notes,
                'source_url'      => $this->extractSourceUrl( $latest ),
            ];

            if ( $minorOnly && 'major' === $type ) {
                continue;
            }

            if ( $securityOnly && false === $security ) {
                continue;
            }

            $updates[] = $entry;
        }

        ksort( $upToDate );

        return [
            'updates'          => $updates,
            'up_to_date'       => $upToDate,
            'errors'           => $errors,
            'filters'          => [
                'minor_only'    => $minorOnly,
                'security_only' => $securityOnly,
            ],
            'summary'          => [
                'total_installed'   => count( $installed ),
                'updates_available' => count( $updates ),
                'major_updates'     => count( array_filter( $updates, fn ( array $u ): bool => 'major' === $u['type'] ) ),
                'security_updates'  => count( array_filter( $updates, fn ( array $u ): bool => true === $u['security'] ) ),
                'errors'            => count( $errors ),
            ],
        ];
    }

    /**
     * Build the map of installed ArtisanPack UI packages keyed by name.
     *
     * @since 1.2.0
     *
     * @return array<string, string> Package name → installed version (leading `v` stripped).
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

            $packages[ $name ] = ltrim( (string) ( $package['version'] ?? '' ), 'v' );
        }

        ksort( $packages );

        return $packages;
    }

    /**
     * Return the package list to evaluate, honouring the test override.
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
     * Determine whether the latest version is strictly newer than the current.
     *
     * @since 1.2.0
     *
     * @param  string  $current  The currently installed version.
     * @param  string  $latest   The latest version reported by Packagist.
     *
     * @return bool True when `$latest` is greater than `$current`.
     */
    protected function isNewer( string $current, string $latest ): bool
    {
        if ( '' === $current || '' === $latest ) {
            return false;
        }

        try {
            return Comparator::greaterThan( $latest, $current );
        } catch ( Throwable $e ) {
            return false;
        }
    }

    /**
     * Classify the change between two version strings as major / minor / patch.
     *
     * @since 1.2.0
     *
     * @param  string  $current  The currently installed version.
     * @param  string  $latest   The latest version reported by Packagist.
     *
     * @return string One of `major`, `minor`, `patch`, or `unknown`.
     */
    protected function classifyChange( string $current, string $latest ): string
    {
        $currentParts = $this->parseVersion( $current );
        $latestParts  = $this->parseVersion( $latest );

        if ( null === $currentParts || null === $latestParts ) {
            return 'unknown';
        }

        if ( $latestParts[0] > $currentParts[0] ) {
            return 'major';
        }

        if ( $latestParts[1] > $currentParts[1] ) {
            return 'minor';
        }

        if ( $latestParts[2] > $currentParts[2] ) {
            return 'patch';
        }

        return 'unknown';
    }

    /**
     * Extract the major / minor / patch components from a version string.
     *
     * @since 1.2.0
     *
     * @param  string  $version  The version string to parse.
     *
     * @return array{0: int, 1: int, 2: int}|null `[major, minor, patch]`, or null when the string is unparseable.
     */
    protected function parseVersion( string $version ): ?array
    {
        if ( ! preg_match( '/^(\d+)\.(\d+)\.(\d+)/', $version, $matches ) ) {
            return null;
        }

        return [ (int) $matches[1], (int) $matches[2], (int) $matches[3] ];
    }

    /**
     * Pull the human-readable release notes out of a Packagist release entry.
     *
     * @since 1.2.0
     *
     * @param  array<string, mixed>  $release  The raw Packagist release entry.
     *
     * @return array<int, string> Notes ( description + keywords ) for the release.
     */
    protected function extractNotes( array $release ): array
    {
        $notes = [];

        $description = $release['description'] ?? null;

        if ( is_string( $description ) && '' !== trim( $description ) ) {
            $notes[] = trim( $description );
        }

        $keywords = $release['keywords'] ?? null;

        if ( is_array( $keywords ) ) {
            foreach ( $keywords as $keyword ) {
                if ( is_string( $keyword ) && '' !== trim( $keyword ) ) {
                    $notes[] = trim( $keyword );
                }
            }
        }

        return $notes;
    }

    /**
     * Pull the canonical source URL out of a Packagist release entry.
     *
     * @since 1.2.0
     *
     * @param  array<string, mixed>  $release  The raw Packagist release entry.
     *
     * @return string|null The source URL, or null when none is available.
     */
    protected function extractSourceUrl( array $release ): ?string
    {
        $source = $release['source'] ?? null;

        if ( is_array( $source ) && is_string( $source['url'] ?? null ) ) {
            return $source['url'];
        }

        $homepage = $release['homepage'] ?? null;

        if ( is_string( $homepage ) && '' !== $homepage ) {
            return $homepage;
        }

        return null;
    }

    /**
     * Determine whether the supplied release notes describe a security release.
     *
     * @since 1.2.0
     *
     * @param  array<int, string>  $notes  Release notes harvested by {@see extractNotes()}.
     *
     * @return bool True when the notes contain any keyword in {@see self::SECURITY_KEYWORDS}.
     */
    protected function isSecurityRelease( array $notes ): bool
    {
        if ( empty( $notes ) ) {
            return false;
        }

        $haystack = strtolower( implode( ' ', $notes ) );

        foreach ( self::SECURITY_KEYWORDS as $keyword ) {
            if ( str_contains( $haystack, $keyword ) ) {
                return true;
            }
        }

        return false;
    }
}
