<?php

namespace Tests\Fixtures\Facades;

use ArtisanPackUI\Core\Facades\ArtisanPackFacade;

class CustomPackageFacade extends ArtisanPackFacade
{
	protected static function getFacadeAccessor(): string
	{
		return 'custom-package-service';
	}

	protected static function getPackageName(): string
	{
		return 'artisanpack-ui/custom-package';
	}
}
