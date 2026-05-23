<?php

/**
 * Verifies the PHP/Laravel/Livewire environment meets ArtisanPack UI minimums.
 *
 * @since 1.2.0
 */

namespace ArtisanPackUI\Core\Diagnostics\Checks;

use ArtisanPackUI\Core\Diagnostics\DiagnosticResult;
use ArtisanPackUI\Core\Diagnostics\DiagnosticStatus;
use ArtisanPackUI\Core\Diagnostics\Support\ComposerLock;
use Illuminate\Contracts\Foundation\Application;

class EnvironmentCheck implements DiagnosticCheck
{
    public const MIN_PHP_VERSION = '8.2.0';

    public const MIN_LARAVEL_VERSION = '10.0.0';

    /**
     * PHP extensions required by Laravel core that ArtisanPack UI also relies on.
     *
     * @var array<int, string>
     */
    public const REQUIRED_EXTENSIONS = [
        'ctype',
        'json',
        'mbstring',
        'openssl',
        'pdo',
        'tokenizer',
    ];

    public function __construct( protected Application $app )
    {
    }

    public function id(): string
    {
        return 'environment';
    }

    public function name(): string
    {
        return 'Environment';
    }

    public function run(): array
    {
        $results = [];

        $phpVersion = PHP_VERSION;
        $results[]  = new DiagnosticResult(
            label: "PHP Version: {$phpVersion}",
            status: version_compare( $phpVersion, self::MIN_PHP_VERSION, '>=' )
                ? DiagnosticStatus::Pass
                : DiagnosticStatus::Error,
            message: 'minimum: ' . self::MIN_PHP_VERSION,
            details: ['current' => $phpVersion, 'minimum' => self::MIN_PHP_VERSION],
        );

        $laravelVersion = $this->app->version();
        $results[]      = new DiagnosticResult(
            label: "Laravel Version: {$laravelVersion}",
            status: version_compare( $laravelVersion, self::MIN_LARAVEL_VERSION, '>=' )
                ? DiagnosticStatus::Pass
                : DiagnosticStatus::Error,
            message: 'minimum: ' . self::MIN_LARAVEL_VERSION,
            details: ['current' => $laravelVersion, 'minimum' => self::MIN_LARAVEL_VERSION],
        );

        $livewireVersion = $this->detectInstalledVersion( 'livewire/livewire' );
        if ( null !== $livewireVersion ) {
            $results[] = new DiagnosticResult(
                label: "Livewire Version: {$livewireVersion}",
                status: DiagnosticStatus::Pass,
                details: ['current' => $livewireVersion],
            );
        }

        $missingExtensions = array_values( array_filter(
            self::REQUIRED_EXTENSIONS,
            fn ( string $extension ) => ! extension_loaded( $extension ),
        ) );

        if ( empty( $missingExtensions ) ) {
            $results[] = new DiagnosticResult(
                label: 'PHP Extensions: ' . implode( ', ', self::REQUIRED_EXTENSIONS ),
                status: DiagnosticStatus::Pass,
                details: ['required' => self::REQUIRED_EXTENSIONS, 'missing' => []],
            );
        } else {
            $results[] = new DiagnosticResult(
                label: 'PHP Extensions missing: ' . implode( ', ', $missingExtensions ),
                status: DiagnosticStatus::Error,
                message: 'Install the missing PHP extensions before running ArtisanPack UI.',
                details: ['required' => self::REQUIRED_EXTENSIONS, 'missing' => $missingExtensions],
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

    protected function detectInstalledVersion( string $packageName ): ?string
    {
        foreach ( ComposerLock::packages() as $package ) {
            if ( ( $package['name'] ?? null ) === $packageName ) {
                return ltrim( (string) ( $package['version'] ?? '' ), 'v' ) ?: null;
            }
        }

        return null;
    }
}
