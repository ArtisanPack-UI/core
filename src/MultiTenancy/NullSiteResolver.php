<?php

/**
 * Null site resolver.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\MultiTenancy
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.3.0
 */

namespace ArtisanPackUI\Core\MultiTenancy;

use ArtisanPackUI\Core\Contracts\SiteResolver;

/**
 * A resolver that never puts a site in context.
 *
 * Bind this when an application wants site scoping switched off outright
 * rather than merely unconfigured, so nothing can put a site in context behind
 * its back. It is also the fallback core binds when the configured resolver
 * chain is empty, which keeps `SiteResolver` resolvable in a single-tenant
 * application without every consumer having to guard for an unbound contract.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\MultiTenancy
 *
 * @since      1.3.0
 */
class NullSiteResolver implements SiteResolver
{
	/**
	 * Gets the identifier of the site currently in context.
	 *
	 * @since 1.3.0
	 *
	 * @return int|string|null Always null.
	 */
	public function currentSiteId(): int|string|null
	{
		return null;
	}
}
