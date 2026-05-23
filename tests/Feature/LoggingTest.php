<?php

// PSR-3 LoggerInterface fixes the signatures we must match below; the local
// `&` references used to capture closure context cannot be typed either; and
// PHP requires the lowercase __construct magic method.
// phpcs:disable ArtisanPackUI.TypeHints.TypeDeclaration.MissingParameterTypeDeclaration
// phpcs:disable ArtisanPackUI.Formatting.Spacing.NoSpaceAfterOperator
// phpcs:disable ArtisanPackUI.Security.ValidatedSanitizedInput.InputNotSanitized
// phpcs:disable ArtisanPackUI.TypeHints.TypeDeclaration.MagicMethodNotUppercase

use ArtisanPackUI\Core\Events\AuditLogCreated;
use ArtisanPackUI\Core\Facades\ArtisanPackLog;
use ArtisanPackUI\Core\Logging\ArtisanPackLogger;
use ArtisanPackUI\Core\Logging\Logger;
use ArtisanPackUI\Core\Logging\LoggerFactory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Event;
use Psr\Log\LoggerInterface;

beforeEach( function (): void {
	$this->recordedLogs = [];

	$this->channel = new class( $this->recordedLogs ) implements LoggerInterface {
		public function __construct( private array &$recorded )
		{
		}

		public function emergency( $message, array $context = [] ): void
		{
			$this->log( 'emergency', $message, $context );
		}

		public function alert( $message, array $context = [] ): void
		{
			$this->log( 'alert', $message, $context );
		}

		public function critical( $message, array $context = [] ): void
		{
			$this->log( 'critical', $message, $context );
		}

		public function error( $message, array $context = [] ): void
		{
			$this->log( 'error', $message, $context );
		}

		public function warning( $message, array $context = [] ): void
		{
			$this->log( 'warning', $message, $context );
		}

		public function notice( $message, array $context = [] ): void
		{
			$this->log( 'notice', $message, $context );
		}

		public function info( $message, array $context = [] ): void
		{
			$this->log( 'info', $message, $context );
		}

		public function debug( $message, array $context = [] ): void
		{
			$this->log( 'debug', $message, $context );
		}

		public function log( $level, $message, array $context = [] ): void
		{
			$this->recorded[] = [
				'level'   => (string) $level,
				'message' => (string) $message,
				'context' => $context,
			];
		}
	};

	$capturedChannel  = null;
	$this->logManager = Mockery::mock( LogManager::class );
	$this->logManager->shouldReceive( 'driver' )
		->andReturnUsing( function () use ( &$capturedChannel ) {
			$capturedChannel = null;

			return $this->channel;
		} );
	$this->logManager->shouldReceive( 'channel' )
		->andReturnUsing( function ( string $channel ) use ( &$capturedChannel ) {
			$capturedChannel = $channel;

			return $this->channel;
		} );
	$this->capturedChannelRef = &$capturedChannel;

	$this->dispatcher = app( Dispatcher::class );
} );

afterEach( function (): void {
	Mockery::close();
} );

test( 'the logger factory is registered as a singleton', function (): void {
	expect( app( LoggerFactory::class ) )->toBe( app( LoggerFactory::class ) );
} );

test( 'debug, info, warning, and error each forward to the channel with the package prefix', function (): void {
	$logger = new Logger(
		'media-library',
		$this->logManager,
		$this->dispatcher,
	);

	$logger->debug( 'starting', [ 'step' => 1 ] );
	$logger->info( 'continuing', [ 'step' => 2 ] );
	$logger->warning( 'slow', [ 'step' => 3 ] );
	$logger->error( 'failed', [ 'step' => 4 ] );

	expect( $this->recordedLogs )->toHaveCount( 4 );

	expect( $this->recordedLogs[0]['level'] )->toBe( 'debug' );
	expect( $this->recordedLogs[0]['message'] )->toBe( '[media-library] starting' );
	expect( $this->recordedLogs[0]['context'] )->toMatchArray( [
		'step'                => 1,
		'artisanpack_package' => 'media-library',
	] );

	expect( $this->recordedLogs[1]['level'] )->toBe( 'info' );
	expect( $this->recordedLogs[1]['message'] )->toBe( '[media-library] continuing' );

	expect( $this->recordedLogs[2]['level'] )->toBe( 'warning' );
	expect( $this->recordedLogs[3]['level'] )->toBe( 'error' );
} );

test( 'the logger uses the default channel when no channel is configured', function (): void {
	$logger = new Logger(
		'media-library',
		$this->logManager,
		$this->dispatcher,
	);

	$logger->info( 'message' );

	expect( $this->capturedChannelRef )->toBeNull();
} );

test( 'the logger uses the configured channel when one is supplied', function (): void {
	$logger = new Logger(
		'media-library',
		$this->logManager,
		$this->dispatcher,
		'artisanpack',
	);

	$logger->info( 'message' );

	expect( $this->capturedChannelRef )->toBe( 'artisanpack' );
} );

test( 'audit records an info-level entry with the structured payload', function (): void {
	Event::fake();

	$logger = new Logger(
		'media-library',
		$this->logManager,
		$this->dispatcher,
		null,
		true,
		fn (): int    => 42,
		fn (): string => '127.0.0.1',
	);

	$logger->audit( 'media.uploaded', [
		'media_id' => 17,
		'filename' => 'logo.png',
	] );

	expect( $this->recordedLogs )->toHaveCount( 1 );
	expect( $this->recordedLogs[0]['level'] )->toBe( 'info' );
	expect( $this->recordedLogs[0]['message'] )->toBe( '[AUDIT] [media-library] media.uploaded' );

	$context = $this->recordedLogs[0]['context'];
	expect( $context['package'] )->toBe( 'media-library' );
	expect( $context['action'] )->toBe( 'media.uploaded' );
	expect( $context['data'] )->toBe( [
		'media_id' => 17,
		'filename' => 'logo.png',
	] );
	expect( $context['user_id'] )->toBe( 42 );
	expect( $context['ip_address'] )->toBe( '127.0.0.1' );
	expect( $context['timestamp'] )->toBeString();
} );

test( 'audit dispatches an AuditLogCreated event with the same payload', function (): void {
	Event::fake( [ AuditLogCreated::class ] );

	$logger = new Logger(
		'media-library',
		$this->logManager,
		Event::getFacadeRoot(),
		null,
		true,
		fn (): int    => 42,
		fn (): string => '127.0.0.1',
	);

	$logger->audit( 'media.uploaded', [ 'media_id' => 17 ] );

	Event::assertDispatched( AuditLogCreated::class, function ( AuditLogCreated $event ): bool {
		return 'media-library' === $event->data['package']
			&& 'media.uploaded' === $event->data['action']
			&& [ 'media_id' => 17 ] === $event->data['data']
			&& 42 === $event->data['user_id'];
	} );
} );

test( 'audit is a no-op when audit logging is disabled', function (): void {
	Event::fake();

	$logger = new Logger(
		'media-library',
		$this->logManager,
		$this->dispatcher,
		null,
		false,
	);

	$logger->audit( 'media.uploaded' );

	expect( $this->recordedLogs )->toBe( [] );
	Event::assertNotDispatched( AuditLogCreated::class );
} );

test( 'the logger returns its package name', function (): void {
	$logger = new Logger(
		'media-library',
		$this->logManager,
		$this->dispatcher,
	);

	expect( $logger->package() )->toBe( 'media-library' );
} );

test( 'the factory caches loggers per package', function (): void {
	$factory = app( LoggerFactory::class );

	$first  = $factory->make( 'media-library' );
	$second = $factory->make( 'media-library' );
	$other  = $factory->make( 'security' );

	expect( $first )->toBeInstanceOf( ArtisanPackLogger::class );
	expect( $first )->toBe( $second );
	expect( $first )->not->toBe( $other );
} );

test( 'the factory reports cached packages via has()', function (): void {
	$factory = app( LoggerFactory::class );

	expect( $factory->has( 'media-library' ) )->toBeFalse();

	$factory->make( 'media-library' );

	expect( $factory->has( 'media-library' ) )->toBeTrue();
} );

test( 'forget drops cached loggers for a single package', function (): void {
	$factory = app( LoggerFactory::class );

	$first = $factory->make( 'media-library' );
	$factory->forget( 'media-library' );

	expect( $factory->has( 'media-library' ) )->toBeFalse();
	expect( $factory->make( 'media-library' ) )->not->toBe( $first );
} );

test( 'forget without an argument clears every cached logger', function (): void {
	$factory = app( LoggerFactory::class );

	$factory->make( 'media-library' );
	$factory->make( 'security' );
	$factory->forget();

	expect( $factory->all() )->toBe( [] );
} );

test( 'the facade proxies to the factory', function (): void {
	$first  = ArtisanPackLog::make( 'media-library' );
	$second = ArtisanPackLog::make( 'media-library' );

	expect( $first )->toBeInstanceOf( ArtisanPackLogger::class );
	expect( $first )->toBe( $second );
} );

test( 'the factory honours the configured log channel', function (): void {
	config()->set( 'artisanpack.core.logging.channel', 'artisanpack' );

	$factory = new LoggerFactory(
		$this->logManager,
		$this->dispatcher,
		app( 'config' ),
	);

	$factory->make( 'media-library' )->info( 'hello' );

	expect( $this->capturedChannelRef )->toBe( 'artisanpack' );
} );

test( 'the factory builds disabled-audit loggers when audit is turned off', function (): void {
	Event::fake();
	config()->set( 'artisanpack.core.logging.audit.enabled', false );

	$factory = new LoggerFactory(
		$this->logManager,
		$this->dispatcher,
		app( 'config' ),
	);

	$factory->make( 'media-library' )->audit( 'media.uploaded' );

	expect( $this->recordedLogs )->toBe( [] );
	Event::assertNotDispatched( AuditLogCreated::class );
} );
