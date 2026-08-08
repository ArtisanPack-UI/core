<?php

use ArtisanPackUI\Core\Contracts\SiteResolver;
use ArtisanPackUI\Core\Exceptions\SiteResolutionException;
use ArtisanPackUI\Core\Facades\ArtisanPackSite;
use ArtisanPackUI\Core\MultiTenancy\ChainSiteResolver;
use ArtisanPackUI\Core\MultiTenancy\HookSiteResolver;
use ArtisanPackUI\Core\MultiTenancy\NullSiteResolver;
use ArtisanPackUI\Core\MultiTenancy\SiteContext;
use Tests\Fixtures\MultiTenancy\ContainerConsumer;
use Tests\Fixtures\MultiTenancy\FixedSiteResolver;
use Tests\Fixtures\MultiTenancy\InjectedConsumer;
use Tests\Fixtures\MultiTenancy\NarrowedSiteResolver;

beforeEach( function (): void {
	FixedSiteResolver::$siteId    = 7;
	NarrowedSiteResolver::$siteId = 3;
} );

it( 'merges the multi-tenant defaults so an unpublished config still honours them', function (): void {
	// Core publishes config/artisanpack.php rather than merging it, so without
	// the dedicated merge an operator could set ARTISANPACK_MULTI_TENANT_ENABLED
	// and have it silently ignored — running a multi-site install unscoped.
	expect( config()->has( 'artisanpack.core.multi_tenant.enabled' ) )->toBeTrue()
		->and( config( 'artisanpack.core.multi_tenant.resolvers' ) )->toBe( [ HookSiteResolver::class ] );
} );

it( 'binds the hook-backed resolver by default so an unpublished config still works', function (): void {
	$resolver = app( SiteResolver::class );

	expect( $resolver )->toBeInstanceOf( ChainSiteResolver::class )
		->and( $resolver->resolvers() )->toHaveCount( 1 )
		->and( $resolver->resolvers()[0] )->toBeInstanceOf( HookSiteResolver::class );
} );

it( 'falls back to the null resolver when the configured list is empty', function (): void {
	config()->set( 'artisanpack.core.multi_tenant.resolvers', [] );

	expect( app( SiteResolver::class ) )->toBeInstanceOf( NullSiteResolver::class );
} );

it( 'builds the resolver chain from configuration', function (): void {
	config()->set( 'artisanpack.core.multi_tenant.resolvers', [
		NullSiteResolver::class,
		FixedSiteResolver::class,
	] );

	expect( app( SiteResolver::class )->currentSiteId() )->toBe( 7 );
} );

it( 'surfaces a misconfigured resolver as a configuration fault', function (): void {
	config()->set( 'artisanpack.core.multi_tenant.resolvers', [ 'App\\Missing' ] );

	expect( fn () => app( SiteResolver::class ) )->toThrow( SiteResolutionException::class );
} );

it( 'rejects a resolver list that is not a list rather than treating it as an opt-out', function (): void {
	// The array brackets forgotten. Reading this as "no resolvers" would leave
	// every consumer unscoped while the configuration reads as though tenancy
	// were switched on.
	config()->set( 'artisanpack.core.multi_tenant.resolvers', FixedSiteResolver::class );

	expect( fn () => app( SiteResolver::class ) )->toThrow( SiteResolutionException::class );
} );

it( 'points every published facade alias at a class that exists', function (): void {
	// The alias is what an application actually types. A rename that updates the
	// class but not composer.json turns \ArtisanPackSite::currentSiteId() into a
	// fatal error that no test importing the FQCN would ever notice.
	$aliases = json_decode(
		file_get_contents( __DIR__ . '/../../composer.json' ),
		true,
	)['extra']['laravel']['aliases'];

	foreach ( $aliases as $alias => $class ) {
		expect( class_exists( $class ) )->toBeTrue( "Alias {$alias} points at missing class {$class}." );
	}
} );

it( 'scopes the site context so a pinned site cannot leak between requests or jobs', function (): void {
	// Bound with scoped() rather than singleton(): Laravel forgets scoped
	// instances between Octane requests and between queue jobs, so a job that
	// pins a site and never releases it cannot scope the next job to it.
	config()->set( 'artisanpack.core.multi_tenant.enabled', true );
	config()->set( 'artisanpack.core.multi_tenant.resolvers', [ FixedSiteResolver::class ] );

	app( SiteContext::class )->setSiteId( 99 );

	expect( apCurrentSiteId() )->toBe( 99 );

	app()->forgetScopedInstances();

	expect( apCurrentSiteId() )->toBe( 7 );
} );

it( 'rebuilds resolvers per scope so a request-dependent resolver cannot freeze', function (): void {
	config()->set( 'artisanpack.core.multi_tenant.resolvers', [ FixedSiteResolver::class ] );

	$first = app( SiteResolver::class );

	app()->forgetScopedInstances();

	expect( app( SiteResolver::class ) )->not->toBe( $first );
} );

it( 'reuses one site context within a scope, over the bound resolver', function (): void {
	config()->set( 'artisanpack.core.multi_tenant.enabled', true );
	config()->set( 'artisanpack.core.multi_tenant.resolvers', [ FixedSiteResolver::class ] );

	expect( app( SiteContext::class ) )->toBe( app( SiteContext::class ) )
		->and( app( SiteContext::class )->resolver() )->toBe( app( SiteResolver::class ) )
		->and( app( SiteContext::class )->currentSiteId() )->toBe( 7 );
} );

it( 'exposes the current site through the helper and the facade', function (): void {
	config()->set( 'artisanpack.core.multi_tenant.enabled', true );
	config()->set( 'artisanpack.core.multi_tenant.resolvers', [ FixedSiteResolver::class ] );

	expect( apCurrentSiteId() )->toBe( 7 )
		->and( ArtisanPackSite::currentSiteId() )->toBe( 7 )
		->and( ArtisanPackSite::hasSite() )->toBeTrue();
} );

it( 'gives every package the same answer from one configuration', function (): void {
	// Two packages scoping their own tables from a single configuration block:
	// one receives the shared context by injection the way an analytics service
	// would, the other resolves it from the container on each call the way a
	// bookings global scope would. Neither configures tenancy itself, and the
	// two must never disagree — a request that scopes one package to site 2 and
	// the other to site 1 is the failure this contract exists to prevent.
	config()->set( 'artisanpack.core.multi_tenant.enabled', true );
	config()->set( 'artisanpack.core.multi_tenant.resolvers', [ FixedSiteResolver::class ] );

	$analytics = app( InjectedConsumer::class );
	$bookings  = new ContainerConsumer;

	expect( $analytics->scopedSiteId() )->toBe( 7 )
		->and( $bookings->scopedSiteId() )->toBe( 7 );

	// A site pinned for a stretch of work moves both packages together, which is
	// what a console command looping over sites depends on.
	app( SiteContext::class )->forSite( 99, function () use ( $analytics, $bookings ): void {
		expect( $analytics->scopedSiteId() )->toBe( 99 )
			->and( $bookings->scopedSiteId() )->toBe( 99 );
	} );

	// Changing the one configuration surface moves both packages together too.
	FixedSiteResolver::$siteId = 21;

	expect( $analytics->scopedSiteId() )->toBe( 21 )
		->and( $bookings->scopedSiteId() )->toBe( 21 );

	// And switching tenancy off leaves both unscoped rather than one of them.
	config()->set( 'artisanpack.core.multi_tenant.enabled', false );

	expect( $analytics->scopedSiteId() )->toBeNull()
		->and( $bookings->scopedSiteId() )->toBeNull();
} );
