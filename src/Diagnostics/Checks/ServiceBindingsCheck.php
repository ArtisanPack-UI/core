<?php

/**
 * Reports the known ArtisanPack UI service-container bindings.
 *
 * @since 1.2.0
 */

namespace ArtisanPackUI\Core\Diagnostics\Checks;

use ArtisanPackUI\Core\Diagnostics\DiagnosticResult;
use ArtisanPackUI\Core\Diagnostics\DiagnosticStatus;
use Illuminate\Contracts\Foundation\Application;
use Throwable;

class ServiceBindingsCheck implements DiagnosticCheck
{
    /**
     * Bindings keyed by alias → expected concrete class.
     *
     * @var array<string, string>
     */
    public const KNOWN_BINDINGS = [
        'core' => \ArtisanPackUI\Core\Core::class,
        'a11y' => \ArtisanPack\Accessibility\Accessibility::class,
    ];

    public function __construct( protected Application $app )
    {
    }

    public function id(): string
    {
        return 'service-bindings';
    }

    public function name(): string
    {
        return 'Service Bindings';
    }

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

    public function supportsFix(): bool
    {
        return false;
    }

    public function fix( callable $writer ): bool
    {
        return false;
    }
}
