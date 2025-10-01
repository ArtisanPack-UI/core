<?php

namespace ArtisanPackUI\Core;

use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{

	public function register(): void
	{
		$this->app->singleton( 'core', function ( $app ) {
			return new Core();
		} );
	}
}
