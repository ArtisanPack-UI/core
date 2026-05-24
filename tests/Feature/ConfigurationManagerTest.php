<?php

// Nested fixture arrays cannot satisfy the per-line arrow alignment sniff.
// phpcs:disable ArtisanPackUI.Formatting.Alignment.ArrayItemNotAligned

use ArtisanPackUI\Core\Config\ConfigurationManager;
use ArtisanPackUI\Core\Config\ConfigurationSchema;
use ArtisanPackUI\Core\Config\ValidationResult;
use ArtisanPackUI\Core\Facades\ArtisanPackConfig;

beforeEach( function (): void {
	config()->set( 'artisanpack', [
		'core'          => [
			'toast_duration' => 5,
		],
		'media-library' => [
			'max_file_size' => 10240,
			'disk'          => 'public',
		],
	] );

	$this->manager = app( ConfigurationManager::class );
} );

test( 'the manager is registered as a singleton', function (): void {
	expect( app( ConfigurationManager::class ) )->toBe( app( ConfigurationManager::class ) );
} );

test( 'get returns a configured value', function (): void {
	expect( $this->manager->get( 'media-library', 'max_file_size' ) )->toBe( 10240 );
} );

test( 'get returns the default when the key is missing', function (): void {
	expect( $this->manager->get( 'media-library', 'missing', 'fallback' ) )->toBe( 'fallback' );
} );

test( 'set stores a value at runtime', function (): void {
	$this->manager->set( 'media-library', 'max_file_size', 20480 );

	expect( $this->manager->get( 'media-library', 'max_file_size' ) )->toBe( 20480 );
	expect( config( 'artisanpack.media-library.max_file_size' ) )->toBe( 20480 );
} );

test( 'set can create nested keys', function (): void {
	$this->manager->set( 'media-library', 'thumbnails.width', 150 );

	expect( $this->manager->get( 'media-library', 'thumbnails.width' ) )->toBe( 150 );
} );

test( 'has reports whether a key exists', function (): void {
	expect( $this->manager->has( 'media-library', 'disk' ) )->toBeTrue();
	expect( $this->manager->has( 'media-library', 'missing' ) )->toBeFalse();
} );

test( 'package returns the entire package configuration', function (): void {
	expect( $this->manager->package( 'media-library' ) )->toBe( [
		'max_file_size' => 10240,
		'disk'          => 'public',
	] );
} );

test( 'package returns an empty array for an unknown package', function (): void {
	expect( $this->manager->package( 'does-not-exist' ) )->toBe( [] );
} );

test( 'all returns the configuration for every package', function (): void {
	expect( $this->manager->all() )->toHaveKeys( [ 'core', 'media-library' ] );
} );

test( 'merge keeps existing user values over supplied defaults', function (): void {
	$merged = $this->manager->merge( 'media-library', [
		'max_file_size' => 5120,
		'enable_webp'   => true,
	] );

	expect( $merged['max_file_size'] )->toBe( 10240 );
	expect( $merged['enable_webp'] )->toBeTrue();
	expect( $this->manager->get( 'media-library', 'enable_webp' ) )->toBeTrue();
	expect( $this->manager->get( 'media-library', 'max_file_size' ) )->toBe( 10240 );
} );

test( 'merge recurses into nested arrays', function (): void {
	config()->set( 'artisanpack.media-library.sizes', [
		'thumb' => [
			'width' => 150,
		],
	] );

	$merged = $this->manager->merge( 'media-library', [
		'sizes' => [
			'thumb'  => [
				'width'  => 100,
				'height' => 100,
			],
			'medium' => [
				'width' => 600,
			],
		],
	] );

	expect( $merged['sizes']['thumb'] )->toBe( [
		'width'  => 150,
		'height' => 100,
	] );
	expect( $merged['sizes']['medium'] )->toBe( [
		'width' => 600,
	] );
} );

test( 'validate passes when no schema is registered', function (): void {
	$result = $this->manager->validate( 'media-library', [
		'max_file_size' => 'nonsense',
	] );

	expect( $result )->toBeInstanceOf( ValidationResult::class );
	expect( $result->passes() )->toBeTrue();
} );

test( 'validate fails when a value violates the registered schema', function (): void {
	$this->manager->registerSchema( 'media-library', [
		'max_file_size' => [
			'type'     => 'integer',
			'required' => true,
		],
		'disk'          => [
			'type' => 'string',
			'in'   => [
				'public',
				's3',
			],
		],
	] );

	$result = $this->manager->validate( 'media-library', [
		'max_file_size' => 'not-an-int',
		'disk'          => 'ftp',
	] );

	expect( $result->fails() )->toBeTrue();
	expect( $result->hasErrors() )->toBeTrue();
	expect( $result->errors( 'max_file_size' ) )->toHaveCount( 1 );
	expect( $result->errors( 'disk' ) )->toHaveCount( 1 );
	expect( $result->errors() )->toHaveCount( 2 );
} );

test( 'validate flags missing required keys', function (): void {
	$this->manager->registerSchema( 'media-library', [
		'max_file_size' => [
			'type'     => 'integer',
			'required' => true,
		],
	] );

	$result = $this->manager->validate( 'media-library', [] );

	expect( $result->fails() )->toBeTrue();
	expect( $result->errors( 'max_file_size' ) )->toHaveCount( 1 );
} );

test( 'validate passes for a valid configuration', function (): void {
	$this->manager->registerSchema( 'media-library', [
		'max_file_size' => [
			'type'     => 'integer',
			'required' => true,
		],
		'disk'          => [
			'type' => 'string',
			'in'   => [
				'public',
				's3',
			],
		],
	] );

	$result = $this->manager->validate( 'media-library', [
		'max_file_size' => 10240,
		'disk'          => 'public',
	] );

	expect( $result->passes() )->toBeTrue();
	expect( $result->errors() )->toBe( [] );
} );

test( 'validate fails when an unknown type name is used in the schema', function (): void {
	$this->manager->registerSchema( 'media-library', [
		'disk' => [
			'type' => 'strng',
		],
	] );

	$result = $this->manager->validate( 'media-library', [
		'disk' => 'public',
	] );

	expect( $result->fails() )->toBeTrue();
	expect( $result->errors( 'disk' ) )->toHaveCount( 1 );
} );

test( 'registerSchema exposes the registered schema', function (): void {
	$this->manager->registerSchema( 'media-library', [
		'disk' => [
			'type' => 'string',
		],
	] );

	expect( $this->manager->schema( 'media-library' ) )->toBeInstanceOf( ConfigurationSchema::class );
	expect( $this->manager->schema( 'unknown' ) )->toBeNull();
} );

test( 'the facade proxies to the manager', function (): void {
	expect( ArtisanPackConfig::get( 'media-library', 'disk' ) )->toBe( 'public' );

	ArtisanPackConfig::set( 'media-library', 'disk', 's3' );

	expect( ArtisanPackConfig::get( 'media-library', 'disk' ) )->toBe( 's3' );
	expect( ArtisanPackConfig::has( 'media-library', 'disk' ) )->toBeTrue();
} );

test( 'the artisanpack_config helper reads package values via dot notation', function (): void {
	expect( artisanpack_config( 'media-library.max_file_size' ) )->toBe( 10240 );
	expect( artisanpack_config( 'media-library.missing', 'default' ) )->toBe( 'default' );
} );

test( 'the artisanpack_config helper returns the default for malformed keys', function (): void {
	expect( artisanpack_config( '', 'fallback' ) )->toBe( 'fallback' );
	expect( artisanpack_config( 'media-library', 'fallback' ) )->toBe( 'fallback' );
	expect( artisanpack_config( '.max_file_size', 'fallback' ) )->toBe( 'fallback' );
	expect( artisanpack_config( 'media-library.', 'fallback' ) )->toBe( 'fallback' );
} );
