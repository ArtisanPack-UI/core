<?php

/**
 * Base exception for all ArtisanPack UI errors.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Exceptions
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Exceptions;

use Exception;
use Throwable;

/**
 * Provides a common base class for every exception thrown across the
 * ArtisanPack UI ecosystem so callers can catch all package errors with a
 * single catch block while retaining package identification, optional error
 * codes, and structured context for logging.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Exceptions
 *
 * @since      1.2.0
 */
class ArtisanPackException extends Exception
{
	/**
	 * The short name of the package that raised the exception.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	protected string $package = 'core';

	/**
	 * The optional machine-readable error code for the exception.
	 *
	 * @since 1.2.0
	 *
	 * @var string|null
	 */
	protected ?string $errorCode = null;

	/**
	 * Create a new ArtisanPack UI exception instance.
	 *
	 * @since 1.2.0
	 *
	 * @param  string          $message    The human-readable error message.
	 * @param  int             $code       The integer exception code.
	 * @param  Throwable|null  $previous   The previous throwable used for chaining.
	 * @param  string|null     $errorCode  The optional machine-readable error code.
	 */
	public function __construct(
		string $message = '',
		int $code = 0,
		?Throwable $previous = null,
		?string $errorCode = null,
	) {
		parent::__construct( $message, $code, $previous );

		$this->errorCode = $errorCode;
	}

	/**
	 * Get the short name of the package that raised the exception.
	 *
	 * @since 1.2.0
	 *
	 * @return string The package short name.
	 */
	public function getPackage(): string
	{
		return $this->package;
	}

	/**
	 * Get the machine-readable error code for the exception.
	 *
	 * @since 1.2.0
	 *
	 * @return string|null The error code, or null when none was provided.
	 */
	public function getErrorCode(): ?string
	{
		return $this->errorCode;
	}

	/**
	 * Get the structured context for the exception suitable for logging.
	 *
	 * @since 1.2.0
	 *
	 * @return array{package: string, error_code: string|null} The exception context.
	 */
	public function context(): array
	{
		return [
			'package'    => $this->package,
			'error_code' => $this->errorCode,
		];
	}
}
