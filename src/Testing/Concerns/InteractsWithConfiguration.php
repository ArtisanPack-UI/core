<?php

/**
 * Fluent configuration helpers for ArtisanPack UI package tests.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Testing\Concerns;

/**
 * Adds chainable helpers for seeding configuration values inside an
 * Orchestra Testbench application.
 *
 * The trait understands the ArtisanPack UI configuration convention that
 * every package's settings live beneath the shared `artisanpack` namespace
 * keyed by the package's short name ( e.g. `artisanpack.media-library` ), so
 * {@see static::withPackageConfig()} resolves to the right key path without
 * the caller having to remember the prefix.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core
 *
 * @since      1.2.0
 */
trait InteractsWithConfiguration
{
    /**
     * Configuration namespace shared by every ArtisanPack UI package.
     *
     * Kept in sync with
     * {@see \ArtisanPackUI\Core\Config\ConfigurationManager::NAMESPACE}.
     *
     * @since 1.2.0
     *
     * @var string
     */
    protected static string $artisanPackConfigNamespace = 'artisanpack';

    /**
     * Seed top-level configuration values for the current test.
     *
     * Each entry in `$config` is written verbatim against the dotted key it
     * is keyed under, so the helper is suitable for setting values that
     * live outside the ArtisanPack UI namespace.
     *
     * @since 1.2.0
     *
     * @param  array<string, mixed>  $config  Dot-notation keys mapped to their values.
     *
     * @return static The current test instance, to allow fluent chaining.
     */
    protected function withConfig( array $config ): static
    {
        foreach ( $config as $key => $value ) {
            config()->set( $key, $value );
        }

        return $this;
    }

    /**
     * Seed configuration values for a single ArtisanPack UI package.
     *
     * Recursively merges `$config` into any existing values under
     * `artisanpack.{package}`, mirroring the precedence rules used by
     * {@see \ArtisanPackUI\Core\Config\ConfigurationManager::merge()} so test
     * overrides win over previously-registered defaults.
     *
     * @since 1.2.0
     *
     * @param  string                $package  The package short name ( e.g. `media-library` ).
     * @param  array<string, mixed>  $config   The configuration values to merge in.
     *
     * @return static The current test instance, to allow fluent chaining.
     */
    protected function withPackageConfig( string $package, array $config ): static
    {
        $namespace = self::$artisanPackConfigNamespace . '.' . $package;

        $existing = (array) config( $namespace, [] );

        config()->set( $namespace, array_replace_recursive( $existing, $config ) );

        return $this;
    }
}
