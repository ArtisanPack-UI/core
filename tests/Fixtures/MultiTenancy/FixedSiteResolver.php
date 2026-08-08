<?php

namespace Tests\Fixtures\MultiTenancy;

use ArtisanPackUI\Core\Contracts\SiteResolver;

/**
 * Always resolves to a preset identifier.
 */
class FixedSiteResolver implements SiteResolver
{
	/**
	 * The identifier every instance resolves to.
	 *
	 * @var int|string|null
	 */
	public static int|string|null $siteId = 7;

	/**
	 * Gets the identifier of the site currently in context.
	 *
	 * @return int|string|null The preset identifier.
	 */
	public function currentSiteId(): int|string|null
	{
		return self::$siteId;
	}
}
