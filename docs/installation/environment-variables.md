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

## `ARTISANPACK_MULTI_TENANT_ENABLED`

| | |
|-|-|
| **Type** | `bool` |
| **Default** | `false` |
| **Consumed by** | `SiteContext`, and through it every package that scopes records per site |
| **Config key** | `artisanpack.core.multi_tenant.enabled` |

Whether site resolution is switched on. Left at `false`, an unconfigured application stays single-tenant and every package leaves its queries unscoped. Set it to `true` only once the `resolvers` list matches how your application determines the current site — see [Site resolution](../usage/site-resolution.md).

Core merges the `multi_tenant` defaults in the service provider rather than relying on the published config, so this variable takes effect whether or not the application has run `vendor:publish --tag=artisanpack-config`. Without that, an operator could set it, see nothing happen, and run a multi-site install with every query unscoped.

Note that a site pinned explicitly through `ArtisanPackSite::setSiteId()` or `ArtisanPackSite::forSite()` applies even while this is `false`, because pinning is an explicit instruction from the calling code.

## Other ArtisanPack UI packages

Sibling packages read their own environment variables under their own section of `config/artisanpack.php` ( e.g. `artisanpack.security.*`, `artisanpack.media-library.*` ). Refer to the respective package's documentation for their full env var inventory.
