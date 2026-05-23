<?php

/**
 * ArtisanPack UI Core Helper Functions.
 *
 * Provides global helper functions for core functionality.
 * These functions are independent and do not require other ArtisanPack UI packages.
 *
 * Note: Accessibility-related helpers (a11y, a11yCSSVarBlackOrWhite, a11yGetContrastColor,
 * a11yCheckContrastColor, generateAccessibleTextColor) are provided by the
 * artisanpack-ui/accessibility package. Install that package if you need those functions.
 *
 * @since   1.0.0
 *
 * @package ArtisanPackUI\Core
 */

if ( ! function_exists( 'artisanpack_config' ) ) {
	/**
	 * Gets a configuration value for an ArtisanPack UI package.
	 *
	 * Accepts a dot-notation key where the first segment is the package short
	 * name and the remaining segments are the key relative to that package
	 * ( e.g. 'media-library.max_file_size' ). This is a thin wrapper around the
	 * ArtisanPackUI\Core\Config\ConfigurationManager resolved from the service
	 * container.
	 *
	 * @since 1.2.0
	 *
	 * @param string $key     The dot-notation key of '{package}.{config-key}'.
	 * @param mixed  $default The value returned when the key is not set.
	 *
	 * @return mixed The configuration value, or the default when not set.
	 */
	// Global helpers follow Laravel's snake_case convention ( e.g. config() ).
	// phpcs:ignore ArtisanPackUI.NamingConventions.NamingConventions.NotCamelCase
	function artisanpack_config( string $key, mixed $default = null ): mixed
	{
		[ $package, $configKey ] = array_pad( explode( '.', $key, 2 ), 2, '' );

		return app( ArtisanPackUI\Core\Config\ConfigurationManager::class )
			->get( $package, $configKey, $default );
	}
}

if ( ! function_exists( 'getToastDuration' ) ) {
	/**
	 * Gets the configured duration for toast notifications.
	 *
	 * Returns the number of seconds that toast notifications should remain
	 * visible on screen. This can be configured in the application's config
	 * file at 'artisanpack.core.toast_duration'.
	 *
	 * @since 1.0.0
	 *
	 * @return float|int The toast duration in seconds. Defaults to 5 seconds.
	 */
	function getToastDuration(): float|int
	{
		return config( 'artisanpack.core.toast_duration', 5 );
	}
}
