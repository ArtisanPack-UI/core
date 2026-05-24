<?php

/**
 * Service provider for the ArtisanPack UI Core package.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.0.0
 */

namespace ArtisanPackUI\Core;

use ArtisanPackUI\Core\Commands\CheckCompatibilityCommand;
use ArtisanPackUI\Core\Commands\CheckUpdatesCommand;
use ArtisanPackUI\Core\Commands\DiagnoseCommand;
use ArtisanPackUI\Core\Commands\InstallPackagesCommand;
use ArtisanPackUI\Core\Commands\MakePackageCommand;
use ArtisanPackUI\Core\Commands\ScaffoldConfigCommand;
use ArtisanPackUI\Core\Compatibility\CompatibilityChecker;
use ArtisanPackUI\Core\Config\ConfigurationManager;
use ArtisanPackUI\Core\Diagnostics\DiagnosticRunner;
use ArtisanPackUI\Core\Logging\LoggerFactory;
use ArtisanPackUI\Core\Updates\PackagistClient;
use ArtisanPackUI\Core\Updates\UpdateChecker;
use ArtisanPackUI\Core\View\BladeDirectiveRegistrar;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Log\LogManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

/**
 * Registers every shared service the ArtisanPack UI ecosystem relies on.
 *
 * Wires the container singletons for configuration management, logging,
 * Blade directive registration, environment diagnostics, cross-package
 * compatibility analysis, and Packagist-backed update checking. Also
 * registers the package-management Artisan commands and publishes the
 * unified `config/artisanpack.php` config stub.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core
 *
 * @since      1.0.0
 */
class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register container bindings.
     *
     * @since 1.0.0
     *
     * @return void
     */
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

        $this->app->singleton( DiagnosticRunner::class, function ( $app ) {
            return new DiagnosticRunner( $app );
        } );

        $this->app->singleton( CompatibilityChecker::class, function ( $app ) {
            return new CompatibilityChecker( $app );
        } );

        $this->app->singleton( PackagistClient::class, function ( $app ) {
            return new PackagistClient(
                $app->make( HttpFactory::class ),
                $app->make( CacheRepository::class ),
                $app->make( Repository::class ),
            );
        } );

        $this->app->singleton( UpdateChecker::class, function ( $app ) {
            return new UpdateChecker( $app->make( PackagistClient::class ) );
        } );
    }

    /**
     * Bootstrap package services.
     *
     * Publishes the unified configuration stub and registers the
     * package-management Artisan commands.
     *
     * @since 1.0.0
     *
     * @return void
     */
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
            DiagnoseCommand::class,
            CheckCompatibilityCommand::class,
            CheckUpdatesCommand::class,
            MakePackageCommand::class,
        ] );
    }
}
