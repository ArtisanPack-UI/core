<?php

/**
 * Default ArtisanPackFacade subclass used by feature tests.
 *
 * @package Tests\Fixtures\Facades
 */

namespace Tests\Fixtures\Facades;

use ArtisanPackUI\Core\Facades\ArtisanPackFacade;

/**
 * Test facade pointing at the "test-facade-service" binding and relying on
 * the default {@see ArtisanPackFacade::getPackageName()} of "artisanpack-ui/core".
 *
 * @package Tests\Fixtures\Facades
 */
class TestFacade extends ArtisanPackFacade
{
	/**
	 * Get the container binding key for the facade's underlying service.
	 *
	 * @return string The accessor key bound by the test.
	 */
	protected static function getFacadeAccessor(): string
	{
		return 'test-facade-service';
	}
}
