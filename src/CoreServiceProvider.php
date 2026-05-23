<?php

namespace ArtisanPackUI\Core;

use ArtisanPackUI\Core\Commands\InstallPackagesCommand;
use ArtisanPackUI\Core\Commands\ScaffoldConfigCommand;
use ArtisanPackUI\Core\Config\ConfigurationManager;
use ArtisanPackUI\Core\Logging\LoggerFactory;
use ArtisanPackUI\Core\View\BladeDirectiveRegistrar;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Log\LogManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

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

        $this->app->singleton( LoggerFactory::class, function ( $app ) {
            return new LoggerFactory(
                $app->make( LogManager::class ),
                $app->make( Dispatcher::class ),
                $app->make( Repository::class ),
            );
        } );

        $this->app->singleton( BladeDirectiveRegistrar::class, function ( $app ) {
            return new BladeDirectiveRegistrar( $app->make( BladeCompiler::class ) );
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
