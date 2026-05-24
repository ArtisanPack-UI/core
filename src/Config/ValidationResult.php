<?php

/**
 * Result object describing the outcome of a configuration validation pass.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Config
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Config;

/**
 * Immutable value object returned by {@see ConfigurationManager::validate()}.
 *
 * Carries the list of validation error messages keyed by the configuration
 * key that failed and exposes a small, expressive API for inspecting whether
 * the validated configuration passed.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Config
 *
 * @since      1.2.0
 */
class ValidationResult
{
	/**
	 * Validation error messages keyed by configuration key.
	 *
	 * @since 1.2.0
	 *
	 * @var array<string, array<int, string>>
	 */
	protected array $errors;

	/**
	 * Create a new validation result.
	 *
	 * @since 1.2.0
	 *
	 * @param  array<string, array<int, string>>  $errors  Error messages keyed by configuration key.
	 */
	public function __construct( array $errors = [] )
	{
		$this->errors = $errors;
	}

	/**
	 * Determine whether the validated configuration passed without errors.
	 *
	 * @since 1.2.0
	 *
	 * @return bool True when there are no errors.
	 */
	public function passes(): bool
	{
		return [] === $this->errors;
	}

	/**
	 * Determine whether the validated configuration failed.
	 *
	 * @since 1.2.0
	 *
	 * @return bool True when there is at least one error.
	 */
	public function fails(): bool
	{
		return ! $this->passes();
	}

	/**
	 * Determine whether the result contains any errors.
	 *
	 * @since 1.2.0
	 *
	 * @return bool True when there is at least one error.
	 */
	public function hasErrors(): bool
	{
		return $this->fails();
	}

	/**
	 * Get the validation error messages.
	 *
	 * When a key is provided only the messages for that configuration key are
	 * returned; otherwise every message is returned as a flat list.
	 *
	 * @since 1.2.0
	 *
	 * @param  string|null  $key  Optional configuration key to filter messages by.
	 *
	 * @return array<int, string> The matching error messages.
	 */
	public function errors( ?string $key = null ): array
	{
		if ( null !== $key ) {
			return $this->errors[ $key ] ?? [];
		}

		return array_merge( ...array_values( $this->errors ) ?: [ [] ] );
	}

	/**
	 * Get the raw error messages keyed by configuration key.
	 *
	 * @since 1.2.0
	 *
	 * @return array<string, array<int, string>> Error messages keyed by configuration key.
	 */
	public function messages(): array
	{
		return $this->errors;
	}
}
