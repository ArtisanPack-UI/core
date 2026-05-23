<?php

/**
 * Status values for diagnostic check results.
 *
 * @since 1.2.0
 */

namespace ArtisanPackUI\Core\Diagnostics;

enum DiagnosticStatus: string
{
    case Pass = 'pass';

    case Warning = 'warning';

    case Error = 'error';

    case Info = 'info';

    public function symbol(): string
    {
        return match ($this) {
            self::Pass => '✓',
            self::Warning => '⚠',
            self::Error => '✗',
            self::Info => 'ℹ',
        };
    }
}
