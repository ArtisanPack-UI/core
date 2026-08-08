<?php

/**
 * Hook-backed site resolver.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\MultiTenancy
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.3.0
 */

namespace ArtisanPackUI\Core\MultiTenancy;

// filter_var() is used here to validate a hook return value, not to read
// request input, so the InputNotSanitized sniff misfires.
// phpcs:disable ArtisanPackUI.Security.ValidatedSanitizedInput.InputNotSanitized

use ArtisanPackUI\Core\Contracts\SiteResolver;
use ArtisanPackUI\Core\Exceptions\SiteResolutionException;

/**
 * Resolves the current site through the CMS framework's filter hook.
 *
 * This is the default resolver. It applies the site-resolution filter with a
 * null default, so an installation where nothing is listening resolves to null
 * and behaves as a single-tenant application. When artisanpack-ui/cms-framework
 * is installed it answers the filter, and the same resolver starts returning a
 * real site. The coupling flows one way: the packages that scope by site
 * listen, and cms-framework never needs to know they exist.
 *
 * Nothing *listening* is ordinary and resolves to null. artisanpack-ui/hooks
 * not being *installed* is different — there is then no filter to answer, so a
 * deployment configured as multi-site would silently unscope every query. That
 * case throws. It is unreachable while site resolution is switched off, because
 * `SiteContext` never consults a resolver then, which is what keeps this safe
 * as the shipped default for applications that have neither package.
 *
 * The filter is applied on every call rather than memoised, because a queue
 * worker or console command may iterate over several sites within a single
 * process and a cached answer would scope those later iterations to the wrong
 * site.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\MultiTenancy
 *
 * @since      1.3.0
 */
class HookSiteResolver implements SiteResolver
{
	/**
	 * The filter hook the current site is resolved through.
	 *
	 * @since 1.3.0
	 *
	 * @var string
	 */
	public const HOOK = 'ap.cmsFramework.currentSite.resolve';

	/**
	 * Gets the identifier of the site currently in context.
	 *
	 * @since 1.3.0
	 *
	 * @throws SiteResolutionException When a listener returns a value that is
	 *                                 neither null nor a usable site
	 *                                 identifier.
	 *
	 * @return int|string|null The current site identifier, or null when nothing
	 *                         answers the filter.
	 */
	public function currentSiteId(): int|string|null
	{
		if ( ! function_exists( 'applyFilters' ) ) {
			// Reaching this resolver at all means site resolution is switched
			// on and nothing earlier in the chain answered — so the hook is
			// being relied on to say which site this is, and the package that
			// fires it is not installed. Returning null here would unscope
			// every query in a deployment whose configuration says it is
			// multi-site.
			throw new SiteResolutionException( sprintf(
				'%s needs artisanpack-ui/hooks to apply the "%s" filter, and it is not installed.'
					. ' Require it, or remove this resolver from'
					. ' "artisanpack.core.multi_tenant.resolvers".',
				self::class,
				self::HOOK,
			), 0, null, 'site_resolver.hooks_missing' );
		}

		$resolved = applyFilters( self::HOOK, null );

		if ( null === $resolved ) {
			return null;
		}

		if ( is_int( $resolved ) ) {
			return $resolved;
		}

		if ( is_string( $resolved ) && '' !== trim( $resolved ) ) {
			// Note the asymmetry: "12" becomes int 12, but "012" stays a string
			// because FILTER_VALIDATE_INT rejects leading zeros. A listener that
			// zero-pads its identifiers therefore hands back a different PHP
			// type than one that does not, which matters to any consumer using
			// the identifier as an array key, in a strict comparison, or in a
			// cache key. Pick one format and keep to it.
			$asInt = filter_var( $resolved, FILTER_VALIDATE_INT );

			return false !== $asInt ? $asInt : $resolved;
		}

		// Coercing an unusable value to null would silently unscope every query
		// the resolution feeds, which leaks one site's records into another.
		// Failing loudly is the safer direction.
		throw new SiteResolutionException( sprintf(
			'The "%s" filter must return an integer or non-empty string site identifier, or null. Got %s.',
			self::HOOK,
			get_debug_type( $resolved ),
		), 0, null, 'site_resolver.invalid_hook_value' );
	}
}
