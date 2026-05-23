<?php

use ArtisanPackUI\Core\Commands\CheckUpdatesCommand;
use ArtisanPackUI\Core\Updates\PackagistClient;
use ArtisanPackUI\Core\Updates\UpdateChecker;
use Illuminate\Support\Facades\Artisan;

/**
 * Build a fake composer.lock-style package entry.
 *
 * @return array<string, mixed>
 */
function updatePackage( string $name, string $version ): array
{
    return [
        'name'    => $name,
        'version' => $version,
    ];
}

/**
 * Build a fake Packagist release entry.
 *
 * @return array<string, mixed>
 */
function packagistRelease( string $version, ?string $description = null, ?string $sourceUrl = null ): array
{
    $release = [
        'version' => $version,
    ];

    if ( null !== $description ) {
        $release['description'] = $description;
    }

    if ( null !== $sourceUrl ) {
        $release['source'] = [ 'url' => $sourceUrl ];
    }

    return $release;
}

beforeEach( function (): void {
    $this->client = new class extends PackagistClient {
        /** @var array<string, array<string, mixed>|null> */
        public array $releases = [];

        public function __construct()
        {
        }

        public function getLatestRelease( string $package ): ?array
        {
            return $this->releases[ $package ] ?? null;
        }

        public function getReleases( string $package ): array
        {
            $release = $this->releases[ $package ] ?? null;

            return null === $release ? [] : [ $release ];
        }

        public function forget( string $package ): void
        {
            unset( $this->releases[ $package ] );
        }
    };

    $this->checker = new UpdateChecker( $this->client );
    $this->app->instance( UpdateChecker::class, $this->checker );
} );

test( 'command is registered', function (): void {
    $commands = Artisan::all();

    expect( $commands )->toHaveKey( 'artisanpack:check-updates' );
    expect( $commands['artisanpack:check-updates'] )->toBeInstanceOf( CheckUpdatesCommand::class );
} );

test( 'command exposes documented options', function (): void {
    $definition = (new CheckUpdatesCommand)->getDefinition();

    expect( $definition->hasOption( 'json' ) )->toBeTrue();
    expect( $definition->hasOption( 'minor-only' ) )->toBeTrue();
    expect( $definition->hasOption( 'security-only' ) )->toBeTrue();
    expect( $definition->hasOption( 'changelog' ) )->toBeTrue();
} );

test( 'reports updates available with version transitions and types', function (): void {
    $this->checker->setPackages( [
        updatePackage( 'artisanpack-ui/core', '1.1.0' ),
        updatePackage( 'artisanpack-ui/security', '2.0.0' ),
        updatePackage( 'artisanpack-ui/livewire-ui-components', '1.0.0' ),
    ] );

    $this->client->releases = [
        'artisanpack-ui/core'                   => packagistRelease( '1.2.0' ),
        'artisanpack-ui/security'               => packagistRelease( '2.1.0', 'Fixed XSS vulnerability in sanitizer' ),
        'artisanpack-ui/livewire-ui-components' => packagistRelease( '2.0.0' ),
    ];

    $this->artisan( 'artisanpack:check-updates' )
        ->expectsOutputToContain( 'ArtisanPack UI Update Check' )
        ->expectsOutputToContain( 'artisanpack-ui/core: 1.1.0 → 1.2.0 (minor)' )
        ->expectsOutputToContain( 'artisanpack-ui/security: 2.0.0 → 2.1.0 (minor)' )
        ->expectsOutputToContain( 'artisanpack-ui/livewire-ui-components: 1.0.0 → 2.0.0 (major)' )
        ->expectsOutputToContain( 'Breaking changes' )
        ->expectsOutputToContain( '🔒 Security-related release' )
        ->expectsOutputToContain( '3 packages have updates available' )
        ->expectsOutputToContain( 'composer update artisanpack-ui/core artisanpack-ui/livewire-ui-components artisanpack-ui/security' )
        ->assertExitCode( 0 );
} );

test( 'lists up-to-date packages when no newer release exists', function (): void {
    $this->checker->setPackages( [
        updatePackage( 'artisanpack-ui/core', '1.2.0' ),
        updatePackage( 'artisanpack-ui/media-library', '1.0.0' ),
    ] );

    $this->client->releases = [
        'artisanpack-ui/core'          => packagistRelease( '1.2.0' ),
        'artisanpack-ui/media-library' => packagistRelease( '1.0.0' ),
    ];

    $this->artisan( 'artisanpack:check-updates' )
        ->expectsOutputToContain( 'Up to Date:' )
        ->expectsOutputToContain( '✓ artisanpack-ui/core: 1.2.0' )
        ->expectsOutputToContain( '✓ artisanpack-ui/media-library: 1.0.0' )
        ->expectsOutputToContain( 'All packages are up to date' )
        ->assertExitCode( 0 );
} );

test( 'minor-only filter excludes major updates', function (): void {
    $this->checker->setPackages( [
        updatePackage( 'artisanpack-ui/core', '1.1.0' ),
        updatePackage( 'artisanpack-ui/livewire-ui-components', '1.0.0' ),
    ] );

    $this->client->releases = [
        'artisanpack-ui/core'                   => packagistRelease( '1.2.0' ),
        'artisanpack-ui/livewire-ui-components' => packagistRelease( '2.0.0' ),
    ];

    $this->artisan( 'artisanpack:check-updates', ['--minor-only' => true] )
        ->expectsOutputToContain( 'artisanpack-ui/core: 1.1.0 → 1.2.0 (minor)' )
        ->doesntExpectOutputToContain( 'artisanpack-ui/livewire-ui-components: 1.0.0 → 2.0.0' )
        ->assertExitCode( 0 );
} );

test( 'security-only filter shows only security-flagged updates', function (): void {
    $this->checker->setPackages( [
        updatePackage( 'artisanpack-ui/core', '1.1.0' ),
        updatePackage( 'artisanpack-ui/security', '2.0.0' ),
    ] );

    $this->client->releases = [
        'artisanpack-ui/core'     => packagistRelease( '1.2.0', 'General improvements' ),
        'artisanpack-ui/security' => packagistRelease( '2.1.0', 'Fixed XSS vulnerability in sanitizer' ),
    ];

    $this->artisan( 'artisanpack:check-updates', ['--security-only' => true] )
        ->expectsOutputToContain( 'artisanpack-ui/security: 2.0.0 → 2.1.0' )
        ->doesntExpectOutputToContain( 'artisanpack-ui/core: 1.1.0' )
        ->expectsOutputToContain( '1 security-related update available' )
        ->assertExitCode( 0 );
} );

test( 'changelog flag renders release notes and source url', function (): void {
    $this->checker->setPackages( [
        updatePackage( 'artisanpack-ui/core', '1.1.0' ),
    ] );

    $this->client->releases = [
        'artisanpack-ui/core' => packagistRelease(
            '1.2.0',
            'Added new diagnostic command',
            'https://github.com/ArtisanPack-UI/core',
        ),
    ];

    $this->artisan( 'artisanpack:check-updates', ['--changelog' => true] )
        ->expectsOutputToContain( '- Added new diagnostic command' )
        ->expectsOutputToContain( 'Source: https://github.com/ArtisanPack-UI/core' )
        ->assertExitCode( 0 );
} );

test( 'json output emits structured report', function (): void {
    $this->checker->setPackages( [
        updatePackage( 'artisanpack-ui/core', '1.1.0' ),
        updatePackage( 'artisanpack-ui/security', '2.1.0' ),
    ] );

    $this->client->releases = [
        'artisanpack-ui/core'     => packagistRelease( '1.2.0' ),
        'artisanpack-ui/security' => packagistRelease( '2.1.0' ),
    ];

    $exitCode = Artisan::call( 'artisanpack:check-updates', ['--json' => true] );
    $output   = Artisan::output();

    expect( $exitCode )->toBe( 0 );

    $decoded = json_decode( trim( $output ), true );

    expect( $decoded )->toBeArray();
    expect( $decoded )->toHaveKeys( [
        'updates',
        'up_to_date',
        'errors',
        'filters',
        'summary',
    ] );
    expect( $decoded['summary']['updates_available'] )->toBe( 1 );
    expect( $decoded['summary']['total_installed'] )->toBe( 2 );
    expect( $decoded['up_to_date'] )->toHaveKey( 'artisanpack-ui/security' );
    expect( $decoded['updates'][0]['package'] )->toBe( 'artisanpack-ui/core' );
    expect( $decoded['updates'][0]['type'] )->toBe( 'minor' );
} );

test( 'summary distinguishes uncheckable packages from a clean slate', function (): void {
    $this->checker->setPackages( [
        updatePackage( 'artisanpack-ui/core', '1.2.0' ),
        updatePackage( 'artisanpack-ui/security', '2.0.0' ),
    ] );

    $this->client->releases = [
        'artisanpack-ui/core'     => packagistRelease( '1.2.0' ),
        'artisanpack-ui/security' => null,
    ];

    $this->artisan( 'artisanpack:check-updates' )
        ->expectsOutputToContain( 'No updates found for checked packages' )
        ->expectsOutputToContain( '1 package could not be checked' )
        ->doesntExpectOutputToContain( 'All packages are up to date' )
        ->assertExitCode( 0 );
} );

test( 'reports errors when packagist has no data for a package', function (): void {
    $this->checker->setPackages( [
        updatePackage( 'artisanpack-ui/core', '1.1.0' ),
    ] );

    $this->client->releases = [
        'artisanpack-ui/core' => null,
    ];

    $this->artisan( 'artisanpack:check-updates' )
        ->expectsOutputToContain( 'Errors:' )
        ->expectsOutputToContain( '✗ artisanpack-ui/core' )
        ->assertExitCode( 0 );
} );

test( 'reports cleanly when no artisanpack-ui packages are installed', function (): void {
    $this->checker->setPackages( [] );

    $this->artisan( 'artisanpack:check-updates' )
        ->expectsOutputToContain( 'All packages are up to date' )
        ->assertExitCode( 0 );
} );

test( 'patch updates are classified as patch', function (): void {
    $this->checker->setPackages( [
        updatePackage( 'artisanpack-ui/core', '1.1.0' ),
    ] );

    $this->client->releases = [
        'artisanpack-ui/core' => packagistRelease( '1.1.1' ),
    ];

    $this->artisan( 'artisanpack:check-updates' )
        ->expectsOutputToContain( 'artisanpack-ui/core: 1.1.0 → 1.1.1 (patch)' )
        ->assertExitCode( 0 );
} );
