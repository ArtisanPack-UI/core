<?php

/**
 * Runs registered diagnostic checks and produces a report.
 *
 * @since 1.2.0
 */

namespace ArtisanPackUI\Core\Diagnostics;

use ArtisanPackUI\Core\Diagnostics\Checks\ArtisanCommandsCheck;
use ArtisanPackUI\Core\Diagnostics\Checks\ConfigurationCheck;
use ArtisanPackUI\Core\Diagnostics\Checks\DiagnosticCheck;
use ArtisanPackUI\Core\Diagnostics\Checks\EnvironmentCheck;
use ArtisanPackUI\Core\Diagnostics\Checks\InstalledPackagesCheck;
use ArtisanPackUI\Core\Diagnostics\Checks\ServiceBindingsCheck;
use Illuminate\Contracts\Foundation\Application;

class DiagnosticRunner
{
    /**
     * @var array<int, DiagnosticCheck>
     */
    protected array $checks = [];

    public function __construct( protected Application $app )
    {
        $this->checks = $this->defaultChecks();
    }

    /**
     * Replace the registered checks. Primarily for testing.
     *
     * @param  array<int, DiagnosticCheck>  $checks
     */
    public function setChecks( array $checks ): self
    {
        $this->checks = $checks;

        return $this;
    }

    /**
     * @return array<int, DiagnosticCheck>
     */
    public function checks(): array
    {
        return $this->checks;
    }

    /**
     * Run all registered checks, optionally filtered to a single check id.
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
     * @return array<int, DiagnosticCheck>
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
     * @return array<int, DiagnosticCheck>
     */
    protected function defaultChecks(): array
    {
        return [
            new EnvironmentCheck( $this->app ),
            new InstalledPackagesCheck,
            new ConfigurationCheck( $this->app ),
            new ServiceBindingsCheck( $this->app ),
            new ArtisanCommandsCheck( $this->app),
        ];
    }
}
