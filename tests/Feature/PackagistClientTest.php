<?php

use ArtisanPackUI\Core\Updates\PackagistClient;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Build a fake Packagist /p2 response payload for the given package.
 *
 * @param  array<int, array<string, mixed>>  $versions
 *
 * @return array<string, mixed>
 */
function packagistPayload( string $package, array $versions ): array
{
    return [
        'packages' => [ $package => $versions ],
    ];
}

/**
 * Build the fake Http::fake() map for a single packagist package URL.
 *
 * @param  array<int, array<string, mixed>>  $versions
 *
 * @return array<string, mixed>
 */
function packagistFakeMap( string $package, array $versions ): array
{
    $url      = 'repo.packagist.org/p2/' . $package . '.json';
    $response = Http::response( packagistPayload( $package, $versions ) );

    return [
        $url => $response,
    ];
}

beforeEach( function (): void {
    Cache::clear();
    $this->client = $this->app->make( PackagistClient::class );
} );

test( 'returns the latest stable release and skips dev/pre-release versions', function (): void {
    $stableRelease = [
        'version'     => '1.2.0',
        'description' => 'Stable release',
    ];

    Http::fake( packagistFakeMap( 'artisanpack-ui/core', [
        ['version' => 'dev-main'],
        ['version' => '2.0.0-beta.1'],
        $stableRelease,
        ['version' => '1.1.0'],
    ] ) );

    $latest = $this->client->getLatestRelease( 'artisanpack-ui/core' );

    expect( $latest )->not->toBeNull();
    expect( $latest['version'] )->toBe( '1.2.0' );
    expect( $latest['description'] )->toBe( 'Stable release' );
} );

test( 'returns null when packagist responds with an error', function (): void {
    $errorResponse = Http::response( '', 404 );

    Http::fake( [
        'repo.packagist.org/p2/artisanpack-ui/missing.json' => $errorResponse,
    ] );

    $latest = $this->client->getLatestRelease( 'artisanpack-ui/missing' );

    expect( $latest )->toBeNull();
} );

test( 'caches results so subsequent calls do not re-query packagist', function (): void {
    Http::fake( packagistFakeMap( 'artisanpack-ui/core', [
        ['version' => '1.2.0'],
    ] ) );

    $this->client->getLatestRelease( 'artisanpack-ui/core' );
    $this->client->getLatestRelease( 'artisanpack-ui/core' );

    Http::assertSentCount( 1 );
} );

test( 'sorts releases by version so the newest stable is returned first', function (): void {
    Http::fake( packagistFakeMap( 'artisanpack-ui/core', [
        ['version' => '1.1.0'],
        ['version' => '1.2.0'],
        ['version' => '1.1.5'],
        ['version' => '1.0.0'],
    ] ) );

    $latest = $this->client->getLatestRelease( 'artisanpack-ui/core' );

    expect( $latest )->not->toBeNull();
    expect( $latest['version'] )->toBe( '1.2.0' );
} );

test( 'forget clears the cache for a package', function (): void {
    Http::fake( packagistFakeMap( 'artisanpack-ui/core', [
        ['version' => '1.2.0'],
    ] ) );

    $this->client->getLatestRelease( 'artisanpack-ui/core' );
    $this->client->forget( 'artisanpack-ui/core' );
    $this->client->getLatestRelease( 'artisanpack-ui/core' );

    Http::assertSentCount( 2 );
} );

test( 'refreshes the cache TTL via touch() when the repository supports it', function (): void {
    Http::fake( packagistFakeMap( 'artisanpack-ui/core', [
        ['version' => '1.2.0'],
    ] ) );

    $touchableCache = new class( new ArrayStore() ) extends CacheRepository {
        /** @var array<int, array{key: string, ttl: int|null}> */
        public array $touchCalls = [];

        /** @var array<int, array{key: string, ttl: int|null}> */
        public array $putCalls = [];

        public function touch( mixed $key, mixed $ttl = null ): bool
        {
            $this->touchCalls[] = [
                'key' => (string) $key,
                'ttl' => $ttl,
            ];

            return true;
        }

        public function put( mixed $key, mixed $value, mixed $ttl = null ): bool
        {
            $this->putCalls[] = [
                'key' => (string) $key,
                'ttl' => $ttl,
            ];

            return parent::put( $key, $value, $ttl );
        }
    };

    $client = new PackagistClient(
        $this->app->make( HttpFactory::class ),
        $touchableCache,
        $this->app->make( ConfigRepository::class ),
    );

    $client->getLatestRelease( 'artisanpack-ui/core' );
    $client->getLatestRelease( 'artisanpack-ui/core' );

    expect( $touchableCache->touchCalls )->toHaveCount( 1 );
    expect( $touchableCache->touchCalls[0]['key'] )->toBe( 'artisanpack-ui:updates:artisanpack-ui/core' );
    expect( $touchableCache->touchCalls[0]['ttl'] )->toBe( 3600 );

    // put() is only called on the initial miss, not on the touch-supported hit.
    expect( $touchableCache->putCalls )->toHaveCount( 1 );
} );

test( 'falls back to put() when touch() reports failure', function (): void {
    Http::fake( packagistFakeMap( 'artisanpack-ui/core', [
        ['version' => '1.2.0'],
    ] ) );

    $unreliableCache = new class( new ArrayStore() ) extends CacheRepository {
        /** @var array<int, array{key: string, ttl: int|null}> */
        public array $touchCalls = [];

        /** @var array<int, array{key: string, ttl: int|null}> */
        public array $putCalls = [];

        public function touch( mixed $key, mixed $ttl = null ): bool
        {
            $this->touchCalls[] = [
                'key' => (string) $key,
                'ttl' => $ttl,
            ];

            return false;
        }

        public function put( mixed $key, mixed $value, mixed $ttl = null ): bool
        {
            $this->putCalls[] = [
                'key' => (string) $key,
                'ttl' => $ttl,
            ];

            return parent::put( $key, $value, $ttl );
        }
    };

    $client = new PackagistClient(
        $this->app->make( HttpFactory::class ),
        $unreliableCache,
        $this->app->make( ConfigRepository::class ),
    );

    $client->getLatestRelease( 'artisanpack-ui/core' );
    $client->getLatestRelease( 'artisanpack-ui/core' );

    // touch() was attempted but returned false, so the put() fallback ran.
    expect( $unreliableCache->touchCalls )->toHaveCount( 1 );
    expect( $unreliableCache->putCalls )->toHaveCount( 2 );
    expect( $unreliableCache->putCalls[1]['ttl'] )->toBe( 3600 );
} );

test( 'falls back to put() to refresh TTL when touch() is not available', function (): void {
    Http::fake( packagistFakeMap( 'artisanpack-ui/core', [
        ['version' => '1.2.0'],
    ] ) );

    $fallbackCache = new class( new ArrayStore() ) extends CacheRepository {
        /** @var array<int, array{key: string, ttl: int|null}> */
        public array $putCalls = [];

        public function put( mixed $key, mixed $value, mixed $ttl = null ): bool
        {
            $this->putCalls[] = [
                'key' => (string) $key,
                'ttl' => $ttl,
            ];

            return parent::put( $key, $value, $ttl );
        }
    };

    expect( method_exists( $fallbackCache, 'touch' ) )->toBeFalse();

    $client = new PackagistClient(
        $this->app->make( HttpFactory::class ),
        $fallbackCache,
        $this->app->make( ConfigRepository::class ),
    );

    $client->getLatestRelease( 'artisanpack-ui/core' );
    $client->getLatestRelease( 'artisanpack-ui/core' );

    // First put: cache miss writes the freshly-fetched releases.
    // Second put: cache hit refreshes the TTL via the read-and-restore fallback.
    expect( $fallbackCache->putCalls )->toHaveCount( 2 );
    expect( $fallbackCache->putCalls[1]['ttl'] )->toBe( 3600 );

    // Only one HTTP call should have been made; the second was served from cache.
    Http::assertSentCount( 1 );
} );
