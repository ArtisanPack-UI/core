<?php

/**
 * Container swap helpers for ArtisanPack UI service mocks.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Testing\Concerns;

use ArtisanPackUI\Core\Config\ConfigurationManager;
use ArtisanPackUI\Core\Logging\LoggerFactory;
use ArtisanPackUI\Core\View\BladeDirectiveRegistrar;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;

/**
 * Convenience helpers for replacing common ArtisanPack UI services with
 * test doubles via the application container.
 *
 * The trait intentionally stays small: it wraps `$this->app->instance()` so
 * package tests can swap a concrete service or a Mockery double in a single
 * call, and exposes dedicated shortcuts for the services every package is
 * most likely to need to replace ( the configuration manager, the logger
 * factory, and the Blade directive registrar ).
 *
 * Mockery is bundled with Orchestra Testbench, so the `mockArtisanPackService`
 * helper works out of the box for the typical package test setup. The helper
 * still guards against Mockery being absent so an installer without the
 * dev-dependencies installed gets a clear error message rather than a
 * confusing class-not-found failure.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core
 *
 * @since      1.2.0
 */
trait MocksArtisanPackServices
{
    /**
     * Swap a binding in the container with the given instance.
     *
     * Equivalent to `$this->app->instance( $abstract, $instance )` with a
     * descriptive name so test setup reads as a single intent-revealing line.
     *
     * @since 1.2.0
     *
     * @template T of object
     *
     * @param  string  $abstract  The container binding key to replace.
     * @param  T       $instance  The replacement service instance.
     *
     * @return T The instance that was bound, returned for fluent assignment.
     */
    protected function swapArtisanPackService( string $abstract, object $instance ): object
    {
        $this->app->instance( $abstract, $instance );

        return $instance;
    }

    /**
     * Create a Mockery double for an ArtisanPack UI service and bind it.
     *
     * The optional `$expectations` closure receives the mock so callers can
     * configure expectations inline:
     *
     * ```php
     * $this->mockArtisanPackService( LoggerFactory::class, function ( MockInterface $mock ) {
     *     $mock->shouldReceive( 'make' )->andReturn( new NullLogger() );
     * } );
     * ```
     *
     * @since 1.2.0
     *
     * @param  string         $abstract      The container binding key to replace.
     * @param  callable|null  $expectations  Optional `function ( MockInterface $mock ): void` callback.
     *
     * @throws RuntimeException When the Mockery library is not installed.
     *
     * @return MockInterface The configured Mockery double, also bound into the container.
     */
    protected function mockArtisanPackService( string $abstract, ?callable $expectations = null ): MockInterface
    {
        if ( ! class_exists( Mockery::class ) ) {
            throw new RuntimeException(
                'The mockArtisanPackService() helper requires the mockery/mockery package. ' .
                'Install it as a dev dependency to use this helper.',
            );
        }

        $mock = Mockery::mock( $abstract );

        if ( null !== $expectations ) {
            $expectations( $mock );
        }

        $this->app->instance( $abstract, $mock );

        return $mock;
    }

    /**
     * Replace the bound {@see ConfigurationManager} with the given instance.
     *
     * @since 1.2.0
     *
     * @param  ConfigurationManager  $manager  The replacement configuration manager.
     *
     * @return ConfigurationManager The instance that was bound.
     */
    protected function swapConfigurationManager( ConfigurationManager $manager ): ConfigurationManager
    {
        return $this->swapArtisanPackService( ConfigurationManager::class, $manager );
    }

    /**
     * Replace the bound {@see LoggerFactory} with the given instance.
     *
     * @since 1.2.0
     *
     * @param  LoggerFactory  $factory  The replacement logger factory.
     *
     * @return LoggerFactory The instance that was bound.
     */
    protected function swapLoggerFactory( LoggerFactory $factory ): LoggerFactory
    {
        return $this->swapArtisanPackService( LoggerFactory::class, $factory );
    }

    /**
     * Replace the bound {@see BladeDirectiveRegistrar} with the given instance.
     *
     * @since 1.2.0
     *
     * @param  BladeDirectiveRegistrar  $registrar  The replacement Blade directive registrar.
     *
     * @return BladeDirectiveRegistrar The instance that was bound.
     */
    protected function swapBladeDirectiveRegistrar( BladeDirectiveRegistrar $registrar ): BladeDirectiveRegistrar
    {
        return $this->swapArtisanPackService( BladeDirectiveRegistrar::class, $registrar );
    }
}
