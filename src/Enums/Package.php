<?php

/**
 * Enum of ArtisanPack UI packages.
 *
 * @since      1.2.0
 *
 * @package    ArtisanPackUI\Core\Enums
 */

namespace ArtisanPackUI\Core\Enums;

/**
 * Represents the runtime packages that make up the ArtisanPack UI ecosystem
 * and provides helpers for resolving their configuration keys and short names.
 *
 * The development tooling packages ( artisanpack-ui/code-style and
 * artisanpack-ui/code-style-pint ) are intentionally omitted because they ship
 * no runtime configuration and therefore have no configuration key to resolve.
 * Their identifiers remain available as constants on {@see \ArtisanPackUI\Core\Constants}.
 *
 * @since 1.2.0
 *
 * @package ArtisanPackUI\Core\Enums
 */
enum Package: string
{
	/**
	 * Get the configuration key associated with the package.
	 *
	 * @since 1.2.0
	 *
	 * @return string The dot-notation configuration key.
	 */
	public function configKey(): string
	{
		return match ( $this ) {
			self::CORE          => 'artisanpack',
			self::ACCESSIBILITY => 'artisanpack.accessibility',
			self::SECURITY      => 'artisanpack.security',
			self::MEDIA_LIBRARY => 'artisanpack.media-library',
			self::LIVEWIRE_UI   => 'artisanpack.livewire-ui-components',
			self::HOOKS         => 'artisanpack.hooks',
			self::ICONS         => 'artisanpack.icons',
			self::CMS           => 'artisanpack.cms-framework',
		};
	}

	/**
	 * Get the short name of the package without the vendor prefix.
	 *
	 * @since 1.2.0
	 *
	 * @return string The package name without the "artisanpack-ui/" prefix.
	 */
	public function shortName(): string
	{
		return str_replace( 'artisanpack-ui/', '', $this->value );
	}
	case CORE          = 'artisanpack-ui/core';
	case ACCESSIBILITY = 'artisanpack-ui/accessibility';
	case SECURITY      = 'artisanpack-ui/security';
	case MEDIA_LIBRARY = 'artisanpack-ui/media-library';
	case LIVEWIRE_UI   = 'artisanpack-ui/livewire-ui-components';
	case HOOKS         = 'artisanpack-ui/hooks';
	case ICONS         = 'artisanpack-ui/icons';
	case CMS           = 'artisanpack-ui/cms-framework';
}
