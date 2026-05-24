<?php

/**
 * Default {@see ArtisanPackLogger} implementation backed by Laravel's log stack.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Logging
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Logging;

// PHP requires the lowercase __construct magic method; the
// MagicMethodNotUppercase sniff misfires on the constructor here.
// phpcs:disable ArtisanPackUI.TypeHints.TypeDeclaration.MagicMethodNotUppercase

use ArtisanPackUI\Core\Enums\LogLevel;
use ArtisanPackUI\Core\Events\AuditLogCreated;
use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Routes ArtisanPack UI package log entries through Laravel's logging stack
 * while ensuring every record carries the owning package's short name so that
 * aggregated logs can be filtered cleanly.
 *
 * Audit records additionally include actor, request, and timestamp metadata
 * and dispatch the {@see AuditLogCreated} event so applications can intercept
 * them for database storage or downstream aggregation.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Logging
 *
 * @since      1.2.0
 */
class Logger implements ArtisanPackLogger
{
	/**
	 * Resolver invoked at audit-time to obtain the current actor identifier.
	 *
	 * @since 1.2.0
	 *
	 * @var Closure|null
	 */
	protected ?Closure $actorResolver;

	/**
	 * Resolver invoked at audit-time to obtain the current request IP address.
	 *
	 * @since 1.2.0
	 *
	 * @var Closure|null
	 */
	protected ?Closure $requestResolver;

	// phpcs:disable ArtisanPackUI.Formatting.Alignment.VariableAssignmentNotAligned

	/**
	 * Create a new ArtisanPack UI logger.
	 *
	 * @since 1.2.0
	 *
	 * @param  string        $package          The package short name ( e.g. `media-library` ).
	 * @param  LogManager    $logManager       The Laravel log manager used to resolve channels.
	 * @param  Dispatcher    $dispatcher       The event dispatcher used to emit audit events.
	 * @param  string|null   $channel          The log channel to write to, or null for the default.
	 * @param  bool          $auditEnabled     Whether audit() writes entries and dispatches events.
	 * @param  Closure|null  $actorResolver    Returns the current actor id, or null to use auth().
	 * @param  Closure|null  $requestResolver  Returns the current ip, or null to use request().
	 */
	public function __construct(
		protected string $package,
		protected LogManager $logManager,
		protected Dispatcher $dispatcher,
		protected ?string $channel = null,
		protected bool $auditEnabled = true,
		?callable $actorResolver = null,
		?callable $requestResolver = null,
	) {
		$this->actorResolver   = null === $actorResolver ? null : Closure::fromCallable( $actorResolver );
		$this->requestResolver = null === $requestResolver ? null : Closure::fromCallable( $requestResolver );
	}

	// phpcs:enable ArtisanPackUI.Formatting.Alignment.VariableAssignmentNotAligned

	/**
	 * {@inheritDoc}
	 */
	public function debug( string $message, array $context = [] ): void
	{
		$this->log( LogLevel::DEBUG, $message, $context );
	}

	/**
	 * {@inheritDoc}
	 */
	public function info( string $message, array $context = [] ): void
	{
		$this->log( LogLevel::INFO, $message, $context );
	}

	/**
	 * {@inheritDoc}
	 */
	public function warning( string $message, array $context = [] ): void
	{
		$this->log( LogLevel::WARNING, $message, $context );
	}

	/**
	 * {@inheritDoc}
	 */
	public function error( string $message, array $context = [] ): void
	{
		$this->log( LogLevel::ERROR, $message, $context );
	}

	/**
	 * {@inheritDoc}
	 */
	public function audit( string $action, array $data = [] ): void
	{
		if ( ! $this->auditEnabled ) {
			return;
		}

		$auditData = [
			'package'    => $this->package,
			'action'     => $action,
			'data'       => $data,
			'user_id'    => $this->resolveActor(),
			'ip_address' => $this->resolveIpAddress(),
			'timestamp'  => $this->resolveTimestamp(),
		];

		$this->channel()->info( "[AUDIT] [{$this->package}] {$action}", $auditData );

		$this->dispatcher->dispatch( new AuditLogCreated( $auditData ) );
	}

	/**
	 * {@inheritDoc}
	 */
	public function package(): string
	{
		return $this->package;
	}

	/**
	 * Write a log entry at the supplied level.
	 *
	 * @since 1.2.0
	 *
	 * @param  LogLevel             $level    The log severity level.
	 * @param  string               $message  The human-readable log message.
	 * @param  array<string, mixed> $context  The structured context to attach.
	 *
	 * @return void
	 */
	protected function log( LogLevel $level, string $message, array $context ): void
	{
		$context['artisanpack_package'] = $this->package;

		$this->channel()->log( $level->value, "[{$this->package}] {$message}", $context );
	}

	/**
	 * Resolve the underlying PSR logger for the configured channel.
	 *
	 * @since 1.2.0
	 *
	 * @return LoggerInterface The PSR logger backing the configured channel.
	 */
	protected function channel(): LoggerInterface
	{
		return null === $this->channel
			? $this->logManager->driver()
			: $this->logManager->channel( $this->channel );
	}

	/**
	 * Resolve the current actor identifier for an audit entry.
	 *
	 * @since 1.2.0
	 *
	 * @return mixed The current actor identifier, or null when unavailable.
	 */
	protected function resolveActor(): mixed
	{
		if ( null !== $this->actorResolver ) {
			return ( $this->actorResolver )();
		}

		try {
			$auth = function_exists( 'auth' ) ? auth() : null;
		} catch ( Throwable ) {
			return null;
		}

		if ( null === $auth ) {
			return null;
		}

		try {
			return $auth->check() ? $auth->id() : null;
		} catch ( Throwable ) {
			return null;
		}
	}

	/**
	 * Resolve the current request IP address for an audit entry.
	 *
	 * @since 1.2.0
	 *
	 * @return string|null The current request IP address, or null when unavailable.
	 */
	protected function resolveIpAddress(): ?string
	{
		if ( null !== $this->requestResolver ) {
			$ip = ( $this->requestResolver )();

			return is_string( $ip ) ? $ip : null;
		}

		try {
			// phpcs:ignore ArtisanPackUI.Security.ValidatedSanitizedInput.InputNotSanitized
			$request = function_exists( 'request' ) ? request() : null;
		} catch ( Throwable ) {
			return null;
		}

		if ( null === $request ) {
			return null;
		}

		try {
			$ip = $request->ip();
		} catch ( Throwable ) {
			return null;
		}

		return is_string( $ip ) ? $ip : null;
	}

	/**
	 * Resolve the ISO-8601 timestamp for an audit entry.
	 *
	 * @since 1.2.0
	 *
	 * @return string The current timestamp in ISO-8601 format.
	 */
	protected function resolveTimestamp(): string
	{
		try {
			if ( function_exists( 'now' ) ) {
				return now()->toIso8601String();
			}
		} catch ( Throwable ) {
			// Fall through to the gmdate-based fallback below.
		}

		return gmdate( 'c' );
	}
}
