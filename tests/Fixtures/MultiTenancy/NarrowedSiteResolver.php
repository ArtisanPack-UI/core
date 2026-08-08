<?php

namespace Tests\Fixtures\MultiTenancy;

use ArtisanPackUI\Core\Contracts\SiteResolver;

/**
 * Narrows the shared contract's return type to `?int`.
 *
 * Proves that a package whose own contract is keyed on an integer identifier —
 * artisanpack-ui/bookings, at the time of writing — can extend or implement the
 * shared contract without widening its own signature.
 */
class NarrowedSiteResolver implements SiteResolver
{
	/**
	 * The identifier every instance resolves to.
	 *
	 * @var int|null
	 */
	public static ?int $siteId = 3;

	/**
	 * Gets the identifier of the site currently in context.
	 *
	 * @return int|null The preset identifier.
	 */
	public function currentSiteId(): ?int
	{
		return self::$siteId;
	}
}
