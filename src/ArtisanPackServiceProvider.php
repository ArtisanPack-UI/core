<?php

/**
 * Base service provider for ArtisanPack UI packages.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core;

use Illuminate\Support\ServiceProvider;

/**
 * Abstract base service provider that handles the common boilerplate shared by
 * every ArtisanPack UI package.
 *
 * Child providers configure their behaviour declaratively through the protected
 * properties below and implement {@see registerPackageBindings()} for any
 * bindings unique to the package. Optional hooks ({@see bootPackage()}) allow
 * additional boot-time logic without re-implementing the common flow.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core
 *
 * @since      1.2.0
 */
abstract class ArtisanPackServiceProvider extends ServiceProvider
{
    /**
     * Absolute path to the package configuration file to merge and publish.
     *
     * @since 1.2.0
     *
     * @var string|null
     */
    protected ?string $configFile = null;

    /**
     * Configuration key the package config is merged under.
     *
     * @since 1.2.0
     *
     * @var string|null
     */
    protected ?string $configKey = null;

    /**
     * Publish tag used when publishing the configuration file.
     *
     * @since 1.2.0
     *
     * @var string|null
     */
    protected ?string $configPublishTag = null;

    /**
     * Artisan command classes to register when running in the console.
     *
     * @since 1.2.0
     *
     * @var array<int, class-string>
     */
    protected array $commands = [];

    /**
     * Container singletons to register, keyed by abstract.
     *
     * Each value may be a concrete class-string or a closure factory. When the
     * key is a numeric index the value is treated as a self-bound class-string.
     *
     * @since 1.2.0
     *
     * @var array<int|string, callable|class-string>
     */
    protected array $singletons = [];

    /**
     * View namespace used when loading the package's views.
     *
     * @since 1.2.0
     *
     * @var string|null
     */
    protected ?string $viewsNamespace = null;

    /**
     * Absolute path to the package's views directory.
     *
     * @since 1.2.0
     *
     * @var string|null
     */
    protected ?string $viewsPath = null;

    /**
     * Absolute path to the package's routes file.
     *
     * @since 1.2.0
     *
     * @var string|null
     */
    protected ?string $routesPath = null;

    /**
     * Register any package services.
     *
     * @since 1.2.0
     */
    public function register(): void
    {
        $this->registerSingletons();
        $this->mergePackageConfig();
        $this->registerPackageBindings();
    }

    /**
     * Bootstrap any package services.
     *
     * @since 1.2.0
     */
    public function boot(): void
    {
        if ( $this->app->runningInConsole() ) {
            $this->publishPackageAssets();
            $this->registerPackageCommands();
        }

        $this->loadPackageViews();
        $this->loadPackageRoutes();
        $this->bootPackage();
    }

    /**
     * Register the bindings unique to the package.
     *
     * @since 1.2.0
     */
    abstract protected function registerPackageBindings(): void;

    /**
     * Perform additional boot-time logic for the package.
     *
     * Child classes may override this method to register policies, gates,
     * Livewire components, Blade directives, and any other boot behaviour.
     *
     * @since 1.2.0
     */
    protected function bootPackage(): void
    {
        // Override in child classes if needed.
    }

    /**
     * Register the configured container singletons.
     *
     * @since 1.2.0
     */
    protected function registerSingletons(): void
    {
        foreach ( $this->singletons as $abstract => $concrete ) {
            if ( is_int( $abstract ) ) {
                $this->app->singleton( $concrete );

                continue;
            }

            $this->app->singleton( $abstract, $concrete );
        }
    }

    /**
     * Merge the package configuration into the application configuration.
     *
     * @since 1.2.0
     */
    protected function mergePackageConfig(): void
    {
        if ( null === $this->configFile || null === $this->configKey ) {
            return;
        }

        $this->mergeConfigFrom( $this->configFile, $this->configKey );
    }

    /**
     * Publish the package's publishable assets.
     *
     * @since 1.2.0
     */
    protected function publishPackageAssets(): void
    {
        if ( null === $this->configFile || null === $this->configKey ) {
            return;
        }

        $this->publishes( [
            $this->configFile => config_path( $this->configKey . '.php' ),
        ], $this->configPublishTag ?? $this->configKey . '-config' );
    }

    /**
     * Register the package's Artisan commands.
     *
     * @since 1.2.0
     */
    protected function registerPackageCommands(): void
    {
        if ( [] === $this->commands ) {
            return;
        }

        $this->commands( $this->commands );
    }

    /**
     * Load the package's views.
     *
     * @since 1.2.0
     */
    protected function loadPackageViews(): void
    {
        if ( null === $this->viewsPath || null === $this->viewsNamespace ) {
            return;
        }

        $this->loadViewsFrom( $this->viewsPath, $this->viewsNamespace );
    }

    /**
     * Load the package's routes.
     *
     * @since 1.2.0
     */
    protected function loadPackageRoutes(): void
    {
        if ( null === $this->routesPath ) {
            return;
        }

        $this->loadRoutesFrom( $this->routesPath );
    }
}
