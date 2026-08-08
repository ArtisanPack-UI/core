<?php

namespace Tests\Fixtures\MultiTenancy;

use ArtisanPackUI\Core\MultiTenancy\SiteContext;
use Illuminate\Container\Container;

/**
 * Stands in for a package that reaches the shared context from the container.
 *
 * Mirrors how an Eloquent global scope inside artisanpack-ui/bookings would
 * reach the site in context after adopting the shared contract — a scope has no
 * constructor of its own to inject into, so it resolves on each call.
 */
class ContainerConsumer
{
	/**
	 * Gets the site this consumer would scope its queries to.
	 *
	 * @return int|string|null The current site identifier.
	 */
	public function scopedSiteId(): int|string|null
	{
		return Container::getInstance()->make( SiteContext::class )->currentSiteId();
	}
}
