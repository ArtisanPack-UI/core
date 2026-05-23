<?php

// Nested fixture arrays cannot satisfy the per-line arrow alignment sniff.
// phpcs:disable ArtisanPackUI.Formatting.Alignment.ArrayItemNotAligned

use ArtisanPackUI\Core\Config\ConfigurationManager;
use ArtisanPackUI\Core\CoreServiceProvider;
use ArtisanPackUI\Core\Logging\LoggerFactory;
use ArtisanPackUI\Core\Testing\ArtisanPackTestCase;
use ArtisanPackUI\Core\Testing\Concerns\ArtisanPackAssertions;
use ArtisanPackUI\Core\Testing\Concerns\InteractsWithConfiguration;
use ArtisanPackUI\Core\Testing\Concerns\MocksArtisanPackServices;
use ArtisanPackUI\Core\View\BladeDirectiveRegistrar;
use Illuminate\Support\Facades\Facade;
use Mockery\MockInterface;
use Orchestra\Testbench\TestCase as Orchestra;
use PHPUnit\Framework\AssertionFailedError;
use Tests\Fixtures\Facades\TestFacade;
use Tests\Fixtures\TestFacadeService;
use Tests\Fixtures\Testing\StubArtisanPackTestCase;
use Tests\Fixtures\Testing\StubArtisanPackTestCaseWithProviders;

uses(
	ArtisanPackAssertions::class,
	InteractsWithConfiguration::class,
	MocksArtisanPackServices::class,
);

// ---------------------------------------------------------------------------
// ArtisanPackTestCase
// ---------------------------------------------------------------------------

test( 'ArtisanPackTestCase extends Orchestra Testbench', function (): void {
	expect( is_subclass_of( ArtisanPackTestCase::class, Orchestra::class ) )->toBeTrue();
} );

test( 'ArtisanPackTestCase composes the shared assertion and helper traits', function (): void {
	$traits = class_uses( ArtisanPackTestCase::class );

	expect( $traits )->toContain( ArtisanPackAssertions::class )
		->and( $traits )->toContain( InteractsWithConfiguration::class )
		->and( $traits )->toContain( MocksArtisanPackServices::class );
} );

test( 'getArtisanPackProviders includes the core service provider by default', function (): void {
	$stub = new StubArtisanPackTestCase( 'stub' );

	expect( $stub->callGetArtisanPackProviders() )->toBe( [ CoreServiceProvider::class ] );
} );

test( 'getAdditionalProviders returns an empty array by default', function (): void {
	$stub = new StubArtisanPackTestCase( 'stub' );

	expect( $stub->callGetAdditionalProviders() )->toBe( [] );
} );

test( 'getPackageProviders returns core providers when no additional providers are configured', function (): void {
	$stub = new StubArtisanPackTestCase( 'stub' );

	expect( $stub->callGetPackageProviders( $this->app ) )->toBe( [ CoreServiceProvider::class ] );
} );

test( 'getPackageProviders merges core providers before any additional providers', function (): void {
	$stub = new StubArtisanPackTestCaseWithProviders( 'stub' );

	expect( $stub->callGetPackageProviders( $this->app ) )->toBe( [
		CoreServiceProvider::class,
		'Tests\\Fixtures\\TestPackageServiceProvider',
	] );
} );

// ---------------------------------------------------------------------------
// ArtisanPackAssertions
// ---------------------------------------------------------------------------

test( 'assertPackageInstalled passes for a known composer package', function (): void {
	$this->assertPackageInstalled( 'artisanpack-ui/core' );
} );

test( 'assertPackageInstalled fails for a package that is not installed', function (): void {
	$this->assertPackageInstalled( 'artisanpack-ui/this-package-does-not-exist' );
} )->throws( AssertionFailedError::class, 'artisanpack-ui/this-package-does-not-exist' );

test( 'assertConfigEquals passes when the value matches', function (): void {
	config()->set( 'testing.assertion.value', 'expected' );

	$this->assertConfigEquals( 'testing.assertion.value', 'expected' );
} );

test( 'assertConfigEquals fails when the value does not match', function (): void {
	config()->set( 'testing.assertion.value', 'actual' );

	$this->assertConfigEquals( 'testing.assertion.value', 'expected' );
} )->throws( AssertionFailedError::class, 'testing.assertion.value' );

test( 'assertCommandExists passes for a registered Artisan command', function (): void {
	$this->assertCommandExists( 'artisanpack:install-packages' );
} );

test( 'assertCommandExists fails for an unknown command', function (): void {
	$this->assertCommandExists( 'artisanpack:command-does-not-exist' );
} )->throws( AssertionFailedError::class, 'artisanpack:command-does-not-exist' );

test( 'assertFacadeWorks passes when the facade resolves to its bound service', function (): void {
	$this->app->singleton( 'test-facade-service', fn (): TestFacadeService => new TestFacadeService() );
	TestFacade::clearResolvedInstance( 'test-facade-service' );

	$this->assertFacadeWorks( TestFacade::class );
} );

test( 'assertFacadeWorks fails when the facade class does not exist', function (): void {
	$this->assertFacadeWorks( 'App\\Facades\\NonExistentFacade' );
} )->throws( AssertionFailedError::class, 'App\\Facades\\NonExistentFacade' );

test( 'assertFacadeWorks fails when the class is not a Laravel facade', function (): void {
	$this->assertFacadeWorks( TestFacadeService::class );
} )->throws( AssertionFailedError::class, 'Illuminate\\Support\\Facades\\Facade' );

test( 'assertFacadeWorks fails when the underlying service cannot be resolved', function (): void {
	TestFacade::clearResolvedInstance( 'test-facade-service' );

	$this->assertFacadeWorks( TestFacade::class );
} )->throws( AssertionFailedError::class, 'test-facade-service' );

test( 'assertHelperExists passes for an existing global helper function', function (): void {
	$this->assertHelperExists( 'artisanpack_config' );
} );

test( 'assertHelperExists fails when the function is not defined', function (): void {
	$this->assertHelperExists( 'artisanpack_undefined_helper_function' );
} )->throws( AssertionFailedError::class, 'artisanpack_undefined_helper_function' );

// ---------------------------------------------------------------------------
// InteractsWithConfiguration
// ---------------------------------------------------------------------------

test( 'withConfig sets each configuration key it is given', function (): void {
	$this->withConfig( [
		'testing.with-config.alpha' => 'one',
		'testing.with-config.beta'  => 'two',
	] );

	expect( config( 'testing.with-config.alpha' ) )->toBe( 'one' )
		->and( config( 'testing.with-config.beta' ) )->toBe( 'two' );
} );

test( 'withConfig returns the current test instance for fluent chaining', function (): void {
	$result = $this->withConfig( [ 'testing.with-config.chain' => 'value' ] );

	expect( $result )->toBe( $this );
} );

test( 'withPackageConfig writes values beneath the artisanpack namespace', function (): void {
	$this->withPackageConfig( 'demo-package', [
		'enabled' => true,
		'nested'  => [
			'key' => 'value',
		],
	] );

	expect( config( 'artisanpack.demo-package.enabled' ) )->toBeTrue()
		->and( config( 'artisanpack.demo-package.nested.key' ) )->toBe( 'value' );
} );

test( 'withPackageConfig merges into existing package configuration', function (): void {
	config()->set( 'artisanpack.demo-package', [
		'existing' => 'kept',
		'nested'   => [
			'a' => 1,
			'b' => 2,
		],
	] );

	$this->withPackageConfig( 'demo-package', [
		'added'  => 'new',
		'nested' => [
			'b' => 20,
		],
	] );

	expect( config( 'artisanpack.demo-package.existing' ) )->toBe( 'kept' )
		->and( config( 'artisanpack.demo-package.added' ) )->toBe( 'new' )
		->and( config( 'artisanpack.demo-package.nested.a' ) )->toBe( 1 )
		->and( config( 'artisanpack.demo-package.nested.b' ) )->toBe( 20 );
} );

test( 'withPackageConfig returns the current test instance for fluent chaining', function (): void {
	$result = $this->withPackageConfig( 'demo-package', [ 'key' => 'value' ] );

	expect( $result )->toBe( $this );
} );

// ---------------------------------------------------------------------------
// MocksArtisanPackServices
// ---------------------------------------------------------------------------

test( 'swapArtisanPackService replaces the binding with the given instance', function (): void {
	$replacement = new TestFacadeService();

	$returned = $this->swapArtisanPackService( 'swap-test-binding', $replacement );

	expect( $returned )->toBe( $replacement )
		->and( $this->app->make( 'swap-test-binding' ) )->toBe( $replacement );
} );

test( 'mockArtisanPackService creates a Mockery double and binds it', function (): void {
	$mock = $this->mockArtisanPackService( TestFacadeService::class );

	expect( $mock )->toBeInstanceOf( MockInterface::class )
		->and( $this->app->make( TestFacadeService::class ) )->toBe( $mock );
} );

test( 'mockArtisanPackService invokes the expectations callback with the mock', function (): void {
	$captured = null;

	$mock = $this->mockArtisanPackService(
		TestFacadeService::class,
		function ( MockInterface $mockInstance ) use ( & $captured ): void {
			$captured = $mockInstance;
		},
	);

	expect( $captured )->toBe( $mock );
} );

test( 'swapConfigurationManager replaces the bound ConfigurationManager', function (): void {
	$replacement = new ConfigurationManager( config() );

	$returned = $this->swapConfigurationManager( $replacement );

	expect( $returned )->toBe( $replacement )
		->and( $this->app->make( ConfigurationManager::class ) )->toBe( $replacement );
} );

test( 'swapLoggerFactory replaces the bound LoggerFactory', function (): void {
	$replacement = $this->app->make( LoggerFactory::class );

	$returned = $this->swapLoggerFactory( $replacement );

	expect( $returned )->toBe( $replacement )
		->and( $this->app->make( LoggerFactory::class ) )->toBe( $replacement );
} );

test( 'swapBladeDirectiveRegistrar replaces the bound BladeDirectiveRegistrar', function (): void {
	$replacement = $this->app->make( BladeDirectiveRegistrar::class );

	$returned = $this->swapBladeDirectiveRegistrar( $replacement );

	expect( $returned )->toBe( $replacement )
		->and( $this->app->make( BladeDirectiveRegistrar::class ) )->toBe( $replacement );
} );

test( 'Facade import is referenced to keep the abstract test case discoverable in IDEs', function (): void {
	expect( class_exists( Facade::class ) )->toBeTrue();
} );
