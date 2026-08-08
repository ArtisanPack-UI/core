---
title: Upgrading from 1.2 to 1.3
---

# Upgrading from 1.2 to 1.3

Core 1.3 **drops support for Laravel 11**. That is the only breaking change: no public API was removed, nothing changed shape, and everything new in the release is additive and inert until you switch it on.

If your application already runs Laravel 12 or 13, the upgrade is a constraint bump and nothing else.

## 1. Check your Laravel version

```bash
php artisan --version
```

- **Laravel 12 or 13** — you are ready, continue to step 2.
- **Laravel 11** — you must upgrade the framework first. See below.

### Why Laravel 11 had to go

This is not a preference. Every Laravel 11 release — v11.0.0 through v11.55.0 — carries unpatched security advisories, and 11.x is past its security-fix window, so there is no patched release to move to. Composer's advisory policy refuses to install any of them, which means Core can no longer install or test against Laravel 11 at all; the Laravel 11 CI leg had been failing on exactly this. Continuing to advertise support for a version that does not resolve would be a claim we cannot honour.

Your two options:

1. **Upgrade to Laravel 12 or 13** — the only path that leaves you on a patched framework. Follow the [Laravel upgrade guide](https://laravel.com/docs/upgrade), then return here.
2. **Stay on Core 1.2.x** — understanding that your framework is unpatched, and that this is a holding position rather than a destination.

## 2. Bump the requirement

```bash
composer require artisanpack-ui/core:^1.3
```

The `illuminate/support` constraint narrows from `^11.0|^12.0|^13.0` to `^12.0|^13.0`. The `orchestra/testbench` dev constraint narrows from `^9.0|^10.0|^11.0` to `^10.0|^11.0` to match.

PHP 8.2 remains the minimum and now receives Laravel 12. Laravel 13 still requires PHP 8.3+, as enforced by Laravel's own constraint.

## 3. Update any assertion on the minimum-Laravel constant

`Constants::MIN_LARAVEL_VERSION` and `Diagnostics\Checks\EnvironmentCheck::MIN_LARAVEL_VERSION` change from `'10.0.0'` to `'12.0.0'`.

Both were already stale — 1.1 dropped Laravel 10 without updating them — which meant `artisanpack:diagnose` reported an unsupported framework version as *passing*. If you assert on either constant, update your expectation:

```php
// Before
expect( Constants::MIN_LARAVEL_VERSION )->toBe( '10.0.0' );

// After
expect( Constants::MIN_LARAVEL_VERSION )->toBe( '12.0.0' );
```

## 4. Run the diagnose command

Confirm the upgrade landed cleanly:

```bash
php artisan artisanpack:diagnose
```

Expected: every check passes. Note that `EnvironmentCheck` now correctly *fails* on a framework below 12.0.0 where it previously passed — if it reports a version problem it did not report before, that is the corrected constant telling you the truth.

## 5. Optional — adopt shared site resolution

New in 1.3 and **entirely opt-in**. `artisanpack.core.multi_tenant.enabled` defaults to `false`, so an application that ignores this section stays single-tenant and every package leaves its queries unscoped, exactly as in 1.2.

Site resolution gives the ecosystem a single answer to "which site is this code running for?" — one contract, one container binding, one configuration block — so a single request can no longer resolve to site 2 for one package while resolving to site 1 for another.

To switch it on:

```dotenv
ARTISANPACK_MULTI_TENANT_ENABLED=true
```

Then confirm the resolver list matches how your application determines the current site:

```php
'multi_tenant' => [
    'enabled'   => env( 'ARTISANPACK_MULTI_TENANT_ENABLED', false ),
    'resolvers' => [
        ArtisanPackUI\Core\MultiTenancy\HookSiteResolver::class,
    ],
],
```

The default `HookSiteResolver` applies the `ap.cmsFramework.currentSite.resolve` filter, which requires `artisanpack-ui/hooks` to be installed — it is a Composer *suggestion* rather than a requirement, and is only needed if that resolver stays in the list while tenancy is on. Swap in `NullSiteResolver`, your own implementation, or several in order, and `ChainSiteResolver` will take the first non-`null` answer.

Read the current site anywhere:

```php
$siteId = apCurrentSiteId();

// or through the facade
use ArtisanPackUI\Core\Facades\ArtisanPackSite;

$siteId = ArtisanPackSite::currentSiteId();
```

Pin a site over a bounded stretch of work — the previous context is restored afterwards, including when the callback throws:

```php
ArtisanPackSite::forSite( 7, function () {
    // Everything in here resolves to site 7.
} );
```

See [Site resolution](../usage/site-resolution.md) for the full guide.

## What did not change

- No public API was removed or altered.
- PHP 8.2 remains the minimum.
- Every 1.2 service — `ArtisanPackServiceProvider`, `ConfigurationManager`, `ArtisanPackLog`, `BladeDirectiveRegistrar`, `DiagnosticRunner`, `CompatibilityChecker`, `UpdateChecker`, `ArtisanPackTestCase` — behaves identically.
- Site resolution is inert until `enabled` is switched on.

## See also

- [UPGRADE.md](../../UPGRADE.md) — the condensed upgrade guide for every version
- [Upgrading from 1.1 to 1.2](upgrading-to-1-2.md)
- [Site resolution](../usage/site-resolution.md)
- [Changelog](../../CHANGELOG.md)
