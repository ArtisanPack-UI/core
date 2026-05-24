<?php

use ArtisanPackUI\Core\Commands\MakePackageCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

function makePackageTargetPath( string $package ): string
{
    return sprintf(
        '%s/artisanpack-make-package-%s/%s',
        sys_get_temp_dir(),
        bin2hex( random_bytes( 4 ) ),
        $package,
    );
}

beforeEach( function (): void {
    $this->targetPath = makePackageTargetPath( 'new-package' );
} );

afterEach( function (): void {
    if ( isset( $this->targetPath ) && File::isDirectory( dirname( $this->targetPath ) ) ) {
        File::deleteDirectory( dirname( $this->targetPath ) );
    }
} );

test( 'command is registered', function (): void {
    $commands = Artisan::all();

    expect( $commands )->toHaveKey( 'artisanpack:make-package' );
    expect( $commands['artisanpack:make-package'] )->toBeInstanceOf( MakePackageCommand::class );
} );

test( 'command has the expected signature options', function (): void {
    $command    = app( MakePackageCommand::class );
    $definition = $command->getDefinition();

    expect( $definition->hasArgument( 'name' ) )->toBeTrue();
    expect( $definition->hasOption( 'path' ) )->toBeTrue();
    expect( $definition->hasOption( 'with-livewire' ) )->toBeTrue();
    expect( $definition->hasOption( 'with-views' ) )->toBeTrue();
    expect( $definition->hasOption( 'with-routes' ) )->toBeTrue();
    expect( $definition->hasOption( 'with-migrations' ) )->toBeTrue();
    expect( $definition->hasOption( 'with-commands' ) )->toBeTrue();
    expect( $definition->hasOption( 'force' ) )->toBeTrue();
} );

test( 'command rejects invalid package names', function (): void {
    $exitCode = Artisan::call( 'artisanpack:make-package', [
        'name'   => 'invalid-name-no-slash',
        '--path' => $this->targetPath,
    ] );

    expect( $exitCode )->toBe( 1 );
    expect( File::isDirectory( $this->targetPath ) )->toBeFalse();
} );

test( 'command scaffolds the base package files', function (): void {
    $exitCode = Artisan::call( 'artisanpack:make-package', [
        'name'   => 'artisanpack-ui/new-package',
        '--path' => $this->targetPath,
    ] );

    expect( $exitCode )->toBe( 0 );
    expect( File::exists( $this->targetPath . '/composer.json' ) )->toBeTrue();
    expect( File::exists( $this->targetPath . '/README.md' ) )->toBeTrue();
    expect( File::exists( $this->targetPath . '/CHANGELOG.md' ) )->toBeTrue();
    expect( File::exists( $this->targetPath . '/CONTRIBUTING.md' ) )->toBeTrue();
    expect( File::exists( $this->targetPath . '/LICENSE' ) )->toBeTrue();
    expect( File::exists( $this->targetPath . '/.gitignore' ) )->toBeTrue();
    expect( File::exists( $this->targetPath . '/phpunit.xml' ) )->toBeTrue();
    expect( File::exists( $this->targetPath . '/.gitlab/issue_templates/Default.md' ) )->toBeTrue();
    expect( File::exists( $this->targetPath . '/src/NewPackageServiceProvider.php' ) )->toBeTrue();
    expect( File::exists( $this->targetPath . '/src/NewPackage.php' ) )->toBeTrue();
    expect( File::exists( $this->targetPath . '/src/Facades/NewPackage.php' ) )->toBeTrue();
    expect( File::exists( $this->targetPath . '/src/helpers.php' ) )->toBeTrue();
    expect( File::exists( $this->targetPath . '/config/new-package.php' ) )->toBeTrue();
    expect( File::exists( $this->targetPath . '/tests/TestCase.php' ) )->toBeTrue();
    expect( File::exists( $this->targetPath . '/tests/Pest.php' ) )->toBeTrue();
    expect( File::exists( $this->targetPath . '/tests/Feature/ExampleTest.php' ) )->toBeTrue();
    expect( File::exists( $this->targetPath . '/tests/Unit/ExampleTest.php' ) )->toBeTrue();
} );

test( 'scaffolded composer.json contains the resolved namespace and provider', function (): void {
    Artisan::call( 'artisanpack:make-package', [
        'name'   => 'artisanpack-ui/new-package',
        '--path' => $this->targetPath,
    ] );

    $composer = json_decode( File::get( $this->targetPath . '/composer.json' ), true );

    expect( $composer['name'] )->toBe( 'artisanpack-ui/new-package' );
    expect( $composer['autoload']['psr-4'] )->toHaveKey( 'ArtisanPackUI\\NewPackage\\' );
    expect( $composer['extra']['laravel']['providers'] )->toContain( 'ArtisanPackUI\\NewPackage\\NewPackageServiceProvider' );
} );

test( 'scaffolded service provider uses the resolved namespace', function (): void {
    Artisan::call( 'artisanpack:make-package', [
        'name'   => 'artisanpack-ui/new-package',
        '--path' => $this->targetPath,
    ] );

    $contents = File::get( $this->targetPath . '/src/NewPackageServiceProvider.php' );

    expect( $contents )->toContain( 'namespace ArtisanPackUI\\NewPackage;' );
    expect( $contents )->toContain( 'class NewPackageServiceProvider extends ArtisanPackServiceProvider' );
    expect( $contents )->toContain( 'use ArtisanPackUI\\Core\\ArtisanPackServiceProvider;' );
} );

test( 'optional flags scaffold their respective directories', function (): void {
    Artisan::call( 'artisanpack:make-package', [
        'name'              => 'artisanpack-ui/new-package',
        '--path'            => $this->targetPath,
        '--with-views'      => true,
        '--with-routes'     => true,
        '--with-migrations' => true,
        '--with-commands'   => true,
        '--with-livewire'   => true,
    ] );

    expect( File::exists( $this->targetPath . '/resources/views/example.blade.php' ) )->toBeTrue();
    expect( File::exists( $this->targetPath . '/routes/web.php' ) )->toBeTrue();
    expect( File::isDirectory( $this->targetPath . '/database/migrations' ) )->toBeTrue();
    expect( File::exists( $this->targetPath . '/src/Console/Commands/NewPackageExampleCommand.php' ) )->toBeTrue();
    expect( File::exists( $this->targetPath . '/src/Livewire/NewPackageExample.php' ) )->toBeTrue();
    expect( File::exists( $this->targetPath . '/resources/views/livewire/example.blade.php' ) )->toBeTrue();
} );

test( 'command refuses to overwrite a non-empty directory without force', function (): void {
    File::ensureDirectoryExists( $this->targetPath );
    File::put( $this->targetPath . '/existing.txt', 'do not overwrite' );

    $exitCode = Artisan::call( 'artisanpack:make-package', [
        'name'   => 'artisanpack-ui/new-package',
        '--path' => $this->targetPath,
    ] );

    expect( $exitCode )->toBe( 1 );
    expect( File::get( $this->targetPath . '/existing.txt' ) )->toBe( 'do not overwrite' );
} );

test( 'force flag allows scaffolding into an existing directory', function (): void {
    File::ensureDirectoryExists( $this->targetPath );
    File::put( $this->targetPath . '/existing.txt', 'preexisting' );

    $exitCode = Artisan::call( 'artisanpack:make-package', [
        'name'    => 'artisanpack-ui/new-package',
        '--path'  => $this->targetPath,
        '--force' => true,
    ] );

    expect( $exitCode )->toBe( 0 );
    expect( File::exists( $this->targetPath . '/composer.json' ) )->toBeTrue();
} );
