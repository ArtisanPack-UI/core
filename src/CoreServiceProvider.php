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
use ArtisanPackUI\Core\Contracts\SiteResolver;
use ArtisanPackUI\Core\Diagnostics\DiagnosticRunner;
use ArtisanPackUI\Core\Exceptions\SiteResolutionException;
use ArtisanPackUI\Core\Logging\LoggerFactory;
use ArtisanPackUI\Core\MultiTenancy\ChainSiteResolver;
use ArtisanPackUI\Core\MultiTenancy\NullSiteResolver;
use ArtisanPackUI\Core\MultiTenancy\SiteContext;
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

        $this->registerSiteResolution();
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

    /**
     * Register the ecosystem's shared site resolution services.
     *
     * The resolver is built from `artisanpack.core.multi_tenant.resolvers`, so
     * every package that scopes data per site reads one configuration surface
     * and cannot disagree with another package about which site a request
     * belongs to. An empty resolver list falls back to `NullSiteResolver`
     * rather than leaving the contract unbound, so consumers can type-hint it
     * unconditionally.
     *
     * Both bindings are `scoped`, not `singleton`, and the distinction matters
     * for correctness rather than tidiness. Laravel forgets scoped instances
     * between Octane requests and between queue jobs, which closes two ways a
     * long-lived worker leaks one site into another's data:
     *
     * - `SiteContext` carries the pinned-site state. A job that pins a site and
     *   never releases it would otherwise scope every later job in that worker
     *   to the same site.
     * - The resolver chain holds built resolver instances, and a resolver may
     *   legitimately depend on the request. A singleton would freeze the first
     *   request's dependencies for the life of the worker, so every later
     *   request would resolve to the first request's site — silently, and
     *   without contradicting the contract's "do not memoise" rule, because the
     *   memoisation would be the container's rather than the implementation's.
     *
     * A resolver list that is not a list at all — `'resolvers' => Foo::class`,
     * the array brackets forgotten — is a configuration fault rather than an
     * opt-out, and is reported as one. Treating it as "no resolvers" would
     * leave every consumer unscoped while the configuration reads as though
     * tenancy were switched on.
     *
     * @since 1.3.0
     *
     * @return void
     */
    protected function registerSiteResolution(): void
    {
        $this->mergeConfigFrom( __DIR__ . '/../config/multi-tenant.php', 'artisanpack.core.multi_tenant' );

        $this->app->scoped( SiteResolver::class, function ( $app ) {
            $resolvers = $app->make( Repository::class )
                ->get( 'artisanpack.core.multi_tenant.resolvers', [] );

            if ( ! is_array( $resolvers ) ) {
                throw new SiteResolutionException( sprintf(
                    'The "artisanpack.core.multi_tenant.resolvers" configuration must be an array of %s'
                        . ' class names, got %s.',
                    SiteResolver::class,
                    get_debug_type( $resolvers ),
                ), 0, null, 'site_resolver.invalid_config' );
            }

            if ( [] === $resolvers ) {
                return new NullSiteResolver;
            }

            return ChainSiteResolver::fromConfig( array_values( $resolvers ), $app );
        } );

        $this->app->scoped( SiteContext::class, function ( $app ) {
            return new SiteContext(
                $app->make( SiteResolver::class ),
                $app->make( Repository::class ),
            );
        } );
    }
}
