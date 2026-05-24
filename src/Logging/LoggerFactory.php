<?php

/**
 * Factory that resolves shared {@see ArtisanPackLogger} instances per package.
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

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Log\LogManager;

/**
 * Provides a single, cached entry point for obtaining the
 * {@see ArtisanPackLogger} that belongs to a package.
 *
 * The factory is registered as a container singleton by
 * {@see \ArtisanPackUI\Core\CoreServiceProvider}, so consuming packages can
 * either inject it directly or resolve it through the
 * {@see \ArtisanPackUI\Core\Facades\ArtisanPackLog} facade.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Logging
 *
 * @since      1.2.0
 */
class LoggerFactory
{
	/**
	 * Cached logger instances keyed by package short name.
	 *
	 * @since 1.2.0
	 *
	 * @var array<string, ArtisanPackLogger>
	 */
	protected array $loggers = [];

	/**
	 * Create a new logger factory.
	 *
	 * @since 1.2.0
	 *
	 * @param  LogManager  $logManager  The Laravel log manager used to resolve channels.
	 * @param  Dispatcher  $dispatcher  The event dispatcher used by audit logging.
	 * @param  Repository  $config      The configuration repository providing the channel and audit settings.
	 */
	public function __construct(
		protected LogManager $logManager,
		protected Dispatcher $dispatcher,
		protected Repository $config,
	) {
	}

	/**
	 * Get the logger for the given package, creating it on first access.
	 *
	 * @since 1.2.0
	 *
	 * @param  string  $package  The package short name ( e.g. `media-library` ).
	 *
	 * @return ArtisanPackLogger The cached logger for the package.
	 */
	public function make( string $package ): ArtisanPackLogger
	{
		return $this->loggers[ $package ] ??= $this->build( $package );
	}

	/**
	 * Determine whether a logger has already been built for the given package.
	 *
	 * @since 1.2.0
	 *
	 * @param  string  $package  The package short name.
	 *
	 * @return bool True when a logger has already been cached for the package.
	 */
	public function has( string $package ): bool
	{
		return isset( $this->loggers[ $package ] );
	}

	/**
	 * Forget the cached logger for the given package, or clear every cached
	 * logger when no package is supplied.
	 *
	 * Primarily intended for use by automated tests that need to reset cached
	 * state between assertions.
	 *
	 * @since 1.2.0
	 *
	 * @param  string|null  $package  The package short name, or null to clear every logger.
	 *
	 * @return void
	 */
	public function forget( ?string $package = null ): void
	{
		if ( null === $package ) {
			$this->loggers = [];

			return;
		}

		unset( $this->loggers[ $package ] );
	}

	/**
	 * Get every cached logger keyed by package short name.
	 *
	 * @since 1.2.0
	 *
	 * @return array<string, ArtisanPackLogger> The cached loggers keyed by package short name.
	 */
	public function all(): array
	{
		return $this->loggers;
	}

	/**
	 * Build a fresh {@see ArtisanPackLogger} for the given package.
	 *
	 * @since 1.2.0
	 *
	 * @param  string  $package  The package short name.
	 *
	 * @return ArtisanPackLogger The new logger instance.
	 */
	protected function build( string $package ): ArtisanPackLogger
	{
		return new Logger(
			$package,
			$this->logManager,
			$this->dispatcher,
			$this->resolveChannel(),
			$this->resolveAuditEnabled(),
		);
	}

	/**
	 * Resolve the configured log channel name.
	 *
	 * @since 1.2.0
	 *
	 * @return string|null The configured channel name, or null to use Laravel's default channel.
	 */
	protected function resolveChannel(): ?string
	{
		$channel = $this->config->get( 'artisanpack.core.logging.channel' );

		if ( null === $channel || '' === $channel ) {
			return null;
		}

		return is_string( $channel ) ? $channel : null;
	}

	/**
	 * Resolve whether audit logging is enabled.
	 *
	 * @since 1.2.0
	 *
	 * @return bool True when audit logging should record entries and dispatch events.
	 */
	protected function resolveAuditEnabled(): bool
	{
		return (bool) $this->config->get( 'artisanpack.core.logging.audit.enabled', true );
	}
}
