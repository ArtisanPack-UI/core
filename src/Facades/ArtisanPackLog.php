<?php

/**
 * Facade for the unified ArtisanPack UI logger factory.
 *
 * @since      1.2.0
 *
 * @package    ArtisanPackUI\Core\Facades
 */

namespace ArtisanPackUI\Core\Facades;

use ArtisanPackUI\Core\Logging\ArtisanPackLogger;
use ArtisanPackUI\Core\Logging\LoggerFactory;

/**
 * Provides static access to the shared {@see LoggerFactory} singleton.
 *
 * @method static ArtisanPackLogger make( string $package )
 * @method static bool has( string $package )
 * @method static void forget( ?string $package = null )
 * @method static array<string, ArtisanPackLogger> all()
 *
 * @see LoggerFactory
 * @since 1.2.0
 *
 * @package ArtisanPackUI\Core\Facades
 */
class ArtisanPackLog extends ArtisanPackFacade
{
	/**
	 * Get the registered name of the component.
	 *
	 * @since 1.2.0
	 *
	 * @return string The container binding key.
	 */
	protected static function getFacadeAccessor(): string
	{
		return LoggerFactory::class;
	}
}
