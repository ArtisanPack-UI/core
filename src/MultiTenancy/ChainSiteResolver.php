<?php

/**
 * Ordered chain of site resolvers.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\MultiTenancy
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.3.0
 */

namespace ArtisanPackUI\Core\MultiTenancy;

// PHP requires the lowercase __construct magic method; the
// MagicMethodNotUppercase sniff misfires on the constructor here.
// phpcs:disable ArtisanPackUI.TypeHints.TypeDeclaration.MagicMethodNotUppercase

use ArtisanPackUI\Core\Contracts\SiteResolver;
use ArtisanPackUI\Core\Exceptions\SiteResolutionException;
use Illuminate\Contracts\Container\Container;

/**
 * Asks each resolver in turn and takes the first non-null answer.
 *
 * A chain is itself a `SiteResolver`, which is what lets an application
 * configure several strategies — a subdomain lookup, a header, a CMS hook —
 * while every consumer still talks to exactly one contract. Chaining is an
 * implementation detail of resolution, not something consumers see.
 *
 * A resolver that throws aborts the chain rather than being skipped. Skipping
 * would hand the next resolver's answer — or null — to a caller that is about
 * to scope a query, turning a configuration fault into cross-site data
 * exposure.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\MultiTenancy
 *
 * @since      1.3.0
 */
class ChainSiteResolver implements SiteResolver
{
	/**
	 * The resolvers to ask, in the order they are asked.
	 *
	 * @since 1.3.0
	 *
	 * @var array<int, SiteResolver>
	 */
	protected array $resolvers;

	/**
	 * Create a new chained resolver.
	 *
	 * @since 1.3.0
	 *
	 * @param  array<int, SiteResolver>  $resolvers  The resolvers to ask, in order.
	 */
	public function __construct( array $resolvers = [] )
	{
		$this->resolvers = array_values( $resolvers );
	}

	/**
	 * Builds a chain from a list of resolver class names.
	 *
	 * Every entry is resolved through the container so resolvers may declare
	 * their own dependencies. A class that is missing or does not implement the
	 * shared contract is a configuration fault and is reported as one — the
	 * alternative, quietly dropping it, produces an application that scopes
	 * nothing and looks like it is working.
	 *
	 * @since 1.3.0
	 *
	 * @param  array<int, class-string>  $resolverClasses  The configured resolver class names.
	 * @param  Container  $container  The container used to build each resolver.
	 *
	 * @throws SiteResolutionException When a configured class is missing or does
	 *                                 not implement `SiteResolver`.
	 *
	 * @return static The assembled chain.
	 */
	public static function fromConfig( array $resolverClasses, Container $container ): static
	{
		$resolvers = [];

		foreach ( $resolverClasses as $resolverClass ) {
			if ( ! is_string( $resolverClass ) || ! class_exists( $resolverClass ) ) {
				throw new SiteResolutionException( sprintf(
					'The configured site resolver %s does not exist.',
					is_string( $resolverClass ) ? sprintf( '"%s"', $resolverClass ) : get_debug_type( $resolverClass ),
				), 0, null, 'site_resolver.missing_class' );
			}

			$resolver = $container->make( $resolverClass );

			if ( ! $resolver instanceof SiteResolver ) {
				throw new SiteResolutionException( sprintf(
					'The configured site resolver "%s" must implement %s.',
					$resolverClass,
					SiteResolver::class,
				), 0, null, 'site_resolver.invalid_class' );
			}

			$resolvers[] = $resolver;
		}

		return new static( $resolvers );
	}

	/**
	 * Gets the identifier of the site currently in context.
	 *
	 * @since 1.3.0
	 *
	 * @return int|string|null The first non-null identifier any resolver
	 *                         returns, or null when none puts a site in
	 *                         context.
	 */
	public function currentSiteId(): int|string|null
	{
		foreach ( $this->resolvers as $resolver ) {
			$siteId = $resolver->currentSiteId();

			if ( null !== $siteId ) {
				return $siteId;
			}
		}

		return null;
	}

	/**
	 * Gets the resolvers making up the chain.
	 *
	 * @since 1.3.0
	 *
	 * @return array<int, SiteResolver> The chained resolvers, in order.
	 */
	public function resolvers(): array
	{
		return $this->resolvers;
	}
}
