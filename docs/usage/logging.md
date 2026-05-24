---
title: Logging
---

# Logging

Every ArtisanPack UI package routes its log messages through the same logging stack so an application owner can filter, aggregate, and forward those messages from a single place. The stack consists of:

- `ArtisanPackLogger` — the interface every package logger implements
- `Logger` — the default implementation backed by Laravel's `LogManager`
- `LoggerFactory` — a container singleton that caches one `Logger` per package
- `ArtisanPackLog` — the static facade for the factory
- `AuditLogCreated` — the event emitted by `$log->audit()` calls

## Resolution

Resolve a logger scoped to your package via the facade, the helper, or container injection:

```php
use ArtisanPackUI\Core\Facades\ArtisanPackLog;
use ArtisanPackUI\Core\Logging\LoggerFactory;

$log = ArtisanPackLog::make( 'media-library' );

// Or
$log = app( LoggerFactory::class )->make( 'media-library' );

// Or inject directly
public function __construct( protected LoggerFactory $loggers ) {}

$log = $this->loggers->make( 'media-library' );
```

The factory caches one logger per package — calling `make()` multiple times returns the same instance.

## Writing entries

The four PSR-3 severities every package implementation emits:

```php
$log->debug( 'Processing thumbnail.', [ 'media_id' => 42 ] );
$log->info( 'Image uploaded.',      [ 'media_id' => 42, 'size' => '1.2 MB' ] );
$log->warning( 'Slow transcode.',   [ 'media_id' => 42, 'duration_ms' => 2400 ] );
$log->error( 'Transcode failed.',   [ 'media_id' => 42, 'reason' => 'unsupported codec' ] );
```

Every entry is automatically prefixed with the owning package's short name so aggregated streams can be filtered cleanly:

```
[2026-05-23 14:32:09] local.INFO: [media-library] Image uploaded. {"media_id":42,"size":"1.2 MB"}
```

## Audit entries

`audit()` is for compliance-relevant or security-relevant actions. It writes an `[AUDIT]` prefixed `info`-level entry AND dispatches an `AuditLogCreated` event with structured metadata:

```php
$log->audit( 'media.deleted', [
    'media_id' => 42,
    'reason'   => 'user_request',
] );
```

The dispatched event payload includes:

- `package` — the owning package's short name
- `action` — the action identifier (e.g. `media.deleted`)
- `data` — the caller-supplied array
- `user_id` — the resolved actor identifier (from `auth()` by default)
- `ip_address` — the resolved request IP (from `request()` by default)
- `timestamp` — UTC ISO 8601 timestamp

### Listening for audit events

Applications listen for `AuditLogCreated` to persist records, forward them to an external aggregator (Elasticsearch, Datadog, Splunk), or trigger notifications:

```php
use ArtisanPackUI\Core\Events\AuditLogCreated;
use Illuminate\Support\Facades\Event;

Event::listen( AuditLogCreated::class, function ( AuditLogCreated $event ): void {
    AuditEntry::create( [
        'package'    => $event->data['package'],
        'action'     => $event->data['action'],
        'data'       => $event->data['data'],
        'user_id'    => $event->data['user_id'],
        'ip_address' => $event->data['ip_address'],
        'occurred_at' => $event->data['timestamp'],
    ] );
} );
```

### Disabling audit logging

Set `artisanpack.core.logging.audit.enabled = false` in `config/artisanpack.php` (or `ARTISANPACK_AUDIT_ENABLED=false` in your `.env` if the binding is wired up) to silently drop `audit()` calls. Useful for environments that should not record audit log noise.

## Choosing a log channel

Set `artisanpack.core.logging.channel` (or the `ARTISANPACK_LOG_CHANNEL` env var) to the name of a Laravel log channel defined in `config/logging.php` to route every ArtisanPack UI entry through that channel:

```php
// config/logging.php
'channels' => [
    'artisanpack' => [
        'driver' => 'daily',
        'path'   => storage_path( 'logs/artisanpack.log' ),
        'days'   => 14,
    ],
],

// config/artisanpack.php
'core' => [
    'logging' => [
        'channel' => 'artisanpack',
    ],
],
```

Leaving the channel as `null` (the default) falls back to Laravel's default channel.

## Custom actor / request resolvers

The default `Logger` resolves the actor identifier via `auth()->id()` and the IP via `request()->ip()`. Console commands, queue workers, and background jobs may not have an authenticated user or an HTTP request available — in those contexts you can construct a logger with your own resolver closures:

```php
use ArtisanPackUI\Core\Logging\Logger;

$log = new Logger(
    package:          'media-library',
    logManager:       app( \Illuminate\Log\LogManager::class ),
    dispatcher:       app( \Illuminate\Contracts\Events\Dispatcher::class ),
    channel:          'artisanpack',
    auditEnabled:     true,
    actorResolver:    fn () => 'cli:thumbnail-generator',
    requestResolver: fn () => gethostbyname( gethostname() ),
);
```

In ordinary HTTP request handling, prefer the factory — it caches loggers, applies the configured channel + audit settings, and uses the default `auth()` / `request()` resolvers.

## Facade method map

| Facade method | Factory method |
|---|---|
| `ArtisanPackLog::make( $package )` | `LoggerFactory::make()` |
| `ArtisanPackLog::has( $package )` | `LoggerFactory::has()` |
| `ArtisanPackLog::forget( ?$package = null )` | `LoggerFactory::forget()` |
| `ArtisanPackLog::all()` | `LoggerFactory::all()` |
