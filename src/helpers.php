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
 * @package ArtisanPackUI\Core
 */

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
