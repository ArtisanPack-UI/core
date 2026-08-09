<?php

use ArtisanPackUI\Core\Contracts\SiteResolver;
use ArtisanPackUI\Core\Exceptions\ArtisanPackException;
use ArtisanPackUI\Core\Exceptions\SiteResolutionException;
use ArtisanPackUI\Core\MultiTenancy\ChainSiteResolver;
use ArtisanPackUI\Core\MultiTenancy\HookSiteResolver;
use ArtisanPackUI\Core\MultiTenancy\NullSiteResolver;
use ArtisanPackUI\Core\MultiTenancy\SiteContext;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Tests\Fixtures\MultiTenancy\FixedSiteResolver;
use Tests\Fixtures\MultiTenancy\HookStub;
use Tests\Fixtures\MultiTenancy\NarrowedSiteResolver;
use Tests\Fixtures\MultiTenancy\NotAResolver;
use Tests\Fixtures\MultiTenancy\ThrowingSiteResolver;

if ( ! function_exists( 'applyFilters' ) ) {
	/**
	 * Stands in for the artisanpack-ui/hooks helper of the same name.
	 *
	 * Core does not depend on that package, so the suite supplies its own.
	 *
	 * @param  string  $hook  The hook name.
	 * @param  mixed  $value  The default value.
	 *
	 * @return mixed The recorded answer, or the default.
	 */
	function applyFilters( string $hook, mixed $value ): mixed
	{
		return HookStub::apply( $hook, $value );
	}
}

beforeEach( function (): void {
	HookStub::reset();
	FixedSiteResolver::$siteId    = 7;
	NarrowedSiteResolver::$siteId = 3;
} );

/**
 * Builds a site context over a resolver and an inline configuration array.
 *
 * @param  SiteResolver  $resolver  The resolver to consult.
 * @param  bool  $enabled  Whether automatic resolution is switched on.
 *
 * @return SiteContext The assembled context.
 */
function makeSiteContext( SiteResolver $resolver, bool $enabled = true ): SiteContext
{
	return new SiteContext( $resolver, new Repository( [
		'artisanpack' => [ 'core' => [ 'multi_tenant' => [ 'enabled' => $enabled ] ] ],
	] ) );
}

describe( 'NullSiteResolver', function (): void {
	it( 'never puts a site in context', function (): void {
		expect( ( new NullSiteResolver )->currentSiteId() )->toBeNull();
	} );
} );

describe( 'HookSiteResolver', function (): void {
	it( 'resolves null when nothing answers the filter', function (): void {
		expect( ( new HookSiteResolver )->currentSiteId() )->toBeNull();
	} );

	it( 'returns an integer identifier as-is', function (): void {
		HookStub::answer( HookSiteResolver::HOOK, 12 );

		expect( ( new HookSiteResolver )->currentSiteId() )->toBe( 12 );
	} );

	it( 'coerces a numeric string identifier to an integer', function (): void {
		HookStub::answer( HookSiteResolver::HOOK, '12' );

		expect( ( new HookSiteResolver )->currentSiteId() )->toBe( 12 );
	} );

	it( 'keeps a non-numeric string identifier intact', function (): void {
		HookStub::answer( HookSiteResolver::HOOK, 'b8f1c0de' );

		expect( ( new HookSiteResolver )->currentSiteId() )->toBe( 'b8f1c0de' );
	} );

	it( 'trims a padded string identifier so one site keeps one identity', function (): void {
		// Returning the untrimmed value would make " b8f1c0de " a different
		// identifier from "b8f1c0de" everywhere it is used as a key.
		HookStub::answer( HookSiteResolver::HOOK, '  b8f1c0de  ' );

		expect( ( new HookSiteResolver )->currentSiteId() )->toBe( 'b8f1c0de' );
	} );

	it( 'fails loudly rather than unscoping when a listener answers with junk', function ( mixed $answer ): void {
		HookStub::answer( HookSiteResolver::HOOK, $answer );

		expect( fn () => ( new HookSiteResolver )->currentSiteId() )
			->toThrow( SiteResolutionException::class );
	} )->with( [
		'boolean'      => [ true ],
		'float'        => [ 1.5 ],
		'array'        => [ [ 1 ] ],
		'empty string' => [ '  ' ],
	] );

	it( 'reports the ecosystem-wide exception type and an error code', function (): void {
		HookStub::answer( HookSiteResolver::HOOK, true );

		$caught = null;

		try {
			( new HookSiteResolver )->currentSiteId();
		} catch ( SiteResolutionException $exception ) {
			$caught = $exception;
		}

		// Asserted outside the catch so that a regression which stops throwing
		// fails the test rather than passing with nothing asserted.
		expect( $caught )->toBeInstanceOf( ArtisanPackException::class )
			->and( $caught->getErrorCode() )->toBe( 'site_resolver.invalid_hook_value' )
			->and( $caught->getPackage() )->toBe( 'core' );
	} );

	it( 're-reads the filter on every call so a worker can loop over sites', function (): void {
		$resolver = new HookSiteResolver;

		HookStub::answer( HookSiteResolver::HOOK, 1 );
		$first = $resolver->currentSiteId();

		HookStub::answer( HookSiteResolver::HOOK, 2 );
		$second = $resolver->currentSiteId();

		expect( [ $first, $second ] )->toBe( [ 1, 2 ] );
	} );
} );

it( 'refuses to resolve through the hook when artisanpack-ui/hooks is absent', function (): void {
	// The suite defines applyFilters() itself, so the missing-package branch
	// cannot be reached here. Assert the guard exists and names the remedy, so a
	// rewrite that turns it back into a silent `return null` is caught — that
	// would unscope every query in a deployment configured as multi-site.
	$source = file_get_contents( ( new ReflectionClass( HookSiteResolver::class ) )->getFileName() );

	expect( $source )->toContain( "if ( ! function_exists( 'applyFilters' ) ) {" )
		->and( $source )->toContain( 'site_resolver.hooks_missing' );
} );

describe( 'ChainSiteResolver', function (): void {
	it( 'resolves null when no resolver is configured', function (): void {
		expect( ( new ChainSiteResolver )->currentSiteId() )->toBeNull();
	} );

	it( 'takes the first non-null answer', function (): void {
		FixedSiteResolver::$siteId = 9;

		$chain = new ChainSiteResolver( [
			new NullSiteResolver,
			new FixedSiteResolver,
			new NarrowedSiteResolver,
		] );

		expect( $chain->currentSiteId() )->toBe( 9 );
	} );

	it( 'aborts rather than skipping a resolver that fails', function (): void {
		$chain = new ChainSiteResolver( [
			new ThrowingSiteResolver,
			new FixedSiteResolver,
		] );

		expect( fn () => $chain->currentSiteId() )->toThrow( RuntimeException::class );
	} );

	it( 'builds from configured class names through the container', function (): void {
		$chain = ChainSiteResolver::fromConfig(
			[ NullSiteResolver::class, FixedSiteResolver::class ],
			new Container,
		);

		expect( $chain->resolvers() )->toHaveCount( 2 )
			->and( $chain->currentSiteId() )->toBe( 7 );
	} );

	it( 'rejects a configured class that does not exist', function (): void {
		expect( fn () => ChainSiteResolver::fromConfig( [ 'App\\Nope' ], new Container ) )
			->toThrow( SiteResolutionException::class );
	} );

	it( 'rejects a configured class that does not implement the contract', function (): void {
		expect( fn () => ChainSiteResolver::fromConfig( [ NotAResolver::class ], new Container ) )
			->toThrow( SiteResolutionException::class );
	} );
} );

describe( 'SiteContext', function (): void {
	it( 'delegates to the resolver when nothing is pinned', function (): void {
		expect( makeSiteContext( new FixedSiteResolver )->currentSiteId() )->toBe( 7 );
	} );

	it( 'ignores the resolver while site scoping is switched off', function (): void {
		$context = makeSiteContext( new FixedSiteResolver, enabled: false );

		expect( $context->currentSiteId() )->toBeNull()
			->and( $context->hasSite() )->toBeFalse()
			->and( $context->enabled() )->toBeFalse();
	} );

	it( 'honours a deliberately pinned site even while scoping is switched off', function (): void {
		$context = makeSiteContext( new FixedSiteResolver, enabled: false );

		expect( $context->forSite( 4, fn () => $context->currentSiteId() ) )->toBe( 4 );
	} );

	it( 'lets a pinned site win over the resolver', function (): void {
		$context = makeSiteContext( new FixedSiteResolver );

		expect( $context->setSiteId( 42 )->currentSiteId() )->toBe( 42 );
	} );

	it( 'hands resolution back to the resolver once the pin is released', function (): void {
		$context = makeSiteContext( new FixedSiteResolver );

		expect( $context->setSiteId( 42 )->forget()->currentSiteId() )->toBe( 7 );
	} );

	it( 'distinguishes pinning no site from pinning nothing', function (): void {
		$context = makeSiteContext( new FixedSiteResolver );

		expect( $context->setSiteId( null )->currentSiteId() )->toBeNull()
			->and( $context->forget()->currentSiteId() )->toBe( 7 );
	} );

	it( 'reports nothing pinned before anything pins a site', function (): void {
		expect( makeSiteContext( new FixedSiteResolver )->isPinned() )->toBeFalse();
	} );

	it( 'reports a pin even when the pinned site is no site at all', function (): void {
		$context = makeSiteContext( new FixedSiteResolver );

		expect( $context->setSiteId( null )->isPinned() )->toBeTrue()
			->and( $context->currentSiteId() )->toBeNull();
	} );

	it( 'stops reporting a pin once it is released', function (): void {
		$context = makeSiteContext( new FixedSiteResolver );

		expect( $context->setSiteId( 42 )->forget()->isPinned() )->toBeFalse()
			->and( $context->setSiteId( 42 )->flush()->isPinned() )->toBeFalse();
	} );

	it( 'reports a pin only for the duration of a bounded callback', function (): void {
		$context = makeSiteContext( new FixedSiteResolver );

		expect( $context->withoutSite( fn () => $context->isPinned() ) )->toBeTrue()
			->and( $context->forSite( 11, fn () => $context->isPinned() ) )->toBeTrue()
			->and( $context->isPinned() )->toBeFalse();
	} );

	it( 'restores the previous context after running for a site', function (): void {
		$context = makeSiteContext( new FixedSiteResolver );

		$inner = $context->forSite( 11, fn () => $context->currentSiteId() );

		expect( $inner )->toBe( 11 )
			->and( $context->currentSiteId() )->toBe( 7 );
	} );

	it( 'passes the pinned identifier to the callback', function (): void {
		$context = makeSiteContext( new FixedSiteResolver );

		expect( $context->forSite( 11, fn ( $siteId ) => $siteId ) )->toBe( 11 );
	} );

	it( 'restores the previous context even when the callback throws', function (): void {
		$context = makeSiteContext( new FixedSiteResolver );

		try {
			$context->forSite( 11, function (): void {
				throw new RuntimeException( 'boom' );
			} );
		} catch ( RuntimeException ) {
			// Expected; the assertion below is the point of the test.
		}

		expect( $context->currentSiteId() )->toBe( 7 );
	} );

	it( 'nests without losing the outer site', function (): void {
		$context = makeSiteContext( new FixedSiteResolver );

		$seen = $context->forSite( 1, fn () => [
			$context->currentSiteId(),
			$context->forSite( 2, fn () => $context->currentSiteId() ),
			$context->currentSiteId(),
		] );

		expect( $seen )->toBe( [ 1, 2, 1 ] )
			->and( $context->currentSiteId() )->toBe( 7 );
	} );

	it( 'runs a callback with no site in context', function (): void {
		$context = makeSiteContext( new FixedSiteResolver );

		expect( $context->withoutSite( fn () => $context->currentSiteId() ) )->toBeNull()
			->and( $context->currentSiteId() )->toBe( 7 );
	} );

	it( 'clears a pinned site when flushed for Octane', function (): void {
		$context = makeSiteContext( new FixedSiteResolver );

		expect( $context->setSiteId( 42 )->flush()->currentSiteId() )->toBe( 7 );
	} );

	it( 'exposes the resolver it was built with', function (): void {
		$resolver = new FixedSiteResolver;

		expect( makeSiteContext( $resolver )->resolver() )->toBe( $resolver );
	} );

	it( 're-asks the resolver on every call rather than memoising', function (): void {
		$context = makeSiteContext( new FixedSiteResolver );

		FixedSiteResolver::$siteId = 1;
		$first                     = $context->currentSiteId();

		FixedSiteResolver::$siteId = 2;
		$second                    = $context->currentSiteId();

		expect( [ $first, $second ] )->toBe( [ 1, 2 ] );
	} );
} );

it( 'accepts an implementation that narrows the return type to an integer', function (): void {
	$resolver = new NarrowedSiteResolver;

	expect( $resolver )->toBeInstanceOf( SiteResolver::class )
		->and( $resolver->currentSiteId() )->toBe( 3 );
} );
