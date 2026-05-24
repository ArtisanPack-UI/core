<?php

/**
 * Runs registered diagnostic checks and produces a report.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Diagnostics
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Diagnostics;

use ArtisanPackUI\Core\Diagnostics\Checks\ArtisanCommandsCheck;
use ArtisanPackUI\Core\Diagnostics\Checks\ConfigurationCheck;
use ArtisanPackUI\Core\Diagnostics\Checks\DiagnosticCheck;
use ArtisanPackUI\Core\Diagnostics\Checks\EnvironmentCheck;
use ArtisanPackUI\Core\Diagnostics\Checks\InstalledPackagesCheck;
use ArtisanPackUI\Core\Diagnostics\Checks\ServiceBindingsCheck;
use Illuminate\Contracts\Foundation\Application;

/**
 * Orchestrates execution of the registered {@see DiagnosticCheck} suite and
 * collects their results into a {@see DiagnosticReport}.
 *
 * The runner is resolved as a container singleton by
 * {@see \ArtisanPackUI\Core\CoreServiceProvider} and consumed by the
 * `artisanpack:diagnose` command. The default check list covers the
 * environment, installed packages, configuration health, service bindings,
 * and Artisan command registration; tests and extensions can swap the list
 * using {@see setChecks()}.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Diagnostics
 *
 * @since      1.2.0
 */
class DiagnosticRunner
{
    /**
     * The registered diagnostic checks.
     *
     * @since 1.2.0
     *
     * @var array<int, DiagnosticCheck>
     */
    protected array $checks = [];

    /**
     * Create a new diagnostic runner with the default check list.
     *
     * @since 1.2.0
     *
     * @param  Application  $app  The Laravel application used to resolve check dependencies.
     */
    public function __construct( protected Application $app )
    {
        $this->checks = $this->defaultChecks();
    }

    /**
     * Replace the registered checks. Primarily for testing.
     *
     * @since 1.2.0
     *
     * @param  array<int, DiagnosticCheck>  $checks  The replacement check list.
     *
     * @return self For fluent chaining.
     */
    public function setChecks( array $checks ): self
    {
        $this->checks = $checks;

        return $this;
    }

    /**
     * Return the currently registered checks.
     *
     * @since 1.2.0
     *
     * @return array<int, DiagnosticCheck> The active check list.
     */
    public function checks(): array
    {
        return $this->checks;
    }

    /**
     * Run all registered checks, optionally filtered to a single check id.
     *
     * @since 1.2.0
     *
     * @param  string|null  $only  When provided, only the check whose `id()` matches this value runs.
     *
     * @return DiagnosticReport The aggregated report.
     */
    public function run( ?string $only = null ): DiagnosticReport
    {
        $sections = [];

        foreach ( $this->checks as $check ) {
            if ( null !== $only && $check->id() !== $only ) {
                continue;
            }

            $sections[] = [
                'check'   => $check,
                'results' => $check->run(),
            ];
        }

        return new DiagnosticReport( $sections );
    }

    /**
     * Resolve fixable checks. When $only is set, restrict to that single check id.
     *
     * @since 1.2.0
     *
     * @param  string|null  $only  Optional check id used to narrow the result set.
     *
     * @return array<int, DiagnosticCheck> The checks that support auto-fix.
     */
    public function fixableChecks( ?string $only = null ): array
    {
        return array_values( array_filter(
            $this->checks,
            function ( DiagnosticCheck $check ) use ( $only ) {
                if ( ! $check->supportsFix() ) {
                    return false;
                }

                return null === $only || $check->id() === $only;
            },
        ) );
    }

    /**
     * Build the default diagnostic check list.
     *
     * @since 1.2.0
     *
     * @return array<int, DiagnosticCheck> The instantiated default checks.
     */
    protected function defaultChecks(): array
    {
        return [
            new EnvironmentCheck( $this->app ),
            new InstalledPackagesCheck,
            new ConfigurationCheck( $this->app ),
            new ServiceBindingsCheck( $this->app ),
            new ArtisanCommandsCheck( $this->app ),
        ];
    }
}
