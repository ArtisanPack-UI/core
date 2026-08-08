<?php

use ArtisanPackUI\Core\Constants;
use ArtisanPackUI\Core\Enums\Package;

test( 'package identifier constants expose the expected values', function (): void {
	expect( Constants::PACKAGE_CORE )->toBe( 'artisanpack-ui/core' )
		->and( Constants::PACKAGE_ACCESSIBILITY )->toBe( 'artisanpack-ui/accessibility' )
		->and( Constants::PACKAGE_SECURITY )->toBe( 'artisanpack-ui/security' )
		->and( Constants::PACKAGE_MEDIA_LIBRARY )->toBe( 'artisanpack-ui/media-library' )
		->and( Constants::PACKAGE_LIVEWIRE_UI )->toBe( 'artisanpack-ui/livewire-ui-components' )
		->and( Constants::PACKAGE_HOOKS )->toBe( 'artisanpack-ui/hooks' )
		->and( Constants::PACKAGE_ICONS )->toBe( 'artisanpack-ui/icons' )
		->and( Constants::PACKAGE_CMS )->toBe( 'artisanpack-ui/cms-framework' )
		->and( Constants::PACKAGE_CODE_STYLE )->toBe( 'artisanpack-ui/code-style' )
		->and( Constants::PACKAGE_CODE_STYLE_PINT )->toBe( 'artisanpack-ui/code-style-pint' );
} );

test( 'configuration key constants expose the expected values', function (): void {
	expect( Constants::CONFIG_KEY )->toBe( 'artisanpack' )
		->and( Constants::CONFIG_KEY_ACCESSIBILITY )->toBe( 'artisanpack.accessibility' )
		->and( Constants::CONFIG_KEY_SECURITY )->toBe( 'artisanpack.security' )
		->and( Constants::CONFIG_KEY_MEDIA )->toBe( 'artisanpack.media-library' )
		->and( Constants::CONFIG_KEY_LIVEWIRE_UI )->toBe( 'artisanpack.livewire-ui-components' )
		->and( Constants::CONFIG_KEY_HOOKS )->toBe( 'artisanpack.hooks' )
		->and( Constants::CONFIG_KEY_ICONS )->toBe( 'artisanpack.icons' )
		->and( Constants::CONFIG_KEY_CMS )->toBe( 'artisanpack.cms-framework' );
} );

test( 'configuration key constants align with the package enum', function (): void {
	expect( Constants::CONFIG_KEY )->toBe( Package::CORE->configKey() )
		->and( Constants::CONFIG_KEY_ACCESSIBILITY )->toBe( Package::ACCESSIBILITY->configKey() )
		->and( Constants::CONFIG_KEY_SECURITY )->toBe( Package::SECURITY->configKey() )
		->and( Constants::CONFIG_KEY_MEDIA )->toBe( Package::MEDIA_LIBRARY->configKey() )
		->and( Constants::CONFIG_KEY_LIVEWIRE_UI )->toBe( Package::LIVEWIRE_UI->configKey() )
		->and( Constants::CONFIG_KEY_HOOKS )->toBe( Package::HOOKS->configKey() )
		->and( Constants::CONFIG_KEY_ICONS )->toBe( Package::ICONS->configKey() )
		->and( Constants::CONFIG_KEY_CMS )->toBe( Package::CMS->configKey() );
} );

test( 'default value constants expose the expected values', function (): void {
	expect( Constants::DEFAULT_TOAST_DURATION )->toBe( 3000 )
		->and( Constants::DEFAULT_PAGINATION_SIZE )->toBe( 15 )
		->and( Constants::DEFAULT_MAX_FILE_SIZE )->toBe( 10240 );
} );

test( 'version requirement constants expose the expected values', function (): void {
	expect( Constants::MIN_PHP_VERSION )->toBe( '8.2.0' )
		->and( Constants::MIN_LARAVEL_VERSION )->toBe( '12.0.0' );
} );
