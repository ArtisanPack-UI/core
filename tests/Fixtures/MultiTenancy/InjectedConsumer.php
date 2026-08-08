<?php

namespace Tests\Fixtures\MultiTenancy;

// PHP requires the lowercase __construct magic method; the
// MagicMethodNotUppercase sniff misfires on the constructor here.
// phpcs:disable ArtisanPackUI.TypeHints.TypeDeclaration.MagicMethodNotUppercase

use ArtisanPackUI\Core\MultiTenancy\SiteContext;

/**
 * Stands in for a package that receives the shared context by injection.
 *
 * Mirrors how a service inside artisanpack-ui/analytics would reach the site in
 * context after adopting the shared contract.
 */
class InjectedConsumer
{
	/**
	 * Create a new consumer.
	 *
	 * @param  SiteContext  $siteContext  The shared site context.
	 */
	public function __construct( protected SiteContext $siteContext )
	{
	}

	/**
	 * Gets the site this consumer would scope its queries to.
	 *
	 * @return int|string|null The current site identifier.
	 */
	public function scopedSiteId(): int|string|null
	{
		return $this->siteContext->currentSiteId();
	}
}
