<?php

/**
 * Shared site resolution defaults.
 *
 * Core publishes `config/artisanpack.php` rather than merging it, so an
 * application that never runs `vendor:publish --tag=artisanpack-config` has no
 * `artisanpack.core.multi_tenant` key at all. These defaults are merged in at
 * that key by CoreServiceProvider so the block behaves the same either way —
 * in particular so ARTISANPACK_MULTI_TENANT_ENABLED in an unpublished
 * application still switches site resolution on. Without this, an operator
 * could set that variable, see nothing happen, and run a multi-site install
 * with every query unscoped.
 *
 * Keep these values in step with the `multi_tenant` block of
 * `config/artisanpack.php`, which is what an application actually edits once it
 * publishes.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.3.0
 */

return [

	'enabled' => env( 'ARTISANPACK_MULTI_TENANT_ENABLED', false ),

	'resolvers' => [
		ArtisanPackUI\Core\MultiTenancy\HookSiteResolver::class,
	],

];
