<?php

/**
 * Aggregated diagnostic output, grouped by check.
 *
 * @since 1.2.0
 */

namespace ArtisanPackUI\Core\Diagnostics;

use ArtisanPackUI\Core\Diagnostics\Checks\DiagnosticCheck;

class DiagnosticReport
{
    /**
     * @var array<int, DiagnosticResult>|null
     */
    private ?array $cachedResults = null;

    /**
     * @var array{passed: int, warnings: int, errors: int}|null
     */
    private ?array $cachedCounts = null;

    /**
     * @param  array<int, array{check: DiagnosticCheck, results: array<int, DiagnosticResult>}>  $sections
     */
    public function __construct( public readonly array $sections )
    {
    }

    /**
     * @return array<int, DiagnosticResult>
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

    public function passedCount(): int
    {
        return $this->counts()['passed'];
    }

    public function warningCount(): int
    {
        return $this->counts()['warnings'];
    }

    public function errorCount(): int
    {
        return $this->counts()['errors'];
    }

    public function hasErrors(): bool
    {
        return $this->errorCount() > 0;
    }

    /**
     * @return array<string, mixed>
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
     * @return array{passed: int, warnings: int, errors: int}
     */
    private function counts(): array
    {
        if ( null !== $this->cachedCounts ) {
            return $this->cachedCounts;
        }

        $counts = ['passed' => 0, 'warnings' => 0, 'errors' => 0];

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
