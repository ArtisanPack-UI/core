<?php

/**
 * Shared site resolution contract.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Contracts
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.3.0
 */

namespace ArtisanPackUI\Core\Contracts;

/**
 * Answers the question "which site is this code running for?".
 *
 * This is the single site-resolution contract for the ArtisanPack UI
 * ecosystem. Every package that scopes data per site — analytics, bookings,
 * and anything added later — asks the one resolver bound against this
 * interface, so a single request cannot resolve to site 2 for one package
 * while resolving to site 1 for another.
 *
 * Two deliberate design choices:
 *
 * - **The contract is keyed on the identifier, not a model.** Core cannot
 *   depend on any package's `Site` model, and packages must not depend on each
 *   other's, so the identifier is the only thing all of them can share. A
 *   package that needs the full record looks it up from the identifier.
 * - **There is no `Request` parameter.** A resolver that requires a request is
 *   unusable from a console command or queue worker, which is precisely where
 *   per-site iteration happens. Implementations that resolve from the request
 *   read it from the container themselves and return null when there isn't
 *   one.
 *
 * Returning null means "no site in context", not "site not found". Consumers
 * treat null as "do not scope", which is what lets a single-tenant application
 * install these packages and never configure anything.
 *
 * Implementations should re-resolve on every call rather than memoise: a
 * worker looping over sites changes the answer within a single process, and a
 * cached answer would scope the later iterations to the wrong site. Callers
 * that want a stable answer for a stretch of work should pin one explicitly
 * through `ArtisanPackUI\Core\MultiTenancy\SiteContext::forSite()`.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Contracts
 *
 * @since      1.3.0
 */
interface SiteResolver
{
	/**
	 * Gets the identifier of the site currently in context.
	 *
	 * @since 1.3.0
	 *
	 * @return int|string|null The current site identifier, or null when no site
	 *                         is in context.
	 */
	public function currentSiteId(): int|string|null;
}
