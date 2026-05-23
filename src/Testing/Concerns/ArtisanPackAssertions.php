<?php

/**
 * Shared assertions for ArtisanPack UI package test suites.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Testing\Concerns;

use Composer\InstalledVersions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Facade;
use Throwable;

/**
 * Provides assertion methods that express expectations in ArtisanPack UI
 * terms ( installed packages, configuration values, Artisan commands,
 * facades, and helpers ) so tests across the ecosystem read consistently and
 * fail with messages tied to the concept being verified rather than the
 * underlying primitive.
 *
 * Designed to be mixed into a PHPUnit-compatible test case ( typically the
 * shared {@see \ArtisanPackUI\Core\Testing\ArtisanPackTestCase} ). The trait
 * delegates to the host class's existing `assertTrue()`, `assertEquals()`,
 * and `assertArrayHasKey()` methods, so it works against any test case that
 * extends `PHPUnit\Framework\TestCase`.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core
 *
 * @since      1.2.0
 */
trait ArtisanPackAssertions
{
    /**
     * Assert that a composer package is installed.
     *
     * Uses Composer's `InstalledVersions` runtime API so the check passes for
     * any package the autoloader currently knows about, including packages
     * installed as path repositories during local development.
     *
     * @since 1.2.0
     *
     * @param  string  $package  The composer package name ( e.g. `artisanpack-ui/core` ).
     *
     * @return void
     */
    public function assertPackageInstalled( string $package ): void
    {
        $this->assertTrue(
            InstalledVersions::isInstalled( $package ),
            sprintf( 'Failed asserting that composer package [%s] is installed.', $package ),
        );
    }

    /**
     * Assert that a configuration value matches the expected value.
     *
     * @since 1.2.0
     *
     * @param  string  $key       The dot-notation configuration key.
     * @param  mixed   $expected  The expected value at that key.
     *
     * @return void
     */
    public function assertConfigEquals( string $key, mixed $expected ): void
    {
        $this->assertSame(
            $expected,
            config( $key ),
            sprintf( 'Failed asserting that config [%s] equals the expected value.', $key ),
        );
    }

    /**
     * Assert that an Artisan command is registered with the application.
     *
     * @since 1.2.0
     *
     * @param  string  $command  The signature name of the command ( e.g. `artisanpack:install` ).
     *
     * @return void
     */
    public function assertCommandExists( string $command ): void
    {
        $this->assertArrayHasKey(
            $command,
            Artisan::all(),
            sprintf( 'Failed asserting that Artisan command [%s] is registered.', $command ),
        );
    }

    /**
     * Assert that a facade is wired up and resolves a concrete instance.
     *
     * Confirms that the given class exists, is a subclass of Laravel's
     * {@see Facade}, and can resolve its underlying service through the
     * application container without throwing. Any exception thrown during
     * resolution is converted into an assertion failure with the original
     * message so developers can see exactly why the facade is misconfigured.
     *
     * @since 1.2.0
     *
     * @param  class-string  $facadeClass  The fully-qualified facade class name.
     *
     * @return void
     */
    public function assertFacadeWorks( string $facadeClass ): void
    {
        $this->assertTrue(
            class_exists( $facadeClass ),
            sprintf( 'Failed asserting that facade class [%s] exists.', $facadeClass ),
        );

        $this->assertTrue(
            is_subclass_of( $facadeClass, Facade::class ),
            sprintf( 'Failed asserting that [%s] extends Illuminate\\Support\\Facades\\Facade.', $facadeClass ),
        );

        try {
            $root = $facadeClass::getFacadeRoot();
        } catch ( Throwable $e ) {
            $this->fail( sprintf(
                'Facade [%s] could not resolve its underlying service: %s',
                $facadeClass,
                $e->getMessage(),
            ) );
        }

        $this->assertNotNull(
            $root,
            sprintf( 'Facade [%s] resolved a null instance.', $facadeClass ),
        );
    }

    /**
     * Assert that a global helper function is defined.
     *
     * @since 1.2.0
     *
     * @param  string  $functionName  The fully-qualified function name.
     *
     * @return void
     */
    public function assertHelperExists( string $functionName ): void
    {
        $this->assertTrue(
            function_exists( $functionName ),
            sprintf( 'Failed asserting that helper function [%s] exists.', $functionName ),
        );
    }
}
