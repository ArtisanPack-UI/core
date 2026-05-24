<?php

/**
 * Status values for diagnostic check results.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Diagnostics
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Diagnostics;

/**
 * Represents the severity classification of a {@see DiagnosticResult}.
 *
 * The enum cases map to the four severities the diagnostic runner reports on:
 * informational metadata, passing checks, soft failures ( warnings ), and hard
 * failures ( errors ).
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Diagnostics
 *
 * @since      1.2.0
 */
enum DiagnosticStatus: string
{
    /**
     * Return a single-character symbol used to prefix the status in the
     * rendered console report.
     *
     * @since 1.2.0
     *
     * @return string The Unicode symbol that represents the status.
     */
    public function symbol(): string
    {
        return match ( $this ) {
            self::Pass    => '✓',
            self::Warning => '⚠',
            self::Error   => '✗',
            self::Info    => 'ℹ',
        };
    }
    case Pass = 'pass';

    case Warning = 'warning';

    case Error = 'error';

    case Info = 'info';
}
