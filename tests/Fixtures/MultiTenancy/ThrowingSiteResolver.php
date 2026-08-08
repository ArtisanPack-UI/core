<?php

namespace Tests\Fixtures\MultiTenancy;

use ArtisanPackUI\Core\Contracts\SiteResolver;
use RuntimeException;

/**
 * Fails whenever it is asked, to prove a chain aborts rather than skipping.
 */
class ThrowingSiteResolver implements SiteResolver
{
	/**
	 * Gets the identifier of the site currently in context.
	 *
	 * @throws RuntimeException Always.
	 *
	 * @return int|string|null Never returns.
	 */
	public function currentSiteId(): int|string|null
	{
		throw new RuntimeException( 'Resolver exploded.' );
	}
}
