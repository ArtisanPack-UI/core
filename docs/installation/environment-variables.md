---
title: Environment variables
---

# Environment variables

ArtisanPack UI Core reads a handful of environment variables. None are required — every variable has a sensible default. Set them in your application's `.env` to override the defaults per environment.

## `ARTISANPACK_LOG_CHANNEL`

| | |
|-|-|
| **Type** | `string` |
| **Default** | `null` ( falls back to Laravel's default log channel ) |
| **Consumed by** | `LoggerFactory`, `Logger`, every `ArtisanPackLog::make()` call |
| **Config key** | `artisanpack.core.logging.channel` |

Name of the Laravel log channel that every ArtisanPack UI package writes through. The channel must exist in your application's `config/logging.php`. Setting it routes informational, warning, error, and audit entries from every sibling package to the same channel so aggregated streams are easy to filter.

## `ARTISANPACK_AUDIT_ENABLED`

| | |
|-|-|
| **Type** | `bool` |
| **Default** | `true` |
| **Consumed by** | `Logger::audit()` calls across every package |
| **Config key** | `artisanpack.core.logging.audit.enabled` |

Whether `$log->audit()` calls actually persist an entry and dispatch an `AuditLogCreated` event. Set to `false` to silently drop audit calls — useful in environments that should not record audit log noise ( local development, integration tests ).

The default config file currently hard-codes `true`; add the env binding if you want per-environment control:

```php
'audit' => [
    'enabled' => env( 'ARTISANPACK_AUDIT_ENABLED', true ),
],
```

## Other ArtisanPack UI packages

Sibling packages read their own environment variables under their own section of `config/artisanpack.php` ( e.g. `artisanpack.security.*`, `artisanpack.media-library.*` ). Refer to the respective package's documentation for their full env var inventory.
