<?php

/**
 * Confirms expected ArtisanPack UI Artisan commands are registered.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Diagnostics\Checks
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Diagnostics\Checks;

use ArtisanPackUI\Core\Diagnostics\DiagnosticResult;
use ArtisanPackUI\Core\Diagnostics\DiagnosticStatus;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Foundation\Application;

/**
 * Diagnostic check that confirms the Artisan commands ArtisanPack UI Core
 * publishes are actually registered with the console kernel.
 *
 * A missing command typically indicates the {@see \ArtisanPackUI\Core\CoreServiceProvider}
 * is not loaded — most often because the application's `bootstrap/providers.php`
 * was edited without re-running package discovery.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Diagnostics\Checks
 *
 * @since      1.2.0
 */
class ArtisanCommandsCheck implements DiagnosticCheck
{
    /**
     * Commands that should always be registered by the core package.
     *
     * @since 1.2.0
     *
     * @var array<int, string>
     */
    public const REQUIRED_COMMANDS = [
        'artisanpack:scaffold-config',
        'artisanpack:install-packages',
        'artisanpack:diagnose',
    ];

    /**
     * Create a new Artisan commands check.
     *
     * @since 1.2.0
     *
     * @param  Application  $app  The Laravel application used to resolve the console kernel.
     */
    public function __construct( protected Application $app )
    {
    }

    /**
     * {@inheritDoc}
     */
    public function id(): string
    {
        return 'commands';
    }

    /**
     * {@inheritDoc}
     */
    public function name(): string
    {
        return 'Artisan Commands';
    }

    /**
     * {@inheritDoc}
     */
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
