<?php

/**
 * Exception raised for site resolution errors.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Exceptions
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.3.0
 */

namespace ArtisanPackUI\Core\Exceptions;

/**
 * Thrown when the site currently in context cannot be determined safely.
 *
 * Site resolution fails loudly rather than falling back to null. Coercing an
 * unusable answer to "no site" would silently unscope every query the
 * resolution feeds, which leaks one site's records into another — a wrong
 * answer that looks like a working page.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Exceptions
 *
 * @since      1.3.0
 */
class SiteResolutionException extends ArtisanPackException
{
}
