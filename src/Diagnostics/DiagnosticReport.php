<?php

/**
 * Aggregated diagnostic output, grouped by check.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Diagnostics
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Diagnostics;

use ArtisanPackUI\Core\Diagnostics\Checks\DiagnosticCheck;

/**
 * Aggregates the {@see DiagnosticResult} objects emitted by every
 * {@see DiagnosticCheck} executed by the {@see DiagnosticRunner}.
 *
 * Exposes pre-computed counts ( passed / warnings / errors ) and a
 * serialisable representation suitable for JSON output, used by both the
 * human-readable and `--json` modes of the `artisanpack:diagnose` command.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Diagnostics
 *
 * @since      1.2.0
 */
class DiagnosticReport
{
    /**
     * Cached flat list of every result returned by {@see allResults()}.
     *
     * @since 1.2.0
     *
     * @var array<int, DiagnosticResult>|null
     */
    private ?array $cachedResults = null;

    /**
     * Cached pass / warning / error counts returned by {@see counts()}.
     *
     * @since 1.2.0
     *
     * @var array{passed: int, warnings: int, errors: int}|null
     */
    private ?array $cachedCounts = null;

    /**
     * Create a new diagnostic report.
     *
     * @since 1.2.0
     *
     * @param  array<int, array{check: DiagnosticCheck, results: array<int, DiagnosticResult>}>  $sections
     *   Result sections grouped by check.
     */
    public function __construct( public readonly array $sections )
    {
    }

    /**
     * Return every individual result across all sections as a flat list.
     *
     * @since 1.2.0
     *
     * @return array<int, DiagnosticResult> The flattened result set.
     */
    public function allResults(): array
    {
        if ( null !== $this->cachedResults ) {
            return $this->cachedResults;
        }

        $all = [];

        foreach ( $this->sections as $section ) {
            foreach ( $section['results'] as $result ) {
                $all[] = $result;
            }
        }

        return $this->cachedResults = $all;
    }

    /**
     * Number of results that passed.
     *
     * @since 1.2.0
     *
     * @return int The count of passing results.
     */
    public function passedCount(): int
    {
        return $this->counts()['passed'];
    }

    /**
     * Number of results that emitted a warning.
     *
     * @since 1.2.0
     *
     * @return int The count of warning results.
     */
    public function warningCount(): int
    {
        return $this->counts()['warnings'];
    }

    /**
     * Number of results that emitted an error.
     *
     * @since 1.2.0
     *
     * @return int The count of error results.
     */
    public function errorCount(): int
    {
        return $this->counts()['errors'];
    }

    /**
     * Whether the report contains at least one error.
     *
     * @since 1.2.0
     *
     * @return bool True when any result has an error severity.
     */
    public function hasErrors(): bool
    {
        return $this->errorCount() > 0;
    }

    /**
     * Convert the report into a serialisable array.
     *
     * @since 1.2.0
     *
     * @return array<string, mixed> The structured representation used by --json output.
     */
    public function toArray(): array
    {
        $sections = [];

        foreach ( $this->sections as $section ) {
            /** @var DiagnosticCheck $check */
            $check                    = $section['check'];
            $sections[ $check->id() ] = [
                'name'    => $check->name(),
                'results' => array_map( fn ( DiagnosticResult $r ) => $r->toArray(), $section['results'] ),
            ];
        }

        return [
            'sections' => $sections,
            'summary'  => [
                'passed'   => $this->passedCount(),
                'warnings' => $this->warningCount(),
                'errors'   => $this->errorCount(),
            ],
        ];
    }

    /**
     * Compute the pass / warning / error counts, caching the result for reuse.
     *
     * @since 1.2.0
     *
     * @return array{passed: int, warnings: int, errors: int} The aggregated counts.
     */
    private function counts(): array
    {
        if ( null !== $this->cachedCounts ) {
            return $this->cachedCounts;
        }

        $counts = [
            'passed'   => 0,
            'warnings' => 0,
            'errors'   => 0,
        ];

        foreach ( $this->allResults() as $result ) {
            if ( $result->passed() ) {
                $counts['passed']++;
            } elseif ( $result->isWarning() ) {
                $counts['warnings']++;
            } elseif ( $result->isError() ) {
                $counts['errors']++;
            }
        }

        return $this->cachedCounts = $counts;
    }
}
