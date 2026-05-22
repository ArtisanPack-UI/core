<?php

use ArtisanPackUI\Core\Enums\LogLevel;
use ArtisanPackUI\Core\Enums\Package;
use ArtisanPackUI\Core\Enums\Priority;
use ArtisanPackUI\Core\Enums\Status;

test( 'package enum cases expose the expected values', function (): void {
	expect( Package::CORE->value )->toBe( 'artisanpack-ui/core' )
		->and( Package::ACCESSIBILITY->value )->toBe( 'artisanpack-ui/accessibility' )
		->and( Package::SECURITY->value )->toBe( 'artisanpack-ui/security' )
		->and( Package::MEDIA_LIBRARY->value )->toBe( 'artisanpack-ui/media-library' )
		->and( Package::LIVEWIRE_UI->value )->toBe( 'artisanpack-ui/livewire-ui-components' )
		->and( Package::HOOKS->value )->toBe( 'artisanpack-ui/hooks' )
		->and( Package::ICONS->value )->toBe( 'artisanpack-ui/icons' )
		->and( Package::CMS->value )->toBe( 'artisanpack-ui/cms-framework' );
} );

test( 'package enum resolves the configuration key', function (): void {
	expect( Package::CORE->configKey() )->toBe( 'artisanpack' )
		->and( Package::ACCESSIBILITY->configKey() )->toBe( 'artisanpack.accessibility' )
		->and( Package::SECURITY->configKey() )->toBe( 'artisanpack.security' )
		->and( Package::MEDIA_LIBRARY->configKey() )->toBe( 'artisanpack.media-library' )
		->and( Package::LIVEWIRE_UI->configKey() )->toBe( 'artisanpack.livewire-ui-components' )
		->and( Package::HOOKS->configKey() )->toBe( 'artisanpack.hooks' )
		->and( Package::ICONS->configKey() )->toBe( 'artisanpack.icons' )
		->and( Package::CMS->configKey() )->toBe( 'artisanpack.cms-framework' );
} );

test( 'package enum resolves the short name', function (): void {
	expect( Package::CORE->shortName() )->toBe( 'core' )
		->and( Package::ACCESSIBILITY->shortName() )->toBe( 'accessibility' )
		->and( Package::MEDIA_LIBRARY->shortName() )->toBe( 'media-library' )
		->and( Package::LIVEWIRE_UI->shortName() )->toBe( 'livewire-ui-components' );
} );

test( 'log level enum cases expose the expected values', function (): void {
	expect( LogLevel::DEBUG->value )->toBe( 'debug' )
		->and( LogLevel::INFO->value )->toBe( 'info' )
		->and( LogLevel::WARNING->value )->toBe( 'warning' )
		->and( LogLevel::ERROR->value )->toBe( 'error' )
		->and( LogLevel::CRITICAL->value )->toBe( 'critical' );
} );

test( 'priority enum cases expose the expected values', function (): void {
	expect( Priority::LOW->value )->toBe( 'low' )
		->and( Priority::MEDIUM->value )->toBe( 'medium' )
		->and( Priority::HIGH->value )->toBe( 'high' )
		->and( Priority::CRITICAL->value )->toBe( 'critical' );
} );

test( 'status enum cases expose the expected values', function (): void {
	expect( Status::PENDING->value )->toBe( 'pending' )
		->and( Status::ACTIVE->value )->toBe( 'active' )
		->and( Status::INACTIVE->value )->toBe( 'inactive' )
		->and( Status::DELETED->value )->toBe( 'deleted' );
} );
