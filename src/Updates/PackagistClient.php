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
     * On a cache hit, refresh the entry's TTL so frequently-checked packages
     * keep their cached releases warm. Laravel 13 added Cache::touch() which
     * extends the TTL without re-reading and re-storing the payload; on
     * Laravel 11/12 we fall back to a put() with the existing value. The
     * adoption is guarded by a method_exists() feature check rather than a
     * Laravel-version check so that custom cache repositories which already
     * expose touch() benefit on older framework versions too.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getReleases( string $package ): array
    {
        $ttl = (int) $this->config->get( 'artisanpack.core.updates.cache_ttl', 3600 );
        $key = self::CACHE_PREFIX . $package;

        $cached = $this->cache->get( $key );

        if ( is_array( $cached ) ) {
            $this->refreshCacheTtl( $key, $ttl, $cached );

            return $cached;
        }

        $releases = $this->fetchReleases( $package );

        $this->cache->put( $key, $releases, $ttl );

        return $releases;
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
     * Extend the TTL of an existing cache entry.
     *
     * Uses Cache::touch() when the underlying repository supports it
     * ( Laravel 13+ or a custom repository that polyfills the method ) so the
     * cached payload does not need to be re-serialised. Falls back to a
     * standard put() on Laravel 11/12, and also when touch() reports failure
     * - this can happen if the entry expired between the get() above and the
     * touch() call here.
     *
     * @param  array<int, array<string, mixed>>  $cached
     */
    protected function refreshCacheTtl( string $key, int $ttl, array $cached ): void
    {
        if ( method_exists( $this->cache, 'touch' ) && true === $this->cache->touch( $key, $ttl ) ) {
            return;
        }

        $this->cache->put( $key, $cached, $ttl );
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

        usort(
            $stable,
            static function ( array $a, array $b ): int {
                $aVersion = ltrim( (string) ( $a['version'] ?? '' ), 'v' );
                $bVersion = ltrim( (string) ( $b['version'] ?? '' ), 'v' );

                return version_compare( $bVersion, $aVersion );
            },
        );

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
