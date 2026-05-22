<?php

namespace Tests;

use ArtisanPackUI\Core\CoreServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
	protected function getPackageProviders( $app )
	{
		return [
			CoreServiceProvider::class,
		];
	}
}
