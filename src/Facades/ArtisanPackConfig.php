<?php

/**
 * Facade for the unified ArtisanPack UI configuration manager.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Facades
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Facades;

use ArtisanPackUI\Core\Config\ConfigurationManager;
use ArtisanPackUI\Core\Config\ConfigurationSchema;
use ArtisanPackUI\Core\Config\ValidationResult;

/**
 * Provides static access to the {@see ConfigurationManager} singleton.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Facades
 *
 * @method static mixed get( string $package, string $key, mixed $default = null )
 * @method static void set( string $package, string $key, mixed $value )
 * @method static bool has( string $package, string $key )
 * @method static array merge( string $package, array $config )
 * @method static ValidationResult validate( string $package, array $config )
 * @method static array all()
 * @method static array package( string $package )
 * @method static void registerSchema( string $package, array $schema )
 * @method static ConfigurationSchema|null schema( string $package )
 *
 * @see ConfigurationManager
 * @since      1.2.0
 */
class ArtisanPackConfig extends ArtisanPackFacade
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
		return ConfigurationManager::class;
	}
}
