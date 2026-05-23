<?php

/**
 * Facade for the unified ArtisanPack UI configuration manager.
 *
 * @since      1.2.0
 *
 * @package    ArtisanPackUI\Core\Facades
 */

namespace ArtisanPackUI\Core\Facades;

use ArtisanPackUI\Core\Config\ConfigurationManager;
use ArtisanPackUI\Core\Config\ConfigurationSchema;
use ArtisanPackUI\Core\Config\ValidationResult;
use Illuminate\Support\Facades\Facade;

/**
 * Provides static access to the {@see ConfigurationManager} singleton.
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
 * @since 1.2.0
 *
 * @package ArtisanPackUI\Core\Facades
 */
class ArtisanPackConfig extends Facade
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
