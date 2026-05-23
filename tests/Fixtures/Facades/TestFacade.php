<?php

namespace Tests\Fixtures\Facades;

use ArtisanPackUI\Core\Facades\ArtisanPackFacade;

class TestFacade extends ArtisanPackFacade
{
	protected static function getFacadeAccessor(): string
	{
		return 'test-facade-service';
	}
}
