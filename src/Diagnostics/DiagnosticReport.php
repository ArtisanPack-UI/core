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
     * @param  array<int, array{check: DiagnosticCheck, results: array<int, DiagnosticResult>}>  $sections
     */
    public function __construct(public readonly array $sections) {}

    /**
     * @return array<int, DiagnosticResult>
     */
    public function allResults(): array
    {
        $all = [];

        foreach ($this->sections as $section) {
            foreach ($section['results'] as $result) {
                $all[] = $result;
            }
        }

        return $all;
    }

    public function passedCount(): int
    {
        return count(array_filter($this->allResults(), fn ($r) => $r->passed()));
    }

    public function warningCount(): int
    {
        return count(array_filter($this->allResults(), fn ($r) => $r->isWarning()));
    }

    public function errorCount(): int
    {
        return count(array_filter($this->allResults(), fn ($r) => $r->isError()));
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

        foreach ($this->sections as $section) {
            /** @var DiagnosticCheck $check */
            $check = $section['check'];
            $sections[$check->id()] = [
                'name' => $check->name(),
                'results' => array_map(fn (DiagnosticResult $r) => $r->toArray(), $section['results']),
            ];
        }

        return [
            'sections' => $sections,
            'summary' => [
                'passed' => $this->passedCount(),
                'warnings' => $this->warningCount(),
                'errors' => $this->errorCount(),
            ],
        ];
    }
}
