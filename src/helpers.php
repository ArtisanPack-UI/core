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
 * @package    ArtisanPack_UI
 * @subpackage Core
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
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
	 * Malformed keys ( missing the package segment, missing the config-key
	 * segment, or empty either side of the dot ) return the supplied default
	 * rather than dispatching a nonsensical lookup against the manager.
	 *
	 * @param string $key     The dot-notation key of '{package}.{config-key}'.
	 * @param mixed  $default The value returned when the key is not set.
	 *
	 * @return mixed The configuration value, or the default when not set or malformed.
	 */
	// Global helpers follow Laravel's snake_case convention ( e.g. config() ).
	// phpcs:ignore ArtisanPackUI.NamingConventions.NamingConventions.NotCamelCase
	function artisanpack_config( string $key, mixed $default = null ): mixed
	{
		$dotPosition = strpos( $key, '.' );

		if ( false === $dotPosition || 0 === $dotPosition || strlen( $key ) - 1 === $dotPosition ) {
			return $default;
		}

		$package   = substr( $key, 0, $dotPosition );
		$configKey = substr( $key, $dotPosition + 1 );

		return app( ArtisanPackUI\Core\Config\ConfigurationManager::class )
			->get( $package, $configKey, $default );
	}
}

if ( ! function_exists( 'artisanpack_blade' ) ) {
	/**
	 * Gets the shared {@see ArtisanPackUI\Core\View\BladeDirectiveRegistrar}.
	 *
	 * The registrar exposes intent-revealing methods for registering common
	 * Blade directive patterns ( echoes, escaped echoes, conditionals, wraps,
	 * Livewire actions, and facade calls ) and replaces the hand-rolled
	 * `Blade::directive()` boilerplate most ArtisanPack UI packages need.
	 *
	 * @since 1.2.0
	 *
	 * @return ArtisanPackUI\Core\View\BladeDirectiveRegistrar The registrar resolved from the container.
	 */
	// Global helpers follow Laravel's snake_case convention ( e.g. config() ).
	// phpcs:ignore ArtisanPackUI.NamingConventions.NamingConventions.NotCamelCase
	function artisanpack_blade(): ArtisanPackUI\Core\View\BladeDirectiveRegistrar
	{
		return app( ArtisanPackUI\Core\View\BladeDirectiveRegistrar::class );
	}
}

if ( ! function_exists( 'artisanpack_supports_laravel_13' ) ) {
	/**
	 * Determines whether the current Laravel runtime is Laravel 13.0 or newer.
	 *
	 * ArtisanPack UI Core keeps supporting Laravel 11 and 12, so any Laravel 13
	 * only API must be guarded behind a runtime check. This helper centralises
	 * that check so consumers do not have to compare framework versions inline.
	 *
	 * Prefer feature detection ( e.g. `method_exists()` ) for individual APIs
	 * when possible; use this helper when the gate is broader than a single
	 * method ( such as documenting that an entire block targets Laravel 13 ).
	 *
	 * @since 1.2.0
	 *
	 * @return bool True when running Laravel 13.0 or newer, false otherwise.
	 */
	// Global helpers follow Laravel's snake_case convention ( e.g. config() ).
	// phpcs:ignore ArtisanPackUI.NamingConventions.NamingConventions.NotCamelCase
	function artisanpack_supports_laravel_13(): bool
	{
		if ( ! class_exists( Illuminate\Foundation\Application::class ) ) {
			return false;
		}

		return version_compare( Illuminate\Foundation\Application::VERSION, '13.0.0', '>=' );
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

if ( ! function_exists( 'apCurrentSiteId' ) ) {
	/**
	 * Gets the identifier of the site the current code is running for.
	 *
	 * Thin wrapper around the shared
	 * ArtisanPackUI\Core\MultiTenancy\SiteContext singleton, which is the one
	 * place a site becomes known across the ArtisanPack UI ecosystem. Returns
	 * null when no site is in context, which consumers read as "do not scope".
	 *
	 * @since 1.3.0
	 *
	 * @return int|string|null The current site identifier, or null when no site
	 *                         is in context.
	 */
	function apCurrentSiteId(): int|string|null
	{
		return app( ArtisanPackUI\Core\MultiTenancy\SiteContext::class )->currentSiteId();
	}
}
