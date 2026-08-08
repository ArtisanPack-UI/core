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

		/*
		|--------------------------------------------------------------------------
		| Shared Site Resolution
		|--------------------------------------------------------------------------
		|
		| This is the one place the ArtisanPack UI ecosystem decides which site
		| the current code is running for. Every package that scopes records per
		| site reads this block, so a single request cannot resolve to one site
		| for analytics and a different one for bookings.
		|
		| "enabled" switches automatic resolution on. Left false, no site is ever
		| put in context and every package leaves its queries unscoped, which is
		| what a single-tenant application wants. Code that pins a site
		| deliberately — ArtisanPackSite::forSite() — is unaffected by this flag.
		|
		| "resolvers" lists the strategies, in order; the first one to return an
		| identifier wins. The default asks artisanpack-ui/cms-framework through
		| the "ap.cmsFramework.currentSite.resolve" filter, resolving to null
		| whenever nothing listens on it. If "enabled" is true and
		| artisanpack-ui/hooks is not installed at all, there is no filter to
		| answer and the default throws rather than leaving every query
		| unscoped — remove it from this list in that case. Add your own class
		| here to resolve from a subdomain, a header, or anywhere else; it only
		| needs to implement ArtisanPackUI\Core\Contracts\SiteResolver.
		|
		*/
		'multi_tenant' => [
			'enabled'   => env( 'ARTISANPACK_MULTI_TENANT_ENABLED', false ),
			'resolvers' => [
				ArtisanPackUI\Core\MultiTenancy\HookSiteResolver::class,
			],
		],

	],

	// Add your package-specific overrides here.

];
