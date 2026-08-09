<?php

/**
 * Facade for the shared site context.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Facades
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.3.0
 */

namespace ArtisanPackUI\Core\Facades;

use ArtisanPackUI\Core\Contracts\SiteResolver;
use ArtisanPackUI\Core\MultiTenancy\SiteContext;
use Closure;

/**
 * Provides static access to the shared {@see SiteContext}.
 *
 * The underlying instance is bound `scoped`, so every call within one request
 * or queue job reaches the same context — including any site pinned by the
 * surrounding code — and the next request or job starts from a clean one.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Facades
 *
 * @method static int|string|null currentSiteId()
 * @method static bool hasSite()
 * @method static bool isPinned()
 * @method static bool enabled()
 * @method static SiteResolver resolver()
 * @method static SiteContext setSiteId( int|string|null $siteId )
 * @method static SiteContext forget()
 * @method static mixed forSite( int|string|null $siteId, Closure $callback )
 * @method static mixed withoutSite( Closure $callback )
 * @method static SiteContext flush()
 *
 * @see SiteContext
 * @since      1.3.0
 */
class ArtisanPackSite extends ArtisanPackFacade
{
	/**
	 * Get the registered name of the component.
	 *
	 * @since 1.3.0
	 *
	 * @return string The container binding key.
	 */
	protected static function getFacadeAccessor(): string
	{
		return SiteContext::class;
	}
}
