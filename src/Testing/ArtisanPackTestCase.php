<?php

/**
 * Shared base test case for ArtisanPack UI packages.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Testing;

use ArtisanPackUI\Core\CoreServiceProvider;
use ArtisanPackUI\Core\Testing\Concerns\ArtisanPackAssertions;
use ArtisanPackUI\Core\Testing\Concerns\InteractsWithConfiguration;
use ArtisanPackUI\Core\Testing\Concerns\MocksArtisanPackServices;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Common base class for tests in any ArtisanPack UI package.
 *
 * Wires the {@see CoreServiceProvider} into every Orchestra Testbench run so
 * that bindings, helpers, and facades provided by core are available to the
 * package under test. Pulls in the three shared concerns
 * ({@see ArtisanPackAssertions}, {@see InteractsWithConfiguration}, and
 * {@see MocksArtisanPackServices}) so packages can reach for ecosystem-aware
 * assertions and helpers without rediscovering the same patterns.
 *
 * Child classes typically override {@see static::getAdditionalProviders()} to
 * register the package's own service providers without disturbing the core
 * boot sequence. The full provider list is composed in
 * {@see static::getPackageProviders()} by merging core providers first so the
 * package-specific providers can rely on core's container bindings.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core
 *
 * @since      1.2.0
 */
abstract class ArtisanPackTestCase extends Orchestra
{
    use ArtisanPackAssertions;
    use InteractsWithConfiguration;
    use MocksArtisanPackServices;

    /**
     * Get the ArtisanPack UI service providers that should always be loaded.
     *
     * Override this method when a package needs to swap core for a custom
     * provider (e.g. an integration test that exercises a different core
     * implementation). The defaults are sufficient for the vast majority of
     * package test suites.
     *
     * @since 1.2.0
     *
     * @return array<int, class-string> The service provider class names to boot first.
     */
    protected function getArtisanPackProviders(): array
    {
        return [
            CoreServiceProvider::class,
        ];
    }

    /**
     * Get the service providers Orchestra Testbench should register.
     *
     * Combines the always-loaded ArtisanPack UI providers with the
     * package-specific providers returned by
     * {@see static::getAdditionalProviders()}. Core providers are merged in
     * first so the package providers can depend on core bindings.
     *
     * @since 1.2.0
     *
     * @param  \Illuminate\Foundation\Application  $app  The Testbench application instance.
     *
     * @return array<int, class-string> The merged list of providers.
     */
    // The parent Orchestra signature has an untyped $app, so we cannot narrow it here.
    // phpcs:ignore ArtisanPackUI.TypeHints.TypeDeclaration.MissingParameterTypeDeclaration
    protected function getPackageProviders( $app ): array
    {
        return array_merge(
            $this->getArtisanPackProviders(),
            $this->getAdditionalProviders(),
        );
    }

    /**
     * Get the package-specific service providers to register after core.
     *
     * Override this method in the package's test case to register the
     * provider(s) under test. The base implementation returns an empty array
     * so tests that only exercise core utilities do not need to override
     * anything.
     *
     * @since 1.2.0
     *
     * @return array<int, class-string> The package-specific service provider class names.
     */
    protected function getAdditionalProviders(): array
    {
        return [];
    }
}
