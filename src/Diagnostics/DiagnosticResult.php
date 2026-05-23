<?php

/**
 * Value object representing a single diagnostic check outcome.
 *
 * @since 1.2.0
 */

namespace ArtisanPackUI\Core\Diagnostics;

class DiagnosticResult
{
    /**
     * @param  array<string, mixed>  $details  Extra context shown in verbose / JSON output.
     */
    public function __construct(
        public readonly string $label,
        public readonly DiagnosticStatus $status,
        public readonly string $message = '',
        public readonly array $details = [],
    ) {
    }

    public function passed(): bool
    {
        return DiagnosticStatus::Pass === $this->status;
    }

    public function isWarning(): bool
    {
        return DiagnosticStatus::Warning === $this->status;
    }

    public function isError(): bool
    {
        return DiagnosticStatus::Error === $this->status;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'label'   => $this->label,
            'status'  => $this->status->value,
            'message' => $this->message,
            'details' => $this->details,
        ];
    }
}
