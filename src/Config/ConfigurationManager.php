<?php

/**
 * Unified configuration manager for ArtisanPack UI packages.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Config
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Config;

use Illuminate\Contracts\Config\Repository;

/**
 * Provides a single, consistent API for reading, writing, merging, and
 * validating the configuration of every installed ArtisanPack UI package.
 *
 * All package configuration lives beneath the shared `artisanpack` namespace
 * in Laravel's configuration repository, keyed by the package's short name
 * ( e.g. `artisanpack.media-library` ). This manager hides that convention
 * behind package-aware helpers so callers never have to assemble the dotted
 * key paths themselves.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Config
 *
 * @since      1.2.0
 */
class ConfigurationManager
{
	/**
	 * The configuration namespace shared by all ArtisanPack UI packages.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	protected const NAMESPACE = 'artisanpack';

	/**
	 * Registered configuration schemas keyed by package short name.
	 *
	 * @since 1.2.0
	 *
	 * @var array<string, ConfigurationSchema>
	 */
	protected array $schemas = [];

	/**
	 * Create a new configuration manager.
	 *
	 * @since 1.2.0
	 *
	 * @param  Repository  $config  The underlying Laravel configuration repository.
	 */
	public function __construct( protected Repository $config )
	{
	}

	/**
	 * Get a configuration value for a package.
	 *
	 * @since 1.2.0
	 *
	 * @param  string  $package  The package short name ( e.g. `media-library` ).
	 * @param  string  $key      The dot-notation key relative to the package.
	 * @param  mixed   $default  The value returned when the key is not set.
	 *
	 * @return mixed The configuration value, or the default when not set.
	 */
	public function get( string $package, string $key, mixed $default = null ): mixed
	{
		return $this->config->get( $this->qualify( $package, $key ), $default );
	}

	/**
	 * Set a configuration value for a package at runtime.
	 *
	 * @since 1.2.0
	 *
	 * @param  string  $package  The package short name.
	 * @param  string  $key      The dot-notation key relative to the package.
	 * @param  mixed   $value    The value to store.
	 *
	 * @return void
	 */
	public function set( string $package, string $key, mixed $value ): void
	{
		$this->config->set( $this->qualify( $package, $key ), $value );
	}

	/**
	 * Determine whether a configuration key is set for a package.
	 *
	 * @since 1.2.0
	 *
	 * @param  string  $package  The package short name.
	 * @param  string  $key      The dot-notation key relative to the package.
	 *
	 * @return bool True when the key exists.
	 */
	public function has( string $package, string $key ): bool
	{
		return $this->config->has( $this->qualify( $package, $key ) );
	}

	/**
	 * Merge the given configuration into a package's existing configuration.
	 *
	 * Existing user values take precedence over the supplied defaults, mirroring
	 * the behaviour of Laravel's `mergeConfigFrom()`. The merged result is written
	 * back to the repository and returned.
	 *
	 * @since 1.2.0
	 *
	 * @param  string                $package  The package short name.
	 * @param  array<string, mixed>  $config   The configuration defaults to merge in.
	 *
	 * @return array<string, mixed> The merged configuration array.
	 */
	public function merge( string $package, array $config ): array
	{
		$existing = $this->package( $package );
		$merged   = array_replace_recursive( $config, $existing );

		$this->config->set( $this->qualify( $package ), $merged );

		return $merged;
	}

	/**
	 * Validate a configuration array against a package's registered schema.
	 *
	 * When no schema has been registered for the package the configuration is
	 * considered valid and a passing result is returned.
	 *
	 * @since 1.2.0
	 *
	 * @param  string                $package  The package short name.
	 * @param  array<string, mixed>  $config   The configuration values to validate.
	 *
	 * @return ValidationResult The outcome of the validation pass.
	 */
	public function validate( string $package, array $config ): ValidationResult
	{
		if ( ! isset( $this->schemas[ $package ] ) ) {
			return new ValidationResult();
		}

		return $this->schemas[ $package ]->validate( $config );
	}

	/**
	 * Get the configuration for every ArtisanPack UI package.
	 *
	 * @since 1.2.0
	 *
	 * @return array<string, mixed> The entire ArtisanPack UI configuration tree.
	 */
	public function all(): array
	{
		return $this->config->get( self::NAMESPACE, [] );
	}

	/**
	 * Get the entire configuration array for a single package.
	 *
	 * @since 1.2.0
	 *
	 * @param  string  $package  The package short name.
	 *
	 * @return array<string, mixed> The package's configuration values.
	 */
	public function package( string $package ): array
	{
		return $this->config->get( $this->qualify( $package ), [] );
	}

	/**
	 * Register a validation schema for a package.
	 *
	 * @since 1.2.0
	 *
	 * @param  string                                 $package  The package short name.
	 * @param  array<string, array<string, mixed>>    $schema   The schema rules keyed by configuration key.
	 *
	 * @return void
	 */
	public function registerSchema( string $package, array $schema ): void
	{
		$this->schemas[ $package ] = new ConfigurationSchema( $schema );
	}

	/**
	 * Get the registered schema for a package, if any.
	 *
	 * @since 1.2.0
	 *
	 * @param  string  $package  The package short name.
	 *
	 * @return ConfigurationSchema|null The registered schema, or null when none exists.
	 */
	public function schema( string $package ): ?ConfigurationSchema
	{
		return $this->schemas[ $package ] ?? null;
	}

	/**
	 * Build the fully-qualified configuration key for a package.
	 *
	 * @since 1.2.0
	 *
	 * @param  string       $package  The package short name.
	 * @param  string|null  $key      An optional dot-notation key relative to the package.
	 *
	 * @return string The fully-qualified configuration key.
	 */
	protected function qualify( string $package, ?string $key = null ): string
	{
		$base = self::NAMESPACE . '.' . $package;

		return null === $key || '' === $key ? $base : $base . '.' . $key;
	}
}
