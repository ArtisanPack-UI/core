<?php

/**
 * Contract implemented by every diagnostic check.
 *
 * @since 1.2.0
 */

namespace ArtisanPackUI\Core\Diagnostics\Checks;

use ArtisanPackUI\Core\Diagnostics\DiagnosticResult;

interface DiagnosticCheck
{
    /**
     * Short identifier (e.g. "environment", "packages") used by the --package filter.
     */
    public function id(): string;

    /**
     * Section heading displayed in the report.
     */
    public function name(): string;

    /**
     * Execute the check and return one or more results.
     *
     * @return array<int, DiagnosticResult>
     */
    public function run(): array;

    /**
     * Whether the check exposes auto-fix behavior.
     */
    public function supportsFix(): bool;

    /**
     * Attempt to remediate issues this check detects. Implementations may emit
     * progress lines via the supplied writer callback. Returns true when something
     * was fixed, false when no fix was performed.
     *
     * @param  callable(string $message): void  $writer
     */
    public function fix(callable $writer): bool;
}
