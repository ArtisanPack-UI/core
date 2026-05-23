<?php

namespace ArtisanPackUI\Core;

use ArtisanPackUI\Core\Commands\InstallPackagesCommand;
use ArtisanPackUI\Core\Commands\ScaffoldConfigCommand;
use ArtisanPackUI\Core\Config\ConfigurationManager;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton( 'core', function ( $app ) {
            return new Core;
        } );

        $this->app->singleton( ConfigurationManager::class, function ( $app ) {
            return new ConfigurationManager( $app->make( Repository::class ) );
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
            InstallPackagesCommand::class,
        ] );
    }
}
