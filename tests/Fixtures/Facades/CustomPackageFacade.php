<?php

/**
 * ArtisanPackFacade subclass with a custom package name.
 *
 * @package Tests\Fixtures\Facades
 */

namespace Tests\Fixtures\Facades;

use ArtisanPackUI\Core\Facades\ArtisanPackFacade;

/**
 * Test facade that overrides {@see ArtisanPackFacade::getPackageName()} so
 * the resulting ServiceException references a non-default composer package.
 *
 * @package Tests\Fixtures\Facades
 */
class CustomPackageFacade extends ArtisanPackFacade
{
	/**
	 * Get the container binding key for the facade's underlying service.
	 *
	 * @return string The accessor key bound by the test.
	 */
	protected static function getFacadeAccessor(): string
	{
		return 'custom-package-service';
	}

	/**
	 * Get the composer package name surfaced in service-missing errors.
	 *
	 * @return string A non-default package name used to assert that the
	 *                override is reflected in the exception message.
	 */
	protected static function getPackageName(): string
	{
		return 'artisanpack-ui/custom-package';
	}
}
