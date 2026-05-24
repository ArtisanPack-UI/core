---
title: FAQ
---

# Frequently Asked Questions

## Do I need to install Core separately?

No. Every sibling ArtisanPack UI package declares `artisanpack-ui/core: ^1.0` in its composer requirements, so installing any sibling package pulls Core in automatically. Install Core directly only if you want the diagnose / compatibility / update tooling on its own.

## Why one config file for the whole ecosystem?

It gives the application owner a single place to override defaults across every ArtisanPack UI package. Without it, every package would publish its own `config/<package>.php` and the owner would have to remember which file controls what. The unified file is also easier to back up, audit, and review in pull requests.

## What happens if I edit `config/artisanpack.php` and then re-run `artisanpack:scaffold-config`?

Existing keys are preserved by default — only missing keys are added. Pass `--force` to overwrite existing keys with the package defaults.

## How do I add my own ArtisanPack UI package?

Run `php artisan artisanpack:make-package artisanpack-ui/<name>` to scaffold one from the blueprint. See [Creating new packages](advanced/creating-packages.md) for the full walkthrough.

## What does `artisanpack:diagnose` actually check?

Five things: PHP / Laravel / Livewire versions + required PHP extensions, the installed version of every known ArtisanPack UI package, the presence of `config/artisanpack.php` + a section for every installed package, the core container bindings (`core`, `a11y`), and the Artisan commands Core publishes. See [Diagnostics](usage/diagnostics.md) for the full check inventory.

## What's the difference between `artisanpack:diagnose` and `artisanpack:check-compatibility`?

- **`diagnose`** asks "is the installation healthy right now?" — PHP version, extensions, config file presence, container bindings, command registration.
- **`check-compatibility`** asks "do the installed packages' declared constraints work together?" — inter-package dependencies, PHP / Laravel / extension constraints declared by each sibling package.

Run both in CI. `diagnose` catches "your install is broken"; `check-compatibility` catches "package A wants version X of package B but you have version Y."

## How does the update checker know about security releases?

It scans each release's notes ( description + keywords pulled from Packagist ) for any of: `security`, `vulnerability`, `vulnerabilities`, `cve-`, `xss`, `csrf`, `sql injection`, `remote code execution`. This is a heuristic — a security release that doesn't use any of these words won't be flagged. Pair `--security-only` with manual review for the strictest workflows.

## Why a separate `Logger` per package instead of one global logger?

Because aggregated log streams need to be filterable. Every entry from `ArtisanPackLog::make( 'media-library' )` carries the `[media-library]` prefix automatically, so when you ship logs to Datadog / Splunk / Elasticsearch you can grep / facet by package without parsing the message body.

## How do I disable audit logging in development?

Set `artisanpack.core.logging.audit.enabled = false` in `config/artisanpack.php`, or add the env binding and set `ARTISANPACK_AUDIT_ENABLED=false` in your `.env.local`. Audit calls become no-ops silently — the application code does not need to change.

## How do I listen for audit log events?

Register a listener on the `AuditLogCreated` event in your application's service provider:

```php
\Illuminate\Support\Facades\Event::listen(
    \ArtisanPackUI\Core\Events\AuditLogCreated::class,
    function ( \ArtisanPackUI\Core\Events\AuditLogCreated $event ): void {
        AuditEntry::create( [
            'package'     => $event->data['package'],
            'action'      => $event->data['action'],
            'data'        => $event->data['data'],
            'user_id'     => $event->data['user_id'],
            'ip_address'  => $event->data['ip_address'],
            'occurred_at' => $event->data['timestamp'],
        ] );
    },
);
```

See [Logging](usage/logging.md) for the full event payload shape.

## Should sibling packages still extend Laravel's `ServiceProvider` directly?

You can, but you don't have to anymore. `ArtisanPackServiceProvider` handles config merging, command registration, view loading, route loading, and singleton registration declaratively — typically reducing a sibling package's provider to a handful of properties + one or two override methods. See [Service provider](usage/service-provider.md).

## Does Core support Laravel 13?

Yes, since Core 1.2. The composer constraint is `^11.0|^12.0|^13.0`. Laravel 13 requires PHP 8.3+, so PHP 8.2 users continue to receive Laravel 11 / 12 ( Laravel enforces its own PHP constraint ).

## Is Laravel 10 still supported?

No, Core 1.1 dropped Laravel 10 support. Use Core 1.0.x if you must stay on Laravel 10.

## What's the recommended way to read configuration?

`artisanpack_config()` helper for one-off reads, the `ArtisanPackConfig` facade for facade-style code, and `app( ConfigurationManager::class )` for injected dependencies. All three go through the same `ConfigurationManager` singleton — pick whichever reads best in context. Avoid `config('artisanpack.…')` directly in package code; the manager adds schema validation and runtime override support.

## How do I write tests for my sibling package?

Extend `ArtisanPackTestCase` and register your provider via `getAdditionalProviders()`. The base class wires Core, pulls in `ArtisanPackAssertions` + `InteractsWithConfiguration` + `MocksArtisanPackServices`, and gives you ecosystem-aware helpers out of the box. See [Testing utilities](usage/testing-utilities.md) for the full walkthrough.

## Where do I file bugs or feature requests?

For Core itself, open an issue at the package repository. For sibling packages, file against the relevant repository ( each sibling package has its own issue tracker ).
