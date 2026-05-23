<?php

/**
 * Shared logger contract used across the ArtisanPack UI ecosystem.
 *
 * @since      1.2.0
 *
 * @package    ArtisanPackUI\Core\Logging
 */

namespace ArtisanPackUI\Core\Logging;

/**
 * Defines the common logging surface implemented by every ArtisanPack UI
 * package logger.
 *
 * Implementations are responsible for routing messages to the configured log
 * channel, prefixing entries with the owning package's short name so that
 * aggregated log streams can be filtered cleanly, and emitting structured
 * audit records that include the actor, request, and timestamp metadata.
 *
 * @since 1.2.0
 *
 * @package ArtisanPackUI\Core\Logging
 */
interface ArtisanPackLogger
{
	/**
	 * Log a debug-level message.
	 *
	 * @since 1.2.0
	 *
	 * @param  string               $message  The human-readable log message.
	 * @param  array<string, mixed> $context  The structured context to attach.
	 *
	 * @return void
	 */
	public function debug( string $message, array $context = [] ): void;

	/**
	 * Log an informational message.
	 *
	 * @since 1.2.0
	 *
	 * @param  string               $message  The human-readable log message.
	 * @param  array<string, mixed> $context  The structured context to attach.
	 *
	 * @return void
	 */
	public function info( string $message, array $context = [] ): void;

	/**
	 * Log a warning-level message.
	 *
	 * @since 1.2.0
	 *
	 * @param  string               $message  The human-readable log message.
	 * @param  array<string, mixed> $context  The structured context to attach.
	 *
	 * @return void
	 */
	public function warning( string $message, array $context = [] ): void;

	/**
	 * Log an error-level message.
	 *
	 * @since 1.2.0
	 *
	 * @param  string               $message  The human-readable log message.
	 * @param  array<string, mixed> $context  The structured context to attach.
	 *
	 * @return void
	 */
	public function error( string $message, array $context = [] ): void;

	/**
	 * Record an audit entry for a sensitive or compliance-relevant action.
	 *
	 * Implementations must dispatch an {@see \ArtisanPackUI\Core\Events\AuditLogCreated}
	 * event so applications can intercept the entry for database storage or
	 * downstream aggregation.
	 *
	 * @since 1.2.0
	 *
	 * @param  string               $action  The audit action identifier ( e.g. `media.uploaded` ).
	 * @param  array<string, mixed> $data    Additional structured data describing the action.
	 *
	 * @return void
	 */
	public function audit( string $action, array $data = [] ): void;

	/**
	 * Get the short name of the package that owns the logger.
	 *
	 * @since 1.2.0
	 *
	 * @return string The package short name ( e.g. `media-library` ).
	 */
	public function package(): string;
}
