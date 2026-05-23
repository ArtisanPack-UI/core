<?php

use ArtisanPackUI\Core\Commands\CheckCompatibilityCommand;
use ArtisanPackUI\Core\Compatibility\CompatibilityChecker;
use Illuminate\Support\Facades\Artisan;

/**
 * Build a fake composer.lock-style package entry.
 *
 * @param array<string, string> $require
 *
 * @return array<string, mixed>
 */
function compatPackage( string $name, string $version, array $require = [] ): array
{
    return [
        'name'    => $name,
        'version' => $version,
        'require' => $require,
    ];
}

beforeEach( function (): void {
    $this->checker = new CompatibilityChecker( $this->app );
    $this->checker->setPhpVersion( '8.3.0' )
        ->setLaravelVersion( '12.0.0' )
        ->setLoadedExtensions( ['mbstring', 'json'] );

    $this->app->instance( CompatibilityChecker::class, $this->checker );
} );

test( 'command is registered', function (): void {
    $commands = Artisan::all();

    expect( $commands )->toHaveKey( 'artisanpack:check-compatibility' );
    expect( $commands['artisanpack:check-compatibility'] )->toBeInstanceOf( CheckCompatibilityCommand::class );
} );

test( 'command exposes documented options', function (): void {
    $definition = (new CheckCompatibilityCommand)->getDefinition();

    expect( $definition->hasOption( 'json' ) )->toBeTrue();
    expect( $definition->hasOption( 'strict' ) )->toBeTrue();
    expect( $definition->hasOption( 'package' ) )->toBeTrue();
} );

test( 'reports installed packages and clean summary when all requirements pass', function (): void {
    $this->checker->setPackages( [
        compatPackage( 'artisanpack-ui/core', '1.2.0', [
            'php'                => '^8.2',
            'illuminate/support' => '^11.0|^12.0|^13.0',
        ] ),
        compatPackage( 'artisanpack-ui/accessibility', '1.0.0', [
            'php'                    => '^8.2',
            'artisanpack-ui/core'    => '^1.0',
        ] ),
    ] );

    $this->artisan( 'artisanpack:check-compatibility' )
        ->expectsOutputToContain( 'ArtisanPack UI Compatibility Check' )
        ->expectsOutputToContain( 'artisanpack-ui/core: 1.2.0' )
        ->expectsOutputToContain( 'artisanpack-ui/accessibility: 1.0.0' )
        ->expectsOutputToContain( 'Issues Found: none' )
        ->assertExitCode( 0 );
} );

test( 'flags unsatisfied inter-package dependency and exits non-zero', function (): void {
    $this->checker->setPackages( [
        compatPackage( 'artisanpack-ui/core', '1.2.0' ),
        compatPackage( 'artisanpack-ui/media-library', '1.0.0', [
            'artisanpack-ui/security' => '^1.0',
        ] ),
        compatPackage( 'artisanpack-ui/security', '2.0.0' ),
    ] );

    $this->artisan( 'artisanpack:check-compatibility' )
        ->expectsOutputToContain( 'artisanpack-ui/media-library requires artisanpack-ui/security ^1.0, you have 2.0.0' )
        ->expectsOutputToContain( 'Recommendations:' )
        ->expectsOutputToContain( '1 error' )
        ->assertExitCode( 1 );
} );

test( 'warns when a required artisanpack-ui dependency is not installed', function (): void {
    $this->checker->setPackages( [
        compatPackage( 'artisanpack-ui/core', '1.2.0' ),
        compatPackage( 'artisanpack-ui/media-library', '1.0.0', [
            'artisanpack-ui/security' => '^1.0',
        ] ),
    ] );

    $this->artisan( 'artisanpack:check-compatibility' )
        ->expectsOutputToContain( 'requires artisanpack-ui/security ^1.0 but it is not installed' )
        ->expectsOutputToContain( '1 warning' )
        ->assertExitCode( 0 );
} );

test( 'strict flag turns warnings into a non-zero exit code', function (): void {
    $this->checker->setPackages( [
        compatPackage( 'artisanpack-ui/core', '1.2.0' ),
        compatPackage( 'artisanpack-ui/media-library', '1.0.0', [
            'artisanpack-ui/security' => '^1.0',
        ] ),
    ] );

    $this->artisan( 'artisanpack:check-compatibility', ['--strict' => true] )
        ->expectsOutputToContain( '1 warning' )
        ->assertExitCode( 1 );
} );

test( 'detects unsatisfied PHP version constraints', function (): void {
    $this->checker->setPhpVersion( '8.1.0' );
    $this->checker->setPackages( [
        compatPackage( 'artisanpack-ui/core', '1.2.0', [
            'php' => '^8.2',
        ] ),
    ] );

    $this->artisan( 'artisanpack:check-compatibility' )
        ->expectsOutputToContain( 'requires php ^8.2 but found 8.1.0' )
        ->assertExitCode( 1 );
} );

test( 'detects unsatisfied Laravel version constraints', function (): void {
    $this->checker->setLaravelVersion( '10.0.0' );
    $this->checker->setPackages( [
        compatPackage( 'artisanpack-ui/core', '1.2.0', [
            'illuminate/support' => '^11.0|^12.0|^13.0',
        ] ),
    ] );

    $this->artisan( 'artisanpack:check-compatibility' )
        ->expectsOutputToContain( 'requires illuminate/support ^11.0|^12.0|^13.0 but found 10.0.0' )
        ->assertExitCode( 1 );
} );

test( 'detects missing PHP extensions', function (): void {
    $this->checker->setLoadedExtensions( ['mbstring'] );
    $this->checker->setPackages( [
        compatPackage( 'artisanpack-ui/core', '1.2.0', [
            'ext-gd' => '*',
        ] ),
    ] );

    $this->artisan( 'artisanpack:check-compatibility' )
        ->expectsOutputToContain( 'requires PHP extension gd' )
        ->assertExitCode( 1 );
} );

test( 'json output emits structured report', function (): void {
    $this->checker->setPackages( [
        compatPackage( 'artisanpack-ui/core', '1.2.0', [
            'php' => '^8.2',
        ] ),
    ] );

    $exitCode = Artisan::call( 'artisanpack:check-compatibility', ['--json' => true] );
    $output   = Artisan::output();

    expect( $exitCode )->toBe( 0 );

    $decoded = json_decode( trim( $output ), true );

    expect( $decoded )->toBeArray();
    expect( $decoded )->toHaveKeys( [
        'php_version',
        'laravel_version',
        'installed_packages',
        'dependency_results',
        'php_results',
        'laravel_results',
        'extension_results',
        'recommendations',
        'summary',
    ] );
    expect( $decoded['installed_packages'] )->toHaveKey( 'artisanpack-ui/core' );
    expect( $decoded['summary']['errors'] )->toBe( 0 );
} );

test( 'package option restricts analysis to the given package', function (): void {
    $this->checker->setPackages( [
        compatPackage( 'artisanpack-ui/core', '1.2.0', [
            'php' => '^8.2',
        ] ),
        compatPackage( 'artisanpack-ui/security', '2.0.0', [
            'php' => '^9.99',
        ] ),
    ] );

    $this->artisan( 'artisanpack:check-compatibility', ['--package' => 'artisanpack-ui/core'] )
        ->doesntExpectOutputToContain( 'artisanpack-ui/security requires php' )
        ->expectsOutputToContain( 'Issues Found: none' )
        ->assertExitCode( 0 );
} );

test( 'unknown package option fails fast with explicit error', function (): void {
    $this->checker->setPackages( [
        compatPackage( 'artisanpack-ui/core', '1.2.0' ),
    ] );

    $this->artisan( 'artisanpack:check-compatibility', ['--package' => 'artisanpack-ui/nope'] )
        ->expectsOutputToContain( "Package 'artisanpack-ui/nope' is not an installed ArtisanPack UI package." )
        ->assertExitCode( 1 );
} );

test( 'unknown package with --json emits a JSON error payload', function (): void {
    $this->checker->setPackages( [
        compatPackage( 'artisanpack-ui/core', '1.2.0' ),
    ] );

    $exitCode = Artisan::call( 'artisanpack:check-compatibility', [
        '--package' => 'artisanpack-ui/nope',
        '--json'    => true,
    ] );
    $output = Artisan::output();

    expect( $exitCode )->toBe( 1 );

    $decoded = json_decode( trim( $output ), true );

    expect( $decoded )->toBeArray();
    expect( $decoded )->toHaveKey( 'error' );
    expect( $decoded['error'] )->toContain( 'artisanpack-ui/nope' );
} );

test( 'reports cleanly when no artisanpack-ui packages are installed', function (): void {
    $this->checker->setPackages( [] );

    $this->artisan( 'artisanpack:check-compatibility' )
        ->expectsOutputToContain( '(no ArtisanPack UI packages installed)' )
        ->expectsOutputToContain( 'Issues Found: none' )
        ->assertExitCode( 0 );
} );
