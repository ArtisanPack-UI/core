---
title: Testing utilities
---

# Testing utilities

ArtisanPack UI Core ships a shared Orchestra Testbench base class and three concerns that every sibling package can lean on so tests across the ecosystem read consistently. These utilities live under `ArtisanPackUI\Core\Testing\` and are autoloaded via the package's PSR-4 mapping.

## `ArtisanPackTestCase`

Abstract base class that registers `CoreServiceProvider` with Orchestra Testbench and pulls in the three concerns below. Extend it from your own package's test case:

```php
<?php

namespace ArtisanPackUI\MediaLibrary\Tests;

use ArtisanPackUI\Core\Testing\ArtisanPackTestCase;
use ArtisanPackUI\MediaLibrary\MediaLibraryServiceProvider;

abstract class TestCase extends ArtisanPackTestCase
{
    /**
     * Package-specific service providers to register AFTER core.
     */
    protected function getAdditionalProviders(): array
    {
        return [
            MediaLibraryServiceProvider::class,
        ];
    }
}
```

The base class:

- Registers `CoreServiceProvider` first via `getArtisanPackProviders()`.
- Merges your `getAdditionalProviders()` after core via `getPackageProviders()` so package providers can depend on core's container bindings.
- Mixes in `ArtisanPackAssertions`, `InteractsWithConfiguration`, and `MocksArtisanPackServices`.

Override `getArtisanPackProviders()` only if you need to swap core for a custom provider — the default is sufficient for the vast majority of test suites.

## `ArtisanPackAssertions`

Trait that adds assertion helpers that express expectations in ArtisanPack UI terms rather than generic primitives. The trait delegates to the host class's PHPUnit assertions, so it works against any test case extending `PHPUnit\Framework\TestCase`.

```php
// Composer package is installed
$this->assertPackageInstalled( 'artisanpack-ui/media-library' );

// Configuration key matches an expected value
$this->assertConfigEquals( 'artisanpack.media-library.enable_webp', true );

// Artisan command is registered with the application
$this->assertCommandExists( 'artisanpack:diagnose' );

// Facade exists, extends Laravel's Facade, and resolves a non-null root
$this->assertFacadeWorks( \ArtisanPackUI\Core\Facades\ArtisanPackLog::class );

// Global helper function is defined
$this->assertHelperExists( 'artisanpack_config' );
```

Each assertion fails with a descriptive message tied to the concept being verified ( "Failed asserting that composer package [artisanpack-ui/media-library] is installed" ) rather than the underlying primitive.

## `InteractsWithConfiguration`

Trait that adds chainable helpers for seeding configuration values inside an Orchestra Testbench application.

```php
// Seed top-level config values (suitable for non-artisanpack keys too)
$this->withConfig( [
    'app.timezone'          => 'America/Los_Angeles',
    'cache.default'         => 'array',
    'queue.default'         => 'sync',
] );

// Seed config for a single ArtisanPack UI package (recursive merge — test values win)
$this->withPackageConfig( 'media-library', [
    'enable_webp'  => true,
    'enable_avif'  => false,
    'default_disk' => 'public',
] );

// Chain freely
$this
    ->withConfig( [ 'app.env' => 'testing' ] )
    ->withPackageConfig( 'security', [ 'csp.enabled' => false ] )
    ->withPackageConfig( 'media-library', [ 'enable_webp' => true ] );
```

`withPackageConfig()` knows about the shared `artisanpack.<package>.<key>` namespace and resolves the right key path without the caller having to remember the prefix. The recursive merge mirrors `ConfigurationManager::merge()` so test overrides win over previously-registered defaults.

## `MocksArtisanPackServices`

Trait that adds container-swap helpers for replacing common ArtisanPack UI services with test doubles.

```php
use ArtisanPackUI\Core\Config\ConfigurationManager;
use ArtisanPackUI\Core\Logging\LoggerFactory;
use ArtisanPackUI\Core\View\BladeDirectiveRegistrar;
use Mockery\MockInterface;

// Generic — swap any container binding
$this->swapArtisanPackService( SomeService::class, new FakeSomeService );

// Generic — mock any container binding
$mock = $this->mockArtisanPackService( SomeService::class, function ( MockInterface $mock ): void {
    $mock->shouldReceive( 'doThing' )->andReturn( 'result' );
} );

// Dedicated shortcuts for the services packages most often need to replace
$this->swapConfigurationManager( $fakeManager );
$this->swapLoggerFactory( $fakeFactory );
$this->swapBladeDirectiveRegistrar( $fakeRegistrar );
```

`mockArtisanPackService()` requires `mockery/mockery` — which is bundled with Orchestra Testbench, so the helper works out of the box in standard package test setups. Without Mockery installed it throws a descriptive `RuntimeException` rather than a confusing class-not-found failure.

## Full example

```php
<?php

namespace ArtisanPackUI\MediaLibrary\Tests\Feature;

use ArtisanPackUI\Core\Logging\LoggerFactory;
use ArtisanPackUI\MediaLibrary\Tests\TestCase;

class MediaUploadTest extends TestCase
{
    public function test_upload_logs_an_audit_entry(): void
    {
        $this->withPackageConfig( 'media-library', [
            'enable_webp' => true,
        ] );

        $this->assertPackageInstalled( 'artisanpack-ui/media-library' );
        $this->assertCommandExists( 'artisanpack:diagnose' );
        $this->assertHelperExists( 'artisanpack_config' );
        $this->assertConfigEquals( 'artisanpack.media-library.enable_webp', true );

        // ... exercise the upload flow ...

        $this->assertFacadeWorks( \ArtisanPackUI\Core\Facades\ArtisanPackLog::class );
    }
}
```
