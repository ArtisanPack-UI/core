<?php

/**
 * Compares installed ArtisanPack UI packages against Packagist to determine
 * which packages have updates available.
 *
 * @since 1.2.0
 */

namespace ArtisanPackUI\Core\Updates;

use ArtisanPackUI\Core\Diagnostics\Checks\InstalledPackagesCheck;
use ArtisanPackUI\Core\Diagnostics\Support\ComposerLock;
use Composer\Semver\Comparator;
use Throwable;

class UpdateChecker
{
    /**
     * Keywords used to flag a release as security-related.
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
     * @var array<int, array<string, mixed>>|null
     */
    protected ?array $packagesOverride = null;

    public function __construct( protected PackagistClient $client )
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

    /**
     * Run the check and return a structured report.
     *
     * Filters:
     *   - minor_only:    only include minor or patch updates
     *   - security_only: only include security-related updates
     *
     * @param  array{minor_only?: bool, security_only?: bool}  $filters
     *
     * @return array<string, mixed>
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
     * @return array<string, string>
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
     * @return array<int, array<string, mixed>>
     */
    protected function getPackages(): array
    {
        if ( null !== $this->packagesOverride ) {
            return $this->packagesOverride;
        }

        return ComposerLock::packages();
    }

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
     * @return array{0: int, 1: int, 2: int}|null
     */
    protected function parseVersion( string $version ): ?array
    {
        if ( ! preg_match( '/^(\d+)\.(\d+)\.(\d+)/', $version, $matches ) ) {
            return null;
        }

        return [ (int) $matches[1], (int) $matches[2], (int) $matches[3] ];
    }

    /**
     * @param  array<string, mixed>  $release
     *
     * @return array<int, string>
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
     * @param  array<string, mixed>  $release
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
     * @param  array<int, string>  $notes
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
