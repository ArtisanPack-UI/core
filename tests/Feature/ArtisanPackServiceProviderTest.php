<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Tests\Fixtures\MinimalServiceProvider;
use Tests\Fixtures\TestPackageServiceProvider;
use Tests\Fixtures\TestService;

test( 'register calls the abstract registerPackageBindings hook', function (): void {
	$provider = new TestPackageServiceProvider( $this->app );

	$provider->register();

	expect( $provider->bindingsRegistered )->toBeTrue();
} );

test( 'register binds keyed singletons via the container', function (): void {
	$provider = new TestPackageServiceProvider( $this->app );

	$provider->register();

	expect( $this->app->bound( 'artisanpack-test-binding' ) )->toBeTrue();

	$first  = $this->app->make( 'artisanpack-test-binding' );
	$second = $this->app->make( 'artisanpack-test-binding' );

	expect( $first )->toBeInstanceOf( TestService::class );
	expect( $first )->toBe( $second );
} );

test( 'register binds numerically indexed singletons as self-bound', function (): void {
	$provider = new TestPackageServiceProvider( $this->app );

	$provider->register();

	expect( $this->app->bound( TestService::class ) )->toBeTrue();

	$first  = $this->app->make( TestService::class );
	$second = $this->app->make( TestService::class );

	expect( $first )->toBe( $second );
} );

test( 'register merges the package configuration', function (): void {
	$provider = new TestPackageServiceProvider( $this->app );

	$provider->register();

	expect( config( 'artisanpack-test.foo' ) )->toBe( 'bar' );
	expect( config( 'artisanpack-test.baz' ) )->toBe( 'qux' );
} );

test( 'user configuration takes precedence over package defaults', function (): void {
	config()->set( 'artisanpack-test', [ 'foo' => 'overridden' ] );

	$provider = new TestPackageServiceProvider( $this->app );
	$provider->register();

	expect( config( 'artisanpack-test.foo' ) )->toBe( 'overridden' );
} );

test( 'boot publishes the package configuration when running in console', function (): void {
	$provider = new TestPackageServiceProvider( $this->app );
	$provider->register();
	$provider->boot();

	$paths = ServiceProvider::pathsToPublish(
		TestPackageServiceProvider::class,
		'artisanpack-test-config',
	);

	expect( $paths )->not->toBeEmpty();
} );

test( 'boot registers package commands when running in console', function (): void {
	$provider = new TestPackageServiceProvider( $this->app );
	$provider->register();
	$provider->boot();

	expect( Artisan::all() )->toHaveKey( 'artisanpack:test-command' );
} );

test( 'boot loads the package views under the configured namespace', function (): void {
	$provider = new TestPackageServiceProvider( $this->app );
	$provider->register();
	$provider->boot();

	expect( View::exists( 'artisanpack-test::sample' ) )->toBeTrue();
} );

test( 'boot loads the package routes', function (): void {
	$provider = new TestPackageServiceProvider( $this->app );
	$provider->register();
	$provider->boot();

	$registered = collect( Route::getRoutes()->getRoutes() )
		->contains( static fn ( $route ): bool => 'artisanpack-test-route' === $route->uri() );

	expect( $registered )->toBeTrue();
} );

test( 'boot calls the bootPackage hook', function (): void {
	$provider = new TestPackageServiceProvider( $this->app );
	$provider->register();
	$provider->boot();

	expect( $provider->packageBooted )->toBeTrue();
} );

test( 'a minimal provider boots without configuration and does not error', function (): void {
	$provider = new MinimalServiceProvider( $this->app );

	$provider->register();
	$provider->boot();

	expect( $provider->bindingsRegistered )->toBeTrue();
} );

test( 'a minimal provider does not merge any configuration', function (): void {
	$provider = new MinimalServiceProvider( $this->app );

	$provider->register();

	expect( config( 'artisanpack-test' ) )->toBeNull();
} );
