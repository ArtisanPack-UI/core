<?php

/**
 * Facade for the ArtisanPack UI Core service.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Facades
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

namespace ArtisanPackUI\Core\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Provides static access to the {@see \ArtisanPackUI\Core\Core} singleton.
 *
 * @see \ArtisanPackUI\Core\Core
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Facades
 *
 * @since      1.0.0
 */
class Core extends Facade
{
	/**
	 * Get the registered name of the component.
	 *
	 * @since 1.0.0
	 *
	 * @return string The container binding key.
	 */
	protected static function getFacadeAccessor()
	{
		return 'core';
	}
}
