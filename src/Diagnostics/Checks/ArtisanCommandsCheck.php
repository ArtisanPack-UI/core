<?php

/**
 * Confirms expected ArtisanPack UI Artisan commands are registered.
 *
 * @since 1.2.0
 */

namespace ArtisanPackUI\Core\Diagnostics\Checks;

use ArtisanPackUI\Core\Diagnostics\DiagnosticResult;
use ArtisanPackUI\Core\Diagnostics\DiagnosticStatus;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Foundation\Application;

class ArtisanCommandsCheck implements DiagnosticCheck
{
    /**
     * Commands that should always be registered by the core package.
     *
     * @var array<int, string>
     */
    public const REQUIRED_COMMANDS = [
        'artisanpack:scaffold-config',
        'artisanpack:install-packages',
        'artisanpack:diagnose',
    ];

    public function __construct( protected Application $app )
    {
    }

    public function id(): string
    {
        return 'commands';
    }

    public function name(): string
    {
        return 'Artisan Commands';
    }

    public function run(): array
    {
        $registered = array_keys( $this->app->make( Kernel::class )->all() );
        $results    = [];

        foreach ( self::REQUIRED_COMMANDS as $command ) {
            $results[] = new DiagnosticResult(
                label: $command,
                status: in_array( $command, $registered, true )
                    ? DiagnosticStatus::Pass
                    : DiagnosticStatus::Error,
                details: ['command' => $command],
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
