---
title: Updates
---

# Updates

`UpdateChecker` reads `composer.lock`, asks Packagist for the latest stable release of every installed ArtisanPack UI package, and produces a structured report classifying each available update as **major**, **minor**, or **patch** — and flagging the ones whose release notes describe a security fix.

`PackagistClient` is the thin Packagist v2 API client underneath. Responses are cached for the duration of `artisanpack.core.updates.cache_ttl` ( default: `3600` seconds ) so the command can be run repeatedly without hitting the Packagist API every invocation. Cache entries are refreshed on read so frequently-queried packages keep their entries warm.

## Running the report

```bash
# Full report
php artisan artisanpack:check-updates

# JSON payload (suitable for CI / scripting)
php artisan artisanpack:check-updates --json

# Skip major updates (only minor + patch)
php artisan artisanpack:check-updates --minor-only

# Only security-related releases
php artisan artisanpack:check-updates --security-only

# Include changelog excerpts + source URLs in the human-readable output
php artisan artisanpack:check-updates --changelog
```

The command always exits zero — having updates available is not a failure condition. CI consumers should parse the `--json` output to decide what to do.

## Programmatic use

```php
use ArtisanPackUI\Core\Updates\UpdateChecker;

$report = app( UpdateChecker::class )->check( [
    'minor_only'    => true,   // optional
    'security_only' => false,  // optional
] );

$report['updates'];     // array<int, array<string, mixed>> — per-package update rows
$report['up_to_date'];  // array<string, string> — packages already at the latest
$report['errors'];      // array<int, array{package: string, message: string}>
$report['filters'];     // array{minor_only: bool, security_only: bool}
$report['summary'];     // array{ total_installed, updates_available, major_updates, security_updates, errors }
```

Each update row contains:

- `package` — `artisanpack-ui/<name>`
- `current_version`
- `latest_version`
- `type` — `major`, `minor`, `patch`, or `unknown`
- `security` — `bool`, true when release notes contain a security keyword
- `breaking` — `bool`, true when the update is a major bump
- `notes` — `array<int, string>` of human-readable notes
- `source_url` — canonical source URL from Packagist, or null

## Security keyword detection

A release is flagged as security-related when its notes ( description + keywords pulled from Packagist ) contain any of:

`security`, `vulnerability`, `vulnerabilities`, `cve-`, `xss`, `csrf`, `sql injection`, `remote code execution`

This is a heuristic — a security release that doesn't use any of these words won't be flagged. Pair `--security-only` with manual review of release notes for the strictest workflows.

## Cache TTL

```php
// config/artisanpack.php
'core' => [
    'updates' => [
        'cache_ttl' => 3600,  // seconds
    ],
],
```

Shorter TTLs catch new releases faster but issue more Packagist requests. The default of 1 hour suits most CI / scheduled-task workflows.

Programmatically clear the cache for a single package:

```php
use ArtisanPackUI\Core\Updates\PackagistClient;

app( PackagistClient::class )->forget( 'artisanpack-ui/media-library' );
```

## Test overrides

```php
$checker = app( UpdateChecker::class );

$checker->setPackages( [
    [
        'name'    => 'artisanpack-ui/media-library',
        'version' => '1.1.0',
    ],
] );

$report = $checker->check();
```

`setPackages()` overrides the composer-lock package list so fixtures can be written without booking up a real install.

## Networking behaviour

`PackagistClient::getReleases()` uses Laravel's HTTP client factory with a 10-second timeout. Network failures and non-2xx responses are swallowed and treated as "no releases available" — the update report records them as per-package errors rather than aborting the run. This keeps the command safe to run on offline / firewalled machines.

## When to run this

- **As a scheduled task** in your application to surface available updates to admins.
- **As a CI step** that opens an issue or sends a notification when an update is detected.
- **As a release-prep check** before publishing your own sibling package — confirm Core is up-to-date first.
