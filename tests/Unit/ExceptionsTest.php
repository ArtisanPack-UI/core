<?php

use ArtisanPackUI\Core\Exceptions\ArtisanPackException;
use ArtisanPackUI\Core\Exceptions\ConfigurationException;
use ArtisanPackUI\Core\Exceptions\DependencyException;
use ArtisanPackUI\Core\Exceptions\InstallationException;
use ArtisanPackUI\Core\Exceptions\ServiceException;
use ArtisanPackUI\Core\Exceptions\ValidationException;

test( 'base exception extends the native exception class', function (): void {
	$exception = new ArtisanPackException( 'Something went wrong' );

	expect( $exception )->toBeInstanceOf( Exception::class )
		->and( $exception->getMessage() )->toBe( 'Something went wrong' );
} );

test( 'base exception defaults to the core package and a null error code', function (): void {
	$exception = new ArtisanPackException();

	expect( $exception->getPackage() )->toBe( 'core' )
		->and( $exception->getErrorCode() )->toBeNull();
} );

test( 'base exception retains the provided code, previous, and error code', function (): void {
	$previous  = new RuntimeException( 'Underlying failure' );
	$exception = new ArtisanPackException( 'Failed', 500, $previous, 'CORE_CONFIG_MISSING' );

	expect( $exception->getCode() )->toBe( 500 )
		->and( $exception->getPrevious() )->toBe( $previous )
		->and( $exception->getErrorCode() )->toBe( 'CORE_CONFIG_MISSING' );
} );

test( 'base exception exposes structured context for logging', function (): void {
	$exception = new ArtisanPackException( 'Failed', 0, null, 'CORE_CONFIG_MISSING' );

	expect( $exception->context() )->toBe( [
		'package'    => 'core',
		'error_code' => 'CORE_CONFIG_MISSING',
	] );
} );

test( 'specific exceptions extend the base ArtisanPack exception', function ( string $exceptionClass ): void {
	$exception = new $exceptionClass( 'Failure' );

	expect( $exception )->toBeInstanceOf( ArtisanPackException::class )
		->and( $exception )->toBeInstanceOf( Exception::class );
} )->with( [
	'configuration' => ConfigurationException::class,
	'validation'    => ValidationException::class,
	'service'       => ServiceException::class,
	'installation'  => InstallationException::class,
	'dependency'    => DependencyException::class,
] );

test( 'specific exceptions can be caught as the base exception', function (): void {
	$caught = null;

	try {
		throw new ValidationException( 'Invalid data', 422, null, 'CORE_VALIDATION_FAILED' );
	} catch ( ArtisanPackException $e ) {
		$caught = $e;
	}

	expect( $caught )->toBeInstanceOf( ValidationException::class )
		->and( $caught->getPackage() )->toBe( 'core' )
		->and( $caught->getErrorCode() )->toBe( 'CORE_VALIDATION_FAILED' );
} );
