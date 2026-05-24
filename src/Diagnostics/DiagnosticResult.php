<?php

/**
 * Value object representing a single diagnostic check outcome.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Diagnostics
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Diagnostics;

// PHP requires the lowercase __construct magic method; the
// MagicMethodNotUppercase sniff misfires on the constructor here.
// phpcs:disable ArtisanPackUI.TypeHints.TypeDeclaration.MagicMethodNotUppercase

/**
 * Immutable value object describing the outcome of a single diagnostic check.
 *
 * Emitted by every {@see Checks\DiagnosticCheck::run()} implementation and
 * aggregated into a {@see DiagnosticReport} for rendering.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Diagnostics
 *
 * @since      1.2.0
 */
class DiagnosticResult
{
    /**
     * Create a new diagnostic result.
     *
     * @since 1.2.0
     *
     * @param  string                $label    Short label shown in the rendered report.
     * @param  DiagnosticStatus      $status   Severity classification for the outcome.
     * @param  string                $message  Optional supplementary message displayed next to the label.
     * @param  array<string, mixed>  $details  Extra context shown in verbose / JSON output.
     */
    public function __construct(
        public readonly string $label,
        public readonly DiagnosticStatus $status,
        public readonly string $message = '',
        public readonly array $details = [],
    ) {
    }

    /**
     * Whether the underlying check passed.
     *
     * @since 1.2.0
     *
     * @return bool True when the status is {@see DiagnosticStatus::Pass}.
     */
    public function passed(): bool
    {
        return DiagnosticStatus::Pass === $this->status;
    }

    /**
     * Whether the underlying check raised a warning.
     *
     * @since 1.2.0
     *
     * @return bool True when the status is {@see DiagnosticStatus::Warning}.
     */
    public function isWarning(): bool
    {
        return DiagnosticStatus::Warning === $this->status;
    }

    /**
     * Whether the underlying check raised an error.
     *
     * @since 1.2.0
     *
     * @return bool True when the status is {@see DiagnosticStatus::Error}.
     */
    public function isError(): bool
    {
        return DiagnosticStatus::Error === $this->status;
    }

    /**
     * Convert the result into a serialisable array.
     *
     * @since 1.2.0
     *
     * @return array<string, mixed> The structured representation used by --json output.
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
