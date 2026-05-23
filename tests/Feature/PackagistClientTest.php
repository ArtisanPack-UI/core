<?php

use ArtisanPackUI\Core\Updates\PackagistClient;
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
