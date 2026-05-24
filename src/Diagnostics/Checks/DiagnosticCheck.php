<?php

/**
 * Contract implemented by every diagnostic check.
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

/**
 * Defines the surface every diagnostic check must implement so the
 * {@see \ArtisanPackUI\Core\Diagnostics\DiagnosticRunner} can execute it
 * and the `artisanpack:diagnose` command can render and optionally repair
 * the resulting report.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Diagnostics\Checks
 *
 * @since      1.2.0
 */
interface DiagnosticCheck
{
    /**
     * Short identifier used by the `--package` filter (e.g. "environment", "packages").
     *
     * @since 1.2.0
     *
     * @return string The kebab-case check identifier.
     */
    public function id(): string;

    /**
     * Section heading displayed in the report.
     *
     * @since 1.2.0
     *
     * @return string The human-readable check name.
     */
    public function name(): string;

    /**
     * Execute the check and return one or more results.
     *
     * @since 1.2.0
     *
     * @return array<int, DiagnosticResult> The collected result rows.
     */
    public function run(): array;

    /**
     * Whether the check exposes auto-fix behavior.
     *
     * @since 1.2.0
     *
     * @return bool True when {@see fix()} performs meaningful remediation.
     */
    public function supportsFix(): bool;

    /**
     * Attempt to remediate issues this check detects. Implementations should emit
     * progress lines via the supplied writer callback. Returns true on success
     * (including no-op when nothing needed fixing) and false only when an attempted
     * fix failed.
     *
     * @since 1.2.0
     *
     * @param  callable(string $message): void  $writer  Callback used to surface progress messages.
     *
     * @return bool True when the fix succeeded or no work was needed; false on failure.
     */
    public function fix( callable $writer ): bool;
}
