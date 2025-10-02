<?php

namespace ArtisanPackUI\Core;

use ArtisanPackUI\Core\Commands\ScaffoldConfigCommand;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{

	public function register(): void
	{
		$this->app->singleton( 'core', function ( $app ) {
			return new Core();
		} );
	}

	public function boot(): void
	{
		if ( $this->app->runningInConsole() ) {
			$this->publishes( [
								  __DIR__ . '/../config/artisanpack.php' => config_path( 'artisanpack.php' ),
							  ], 'artisanpack-config' );
		}

		$this->commands( [
							 ScaffoldConfigCommand::class,
						 ] );
	}
}
