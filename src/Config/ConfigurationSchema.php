<?php

/**
 * Lightweight schema describing the expected shape of a package configuration.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Config
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Config;

use Illuminate\Support\Arr;

/**
 * Validates a flat or nested configuration array against a declarative set of
 * rules without pulling in a full JSON Schema implementation.
 *
 * Each rule is keyed by a dot-notation configuration key and may declare any of
 * the following constraints:
 *
 * - `type`     One of string, integer, float, numeric, boolean, array.
 * - `required` Whether the key must be present in the configuration.
 * - `in`       A list of allowed values for the key.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Config
 *
 * @since      1.2.0
 */
class ConfigurationSchema
{
	/**
	 * The schema rules keyed by dot-notation configuration key.
	 *
	 * @since 1.2.0
	 *
	 * @var array<string, array<string, mixed>>
	 */
	protected array $rules;

	/**
	 * Create a new configuration schema.
	 *
	 * @since 1.2.0
	 *
	 * @param  array<string, array<string, mixed>>  $rules  The schema rules keyed by configuration key.
	 */
	public function __construct( array $rules = [] )
	{
		$this->rules = $rules;
	}

	/**
	 * Get the raw schema rules.
	 *
	 * @since 1.2.0
	 *
	 * @return array<string, array<string, mixed>> The schema rules keyed by configuration key.
	 */
	public function rules(): array
	{
		return $this->rules;
	}

	/**
	 * Validate the given configuration against the schema.
	 *
	 * @since 1.2.0
	 *
	 * @param  array<string, mixed>  $config  The configuration values to validate.
	 *
	 * @return ValidationResult The outcome of the validation pass.
	 */
	public function validate( array $config ): ValidationResult
	{
		$errors = [];

		foreach ( $this->rules as $key => $rule ) {
			$present = Arr::has( $config, $key );
			$value   = Arr::get( $config, $key );

			if ( ! $present ) {
				if ( true === ( $rule['required'] ?? false ) ) {
					$errors[ $key ][] = "The configuration key '{$key}' is required.";
				}

				continue;
			}

			$messages = $this->validateValue( $key, $value, $rule );

			if ( [] !== $messages ) {
				$errors[ $key ] = $messages;
			}
		}

		return new ValidationResult( $errors );
	}

	/**
	 * Validate a single value against its rule definition.
	 *
	 * @since 1.2.0
	 *
	 * @param  string               $key    The configuration key being validated.
	 * @param  mixed                $value  The value to validate.
	 * @param  array<string, mixed> $rule   The rule definition for the key.
	 *
	 * @return array<int, string> The validation error messages for the value.
	 */
	protected function validateValue( string $key, mixed $value, array $rule ): array
	{
		$messages = [];

		if ( isset( $rule['type'] ) && ! $this->matchesType( $value, $rule['type'] ) ) {
			$messages[] = "The configuration key '{$key}' must be of type {$rule['type']}.";
		}

		if ( isset( $rule['in'] ) && is_array( $rule['in'] ) && ! in_array( $value, $rule['in'], true ) ) {
			$allowed    = implode( ', ', array_map( static fn ( $option ): string => var_export( $option, true ), $rule['in'] ) );
			$messages[] = "The configuration key '{$key}' must be one of: {$allowed}.";
		}

		return $messages;
	}

	/**
	 * Determine whether a value matches the expected type.
	 *
	 * @since 1.2.0
	 *
	 * @param  mixed   $value  The value to inspect.
	 * @param  string  $type   The expected type name.
	 *
	 * @return bool True when the value matches the expected type.
	 */
	protected function matchesType( mixed $value, string $type ): bool
	{
		return match ( $type ) {
			'string'          => is_string( $value ),
			'int', 'integer'  => is_int( $value ),
			'float', 'double' => is_float( $value ),
			'numeric'         => is_numeric( $value ),
			'bool', 'boolean' => is_bool( $value ),
			'array'           => is_array( $value ),
			default           => false,
		};
	}
}
