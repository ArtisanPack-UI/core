<?php

use ArtisanPackUI\Core\Exceptions\ArtisanPackException;
use ArtisanPackUI\Core\Exceptions\ServiceException;
use Tests\Fixtures\Facades\CustomPackageFacade;
use Tests\Fixtures\Facades\TestFacade;
use Tests\Fixtures\TestFacadeService;

beforeEach( function (): void {
	TestFacade::clearResolvedInstance( 'test-facade-service' );
	CustomPackageFacade::clearResolvedInstance( 'custom-package-service' );
} );

test( 'resolveFacadeInstance returns the bound service', function (): void {
	$this->app->singleton( 'test-facade-service', fn (): TestFacadeService => new TestFacadeService() );

	expect( TestFacade::ping() )->toBe( 'pong' );
} );

test( 'resolveFacadeInstance throws a ServiceException when the service is not bound', function (): void {
	TestFacade::ping();
} )->throws(
	ServiceException::class,
	'Service [test-facade-service] is not bound in the container. ' .
	'Make sure the artisanpack-ui/core package is properly installed and configured.',
);

test( 'the thrown ServiceException carries the SERVICE_NOT_BOUND error code', function (): void {
	$caught = null;

	try {
		TestFacade::ping();
	} catch ( ServiceException $e ) {
		$caught = $e;
	}

	expect( $caught )->toBeInstanceOf( ServiceException::class )
		->and( $caught->getErrorCode() )->toBe( 'SERVICE_NOT_BOUND' )
		->and( $caught )->toBeInstanceOf( ArtisanPackException::class );
} );

test( 'subclasses surface their own package name in the error message', function (): void {
	CustomPackageFacade::getFacadeRoot();
} )->throws(
	ServiceException::class,
	'Service [custom-package-service] is not bound in the container. ' .
	'Make sure the artisanpack-ui/custom-package package is properly installed and configured.',
);

test( 'isAvailable reports true when the service is bound', function (): void {
	$this->app->singleton( 'test-facade-service', fn (): TestFacadeService => new TestFacadeService() );

	expect( TestFacade::isAvailable() )->toBeTrue();
} );

test( 'isAvailable reports false when the service is not bound', function (): void {
	expect( TestFacade::isAvailable() )->toBeFalse();
} );

test( 'isAvailable reports false when the application container is unavailable', function (): void {
	TestFacade::setFacadeApplication( null );

	expect( TestFacade::isAvailable() )->toBeFalse();
} );

test( 'tryGet returns the resolved service when it is bound', function (): void {
	$service = new TestFacadeService();
	$this->app->singleton( 'test-facade-service', fn (): TestFacadeService => $service );

	expect( TestFacade::tryGet() )->toBe( $service );
} );

test( 'tryGet returns null when the service is not bound', function (): void {
	expect( TestFacade::tryGet() )->toBeNull();
} );

test( 'tryGet returns null when the application container is unavailable', function (): void {
	TestFacade::setFacadeApplication( null );

	expect( TestFacade::tryGet() )->toBeNull();
} );

test( 'resolved instances are cached on repeat lookups', function (): void {
	$service = new TestFacadeService();
	$this->app->singleton( 'test-facade-service', fn (): TestFacadeService => $service );

	$first  = TestFacade::getFacadeRoot();
	$second = TestFacade::getFacadeRoot();

	expect( $first )->toBe( $service )
		->and( $second )->toBe( $service );
} );
