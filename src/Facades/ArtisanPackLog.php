<?php

/**
 * Facade for the unified ArtisanPack UI logger factory.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Facades
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Facades;

use ArtisanPackUI\Core\Logging\ArtisanPackLogger;
use ArtisanPackUI\Core\Logging\LoggerFactory;

/**
 * Provides static access to the shared {@see LoggerFactory} singleton.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Facades
 *
 * @method static ArtisanPackLogger make( string $package )
 * @method static bool has( string $package )
 * @method static void forget( ?string $package = null )
 * @method static array<string, ArtisanPackLogger> all()
 *
 * @see LoggerFactory
 * @since      1.2.0
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
