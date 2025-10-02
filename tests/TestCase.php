<?php

namespace Tests;

use ArtisanPackUI\Core\CoreServiceProvider;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
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
