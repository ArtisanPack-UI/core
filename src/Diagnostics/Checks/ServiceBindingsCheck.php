<?php

/**
 * Reports the known ArtisanPack UI service-container bindings.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Diagnostics\Checks
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Diagnostics\Checks;

// PHP requires the lowercase __construct magic method; the
// MagicMethodNotUppercase sniff misfires on the constructor here.
// phpcs:disable ArtisanPackUI.TypeHints.TypeDeclaration.MagicMethodNotUppercase

use ArtisanPackUI\Core\Diagnostics\DiagnosticResult;
use ArtisanPackUI\Core\Diagnostics\DiagnosticStatus;
use Illuminate\Contracts\Foundation\Application;
use Throwable;

/**
 * Diagnostic check that walks {@see self::KNOWN_BINDINGS} and confirms that
 * each expected alias resolves out of the container to the expected concrete
 * implementation.
 *
 * Surfacing this in the diagnose report makes service-provider misregistration
 * issues — for example a sibling package's provider being skipped — visible
 * without having to bisect the container manually.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Diagnostics\Checks
 *
 * @since      1.2.0
 */
class ServiceBindingsCheck implements DiagnosticCheck
{
    /**
     * Bindings keyed by alias → expected concrete class.
     *
     * @since 1.2.0
     *
     * @var array<string, string>
     */
    public const KNOWN_BINDINGS = [
        'core' => \ArtisanPackUI\Core\Core::class,
        'a11y' => \ArtisanPack\Accessibility\Accessibility::class,
    ];

    /**
     * Create a new service-bindings check.
     *
     * @since 1.2.0
     *
     * @param  Application  $app  The Laravel application whose container is inspected.
     */
    public function __construct( protected Application $app )
    {
    }

    /**
     * {@inheritDoc}
     */
    public function id(): string
    {
        return 'service-bindings';
    }

    /**
     * {@inheritDoc}
     */
    public function name(): string
    {
        return 'Service Bindings';
    }

    /**
     * {@inheritDoc}
     */
    public function run(): array
    {
        $results = [];

        foreach ( self::KNOWN_BINDINGS as $alias => $concrete ) {
            if ( ! class_exists( $concrete ) && ! interface_exists( $concrete ) ) {
                continue;
            }

            if ( ! $this->app->bound( $alias ) ) {
                $results[] = new DiagnosticResult(
                    label: "{$alias} binding missing",
                    status: DiagnosticStatus::Warning,
                    message: "Expected alias '{$alias}' to resolve to {$concrete}.",
                    details: ['alias' => $alias, 'concrete' => $concrete],
                );

                continue;
            }

            try {
                $resolved = $this->app->make( $alias );
            } catch ( Throwable $exception ) {
                $results[] = new DiagnosticResult(
                    label: "{$alias} failed to resolve",
                    status: DiagnosticStatus::Error,
                    message: $exception->getMessage(),
                    details: [
                        'alias'     => $alias,
                        'concrete'  => $concrete,
                        'exception' => $exception::class,
                    ],
                );

                continue;
            }

            if ( ! $resolved instanceof $concrete ) {
                $results[] = new DiagnosticResult(
                    label: "{$alias} resolved to unexpected type",
                    status: DiagnosticStatus::Error,
                    message: 'Expected ' . $concrete . ', got ' . get_debug_type( $resolved ) . '.',
                    details: [
                        'alias'    => $alias,
                        'expected' => $concrete,
                        'resolved' => get_debug_type( $resolved ),
                    ],
                );

                continue;
            }

            $results[] = new DiagnosticResult(
                label: "{$alias} → {$concrete}",
                status: DiagnosticStatus::Pass,
                details: ['alias' => $alias, 'concrete' => $concrete],
            );
        }

        return $results;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsFix(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function fix( callable $writer ): bool
    {
        return false;
    }
}
