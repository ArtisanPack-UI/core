<?php

/**
 * Centralized constants shared across the ArtisanPack UI ecosystem.
 *
 * @since      1.2.0
 *
 * @package    ArtisanPackUI\Core
 */

namespace ArtisanPackUI\Core;

/**
 * Provides a single source of truth for package identifiers, configuration
 * keys, common default values, and version requirements used throughout the
 * ArtisanPack UI packages.
 *
 * @since 1.2.0
 *
 * @package ArtisanPackUI\Core
 */
final class Constants
{
	/**
	 * Package identifier for the core package.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	public const PACKAGE_CORE = 'artisanpack-ui/core';

	/**
	 * Package identifier for the accessibility package.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	public const PACKAGE_ACCESSIBILITY = 'artisanpack-ui/accessibility';

	/**
	 * Package identifier for the security package.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	public const PACKAGE_SECURITY = 'artisanpack-ui/security';

	/**
	 * Package identifier for the media library package.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	public const PACKAGE_MEDIA_LIBRARY = 'artisanpack-ui/media-library';

	/**
	 * Package identifier for the Livewire UI components package.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	public const PACKAGE_LIVEWIRE_UI = 'artisanpack-ui/livewire-ui-components';

	/**
	 * Package identifier for the hooks package.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	public const PACKAGE_HOOKS = 'artisanpack-ui/hooks';

	/**
	 * Package identifier for the icons package.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	public const PACKAGE_ICONS = 'artisanpack-ui/icons';

	/**
	 * Package identifier for the CMS framework package.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	public const PACKAGE_CMS = 'artisanpack-ui/cms-framework';

	/**
	 * Package identifier for the code style package.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	public const PACKAGE_CODE_STYLE = 'artisanpack-ui/code-style';

	/**
	 * Package identifier for the code style Pint package.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	public const PACKAGE_CODE_STYLE_PINT = 'artisanpack-ui/code-style-pint';

	/**
	 * Root configuration key for the ArtisanPack UI ecosystem.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	public const CONFIG_KEY = 'artisanpack';

	/**
	 * Configuration key for the accessibility package.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	public const CONFIG_KEY_ACCESSIBILITY = 'artisanpack.accessibility';

	/**
	 * Configuration key for the security package.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	public const CONFIG_KEY_SECURITY = 'artisanpack.security';

	/**
	 * Configuration key for the media library package.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	public const CONFIG_KEY_MEDIA = 'artisanpack.media-library';

	/**
	 * Configuration key for the Livewire UI components package.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	public const CONFIG_KEY_LIVEWIRE_UI = 'artisanpack.livewire-ui-components';

	/**
	 * Configuration key for the hooks package.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	public const CONFIG_KEY_HOOKS = 'artisanpack.hooks';

	/**
	 * Configuration key for the icons package.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	public const CONFIG_KEY_ICONS = 'artisanpack.icons';

	/**
	 * Configuration key for the CMS framework package.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	public const CONFIG_KEY_CMS = 'artisanpack.cms-framework';

	/**
	 * Default toast notification duration in milliseconds.
	 *
	 * @since 1.2.0
	 *
	 * @var int
	 */
	public const DEFAULT_TOAST_DURATION = 3000;

	/**
	 * Default pagination page size.
	 *
	 * @since 1.2.0
	 *
	 * @var int
	 */
	public const DEFAULT_PAGINATION_SIZE = 15;

	/**
	 * Default maximum file size in kilobytes ( 10MB ).
	 *
	 * @since 1.2.0
	 *
	 * @var int
	 */
	public const DEFAULT_MAX_FILE_SIZE = 10240;

	/**
	 * Minimum supported PHP version.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	public const MIN_PHP_VERSION = '8.2.0';

	/**
	 * Minimum supported Laravel version.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	public const MIN_LARAVEL_VERSION = '10.0.0';
}
