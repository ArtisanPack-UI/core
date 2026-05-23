<?php

/**
 * Client for querying the Packagist API for ArtisanPack UI package metadata.
 *
 * @since 1.2.0
 */

namespace ArtisanPackUI\Core\Updates;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Throwable;

class PackagistClient
{
    protected const BASE_URL = 'https://repo.packagist.org/p2/';

    protected const CACHE_PREFIX = 'artisanpack-ui:updates:';

    public function __construct(
        protected HttpFactory $http,
        protected CacheRepository $cache,
        protected ConfigRepository $config,
    ) {
    }

    /**
     * Fetch the full set of stable releases for a package, newest first.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getReleases( string $package ): array
    {
        $ttl = (int) $this->config->get( 'artisanpack.core.updates.cache_ttl', 3600 );
        $key = self::CACHE_PREFIX . $package;

        return $this->cache->remember( $key, $ttl, function () use ( $package ): array {
            return $this->fetchReleases( $package );
        } );
    }

    /**
     * Fetch the latest stable release for a package, or null if unavailable.
     *
     * @return array<string, mixed>|null
     */
    public function getLatestRelease( string $package ): ?array
    {
        $releases = $this->getReleases( $package );

        return $releases[0] ?? null;
    }

    /**
     * Forget cached release data for a package.
     */
    public function forget( string $package ): void
    {
        $this->cache->forget( self::CACHE_PREFIX . $package );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fetchReleases( string $package ): array
    {
        try {
            $response = $this->http
                ->timeout( 10 )
                ->acceptJson()
                ->get( self::BASE_URL . $package . '.json' );
        } catch ( Throwable $e ) {
            return [];
        }

        if ( ! $response->successful() ) {
            return [];
        }

        $payload = $response->json();

        if ( ! is_array( $payload ) ) {
            return [];
        }

        $versions = $payload['packages'][ $package ] ?? [];

        if ( ! is_array( $versions ) ) {
            return [];
        }

        $stable = [];

        foreach ( $versions as $entry ) {
            if ( ! is_array( $entry ) ) {
                continue;
            }

            $version = (string) ( $entry['version'] ?? '' );

            if ( '' === $version || $this->isDevVersion( $version ) ) {
                continue;
            }

            $stable[] = $entry;
        }

        return $stable;
    }

    protected function isDevVersion( string $version ): bool
    {
        $normalized = strtolower( $version );

        if ( str_contains( $normalized, 'dev' ) ) {
            return true;
        }

        foreach ( ['alpha', 'beta', 'rc'] as $marker ) {
            if ( str_contains( $normalized, '-' . $marker ) ) {
                return true;
            }
        }

        return false;
    }
}
