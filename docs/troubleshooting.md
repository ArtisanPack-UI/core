---
title: Troubleshooting
---

# Troubleshooting

## Diagnose first

Almost every Core-related issue surfaces in the diagnostic runner. Run it first:

```bash
php artisan artisanpack:diagnose -v
```

`-v` expands each result's `details` array so you can see exactly what's failing. Pass `--fix` to attempt an auto-repair on fixable checks (today: the missing-config check).

## "config/artisanpack.php is missing"

Run:

```bash
php artisan artisanpack:scaffold-config
```

Or use the standard publish workflow:

```bash
php artisan vendor:publish --tag=artisanpack-config
```

Or — easiest — let `artisanpack:diagnose --fix` handle it:

```bash
php artisan artisanpack:diagnose --fix
```

## "Missing configuration for installed package: …"

You installed a sibling ArtisanPack UI package after the unified config was generated. Re-run the scaffold command to merge the new package's defaults in:

```bash
php artisan artisanpack:scaffold-config
```

Existing customisations are preserved. Pass `--force` to overwrite existing keys with the package defaults.

## "{package} config found but package not installed"

A sibling package's config section is still in `config/artisanpack.php` even though the package itself is no longer installed. Open the file and delete the orphaned section, or accept that the unused section is harmless — diagnose reports it as a warning, not an error.

## "core binding missing" or "core failed to resolve"

The `CoreServiceProvider` is not loaded. Likely causes:

- Your `bootstrap/providers.php` was edited and the provider was removed. Add `\ArtisanPackUI\Core\CoreServiceProvider::class` back in (Laravel auto-discovery should add it for you on `composer install`, but a manually-edited providers list bypasses discovery).
- Composer autoload is stale. Run `composer dump-autoload`.

## "Property type missing" lint errors in commands

The `$signature` and `$description` properties on Laravel's `Command` base class are declared **untyped** (only `@var string` docblocks). PHP's inheritance rules forbid a child class from adding a type declaration to a property the parent leaves untyped — doing so fails with `Type of …::$signature must not be defined (as in class …)`. Leave these properties untyped in your command subclasses; the PHPCS warning is a known false-positive for Laravel Command subclasses.

## Tests fail with "class CoreServiceProvider not found"

Your test case isn't pulling Core in. Either:

- Extend `ArtisanPackUI\Core\Testing\ArtisanPackTestCase` — it registers `CoreServiceProvider` for you. See [Testing utilities](usage/testing-utilities.md).
- Or override `getPackageProviders()` to include `\ArtisanPackUI\Core\CoreServiceProvider::class`.

## "Class 'ArtisanPackUI\Core\Facades\ArtisanPackConfig' not found"

You're on Core 1.1 or older. The unified `ConfigurationManager` + facades arrived in 1.2. Upgrade:

```bash
composer require artisanpack-ui/core:^1.2
```

## "Helper function 'artisanpack_config' not found"

The `src/helpers.php` file is loaded via Composer's `files` autoload directive. Either:

- Run `composer dump-autoload` to re-generate the autoload map.
- Confirm the helpers file is registered in your install's composer autoload (`vendor/composer/autoload_files.php` should contain a reference to `core/src/helpers.php`).

## `artisanpack:check-updates` returns no results

Possible causes:

- The application has no internet access — `PackagistClient::getReleases()` returns an empty list on network failure. The command reports per-package errors in this case.
- Every installed sibling package is at the latest stable release. Run with `--json` to confirm: the `summary.updates_available` field will be `0`.
- A package isn't listed under `InstalledPackagesCheck::KNOWN_PACKAGES`. Only known packages are queried.

## Update report flags a non-security release as security-related

The detector is a keyword heuristic ( see [Updates](usage/updates.md) ). If a release's description happens to mention "security" without being a security release, it will be flagged. Pair `--security-only` with manual review of release notes before reacting.

## Compatibility check says a satisfied requirement is unsatisfied

Possible causes:

- The installed package's lockfile lists a version Composer cannot parse via `composer/semver`. Confirm `composer install` ran cleanly and `composer.lock` is well-formed.
- The constraint uses a syntax not supported by `composer/semver`. Open an issue against the sibling package — it should widen the constraint.
- You're running on Laravel 13 but the sibling package hasn't widened to `^11.0|^12.0|^13.0` yet. Open an issue or PR against the sibling.

## Audit events not being persisted

Audit logging is enabled by default but you also need a listener registered:

```php
\Illuminate\Support\Facades\Event::listen(
    \ArtisanPackUI\Core\Events\AuditLogCreated::class,
    YourAuditListener::class,
);
```

Confirm `artisanpack.core.logging.audit.enabled` isn't set to `false` in `config/artisanpack.php` or via the `ARTISANPACK_AUDIT_ENABLED` env var.

## Log entries don't appear in the configured channel

Confirm:

- `artisanpack.core.logging.channel` is set to a channel that exists in `config/logging.php`.
- The channel's `driver` and `path` (or whatever the driver requires) are correctly configured.
- Cached config: run `php artisan config:clear`.

## Blade directive throws `InvalidArgumentException`

`BladeDirectiveRegistrar` validates directive names at registration. Names must start with a letter or underscore and contain only word characters ( `[A-Za-z_][A-Za-z0-9_]*` ). Rename the directive to a valid identifier.

## "Could not connect to Packagist"

The update checker uses Laravel's HTTP client factory with a 10-second timeout. Network failures are swallowed and treated as "no releases available" — they appear in the report's `errors` section rather than aborting the run. Causes:

- No internet access on the host.
- Corporate firewall blocking `repo.packagist.org`.
- Packagist outage. Check status.packagist.com.

## Generic Composer / Laravel issues

```bash
# Re-generate the Composer autoloader
composer dump-autoload

# Clear all Laravel caches
php artisan optimize:clear

# Just configuration cache
php artisan config:clear
```

## Still stuck

- Run `php artisan artisanpack:diagnose -v --json` and capture the output before opening an issue — it includes everything anyone helping you will ask for first.
- Confirm you're on a supported PHP version (`8.2+`) and Laravel version (`11+`).
- Run the package's own test suite (`composer test` inside the Core package directory) to confirm Core itself is healthy.
- Open an issue against the relevant repository with the diagnose output, your `composer.json`, your `composer.lock`, and a minimal reproduction case.
