<?php

/**
 * ArtisanPack UI Unified Configuration.
 *
 * This file is published to your application's config directory, allowing you
 * to customize the settings for all installed ArtisanPack UI packages from a
 * single location.
 *
 * To override default settings for a specific package, add a key with the
 * package's name (e.g., 'cms-framework', 'visual-editor') and define your
 * custom values within it.
 *
 * Example:
 *
 * 'cms-framework' => [
 * 'settings' => [
 * 'site_title' => 'My Awesome Site',
 * ],
 * ],
 * 'visual-editor' => [
 * 'autosave_interval' => 300,
 * ],
 *
 */
return [

	'core' => [

		/*
		|--------------------------------------------------------------------------
		| Shared Logging
		|--------------------------------------------------------------------------
		|
		| Controls how every ArtisanPack UI package routes its log messages and
		| audit entries. Setting "channel" to a value other than null forwards
		| every entry through a dedicated Laravel log channel ( the channel must
		| exist in your application's config/logging.php ). Leaving it null falls
		| back to Laravel's default log channel.
		|
		| Audit entries are written at info level with an "[AUDIT]" prefix and an
		| accompanying AuditLogCreated event is dispatched so applications can
		| persist them to a database, forward them to an external service, or
		| trigger notifications.
		|
		*/
		'logging' => [
			'channel' => env( 'ARTISANPACK_LOG_CHANNEL' ),
			'audit'   => [
				'enabled' => env( 'ARTISANPACK_AUDIT_ENABLED', true ),
			],
		],

	],

	// Add your package-specific overrides here.

];
