<?php

/**
 * The ecosystem's single site context.
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
use Closure;
use Illuminate\Contracts\Config\Repository;

/**
 * The one place a site becomes known.
 *
 * Packages ask this service — not a resolver of their own — which site the
 * current code is running for, so every package in a request gets the same
 * answer from the same configuration. Resolution itself stays pluggable behind
 * `SiteResolver`; what this class adds on top is the ability to pin a site
 * deliberately, which is what console commands, queue workers, and
 * cross-site reporting need.
 *
 * A pinned site always wins over the resolver, and it wins even when site
 * scoping is disabled in configuration: the `enabled` flag governs *automatic*
 * resolution, whereas a `forSite()` call is an unambiguous instruction from
 * the calling code. With nothing pinned and scoping disabled, the answer is
 * always null and every consumer leaves its queries alone.
 *
 * Nothing here is memoised. The resolver is asked on every call because the
 * site in context can change within a single process — a command looping over
 * sites is the obvious case — and a cached answer would scope later iterations
 * to the wrong site.
 *
 * Under Laravel Octane, call `flush()` on `RequestTerminated` so a pinned site
 * cannot leak from one request into the next.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\MultiTenancy
 *
 * @since      1.3.0
 */
class SiteContext
{
	/**
	 * Whether a site has been pinned explicitly.
	 *
	 * Tracked separately from the identifier because pinning "no site" is a
	 * meaningful instruction that is not the same as having pinned nothing.
	 *
	 * @since 1.3.0
	 *
	 * @var bool
	 */
	protected bool $pinned = false;

	/**
	 * The pinned site identifier, when one is pinned.
	 *
	 * @since 1.3.0
	 *
	 * @var int|string|null
	 */
	protected int|string|null $pinnedSiteId = null;

	/**
	 * Create a new site context.
	 *
	 * @since 1.3.0
	 *
	 * @param  SiteResolver  $resolver  The resolver consulted when no site is pinned.
	 * @param  Repository  $config  The application configuration repository.
	 */
	public function __construct(
		protected SiteResolver $resolver,
		protected Repository $config,
	) {
	}

	/**
	 * Gets the identifier of the site currently in context.
	 *
	 * @since 1.3.0
	 *
	 * @return int|string|null The current site identifier, or null when no site
	 *                         is in context and queries should not be scoped.
	 */
	public function currentSiteId(): int|string|null
	{
		if ( $this->pinned ) {
			return $this->pinnedSiteId;
		}

		if ( ! $this->enabled() ) {
			return null;
		}

		return $this->resolver->currentSiteId();
	}

	/**
	 * Determines whether any site is currently in context.
	 *
	 * @since 1.3.0
	 *
	 * @return bool True when a site is in context.
	 */
	public function hasSite(): bool
	{
		return null !== $this->currentSiteId();
	}

	/**
	 * Determines whether automatic site resolution is switched on.
	 *
	 * @since 1.3.0
	 *
	 * @return bool True when the configured resolver should be consulted.
	 */
	public function enabled(): bool
	{
		return (bool) $this->config->get( 'artisanpack.core.multi_tenant.enabled', false );
	}

	/**
	 * Gets the resolver backing automatic resolution.
	 *
	 * @since 1.3.0
	 *
	 * @return SiteResolver The bound resolver.
	 */
	public function resolver(): SiteResolver
	{
		return $this->resolver;
	}

	/**
	 * Pins a site for the remainder of the process.
	 *
	 * Prefer `forSite()` wherever the pinned site should only apply to a
	 * bounded piece of work — it restores whatever was in context afterwards,
	 * including on failure.
	 *
	 * @since 1.3.0
	 *
	 * @param  int|string|null  $siteId  The site to pin, or null to pin "no site".
	 *
	 * @return static The same instance, for chaining.
	 */
	public function setSiteId( int|string|null $siteId ): static
	{
		$this->pinned       = true;
		$this->pinnedSiteId = $siteId;

		return $this;
	}

	/**
	 * Releases any pinned site, handing resolution back to the resolver.
	 *
	 * @since 1.3.0
	 *
	 * @return static The same instance, for chaining.
	 */
	public function forget(): static
	{
		$this->pinned       = false;
		$this->pinnedSiteId = null;

		return $this;
	}

	/**
	 * Runs a callback with a specific site in context.
	 *
	 * Whatever was in context before — a different pinned site, or nothing
	 * pinned at all — is restored afterwards, including when the callback
	 * throws. Calls nest safely.
	 *
	 * @since 1.3.0
	 *
	 * @param  int|string|null  $siteId  The site to run the callback for.
	 * @param  Closure  $callback  The callback to run.
	 *
	 * @return mixed Whatever the callback returns.
	 */
	public function forSite( int|string|null $siteId, Closure $callback ): mixed
	{
		$previouslyPinned = $this->pinned;
		$previousSiteId   = $this->pinnedSiteId;

		$this->setSiteId( $siteId );

		try {
			return $callback( $siteId );
		} finally {
			$this->pinned       = $previouslyPinned;
			$this->pinnedSiteId = $previousSiteId;
		}
	}

	/**
	 * Runs a callback with no site in context.
	 *
	 * Consumers leave their queries unscoped for the duration, which is what
	 * administrative and maintenance work spanning every site needs.
	 *
	 * @since 1.3.0
	 *
	 * @param  Closure  $callback  The callback to run.
	 *
	 * @return mixed Whatever the callback returns.
	 */
	public function withoutSite( Closure $callback ): mixed
	{
		return $this->forSite( null, $callback );
	}

	/**
	 * Clears all per-request state for Laravel Octane.
	 *
	 * Register this against Octane's `RequestTerminated` event so a site pinned
	 * during one request cannot leak into the next.
	 *
	 * @since 1.3.0
	 *
	 * @return static The same instance, for chaining.
	 */
	public function flush(): static
	{
		return $this->forget();
	}
}
