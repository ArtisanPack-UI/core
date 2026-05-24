---
title: Upgrading from 1.1 to 1.2
---

# Upgrading from 1.1 to 1.2

Core 1.2 is **additive and backwards compatible**. Nothing was removed, no public API changed shape, and every Core 1.1 application continues to work after a `composer update artisanpack-ui/core` with no other changes required.

The release adds a set of new services (`ArtisanPackServiceProvider`, `ConfigurationManager`, `LoggerFactory`, `BladeDirectiveRegistrar`, `DiagnosticRunner`, `CompatibilityChecker`, `UpdateChecker`), four new Artisan commands (`artisanpack:diagnose`, `artisanpack:check-compatibility`, `artisanpack:check-updates`, `artisanpack:make-package`), a base test case (`ArtisanPackTestCase`), and Laravel 13 support.

Migration to the new APIs is **opt-in** — keep using your existing setup until you have a reason to adopt one of the new affordances.

## 1. Bump the requirement

```bash
composer require artisanpack-ui/core:^1.2
```

The composer constraint widens from `^11.0|^12.0` to `^11.0|^12.0|^13.0` on `illuminate/support`. Laravel 11 and 12 remain fully supported. PHP 8.2 remains the minimum.

## 2. Run the diagnose command

Confirm the upgrade landed cleanly:

```bash
php artisan artisanpack:diagnose
```

Expected: every check passes. If `ConfigurationCheck` reports drift, run `php artisan artisanpack:diagnose --fix` to repair the config file.

## 3. (Optional) Migrate sibling packages to `ArtisanPackServiceProvider`

If you maintain a sibling ArtisanPack UI package, you can shrink its service provider by extending `ArtisanPackServiceProvider` and moving the boilerplate into declarative properties. See [Service provider](../usage/service-provider.md) for the migration walkthrough. Existing providers continue to work — there's no hard deadline.

## 4. (Optional) Route logging through the shared stack

Replace direct `Log::info()` calls with the package-scoped logger:

```php
// Before
\Illuminate\Support\Facades\Log::info( '[media-library] Image uploaded.', [ 'id' => 42 ] );

// After
use ArtisanPackUI\Core\Facades\ArtisanPackLog;

ArtisanPackLog::make( 'media-library' )->info( 'Image uploaded.', [ 'id' => 42 ] );
```

The shared stack adds the package prefix automatically, honours the `artisanpack.core.logging.channel` setting, and exposes the `audit()` method + `AuditLogCreated` event for compliance use cases.

## 5. (Optional) Register schemas with `ConfigurationManager`

If your package validates its own configuration today, register a schema instead:

```php
// In your package's service provider bootPackage()
app( \ArtisanPackUI\Core\Config\ConfigurationManager::class )->registerSchema( 'media-library', [
    'enable_webp' => [ 'type' => 'bool', 'required' => true ],
    'default_disk' => [ 'type' => 'string', 'in' => [ 'public', 's3' ] ],
] );
```

Callers can then validate via `$config->validate( 'media-library', $config->package( 'media-library' ) )` and receive a structured `ValidationResult`.

## 6. (Optional) Adopt `ArtisanPackTestCase` in package test suites

If you maintain a sibling package's test suite, replace your hand-rolled `Orchestra\Testbench\TestCase` base class with `ArtisanPackTestCase`:

```php
// Before
abstract class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders( $app ): array
    {
        return [
            \ArtisanPackUI\Core\CoreServiceProvider::class,
            MediaLibraryServiceProvider::class,
        ];
    }
}

// After
abstract class TestCase extends \ArtisanPackUI\Core\Testing\ArtisanPackTestCase
{
    protected function getAdditionalProviders(): array
    {
        return [
            MediaLibraryServiceProvider::class,
        ];
    }
}
```

The new base case wires `CoreServiceProvider` automatically and pulls in `ArtisanPackAssertions`, `InteractsWithConfiguration`, and `MocksArtisanPackServices`.

## 7. (Optional) Wire CI to the new commands

Add the diagnostic / compatibility / update commands to your CI pipeline:

```yaml
- run: php artisan artisanpack:diagnose --json
- run: php artisan artisanpack:check-compatibility --strict --json
- run: php artisan artisanpack:check-updates --security-only --json
```

`--json` makes each command emit a machine-parseable payload. `--strict` on the compatibility check causes warnings to fail the pipeline; the diagnose command always fails the pipeline on errors; the update-check command always exits zero.

## Laravel 13 notes

Laravel 13 requires PHP 8.3+. PHP 8.2 users continue to receive Laravel 11 / 12 — Laravel itself enforces its own PHP constraint, so Composer won't try to install Laravel 13 on a PHP 8.2 host.

Where Core uses Laravel 13-only APIs ( e.g. `Cache::touch()` in `PackagistClient` ), it gates them behind `method_exists()` feature detection rather than a Laravel-version check. The `artisanpack_supports_laravel_13()` helper is available for cases where the gate is broader than a single method.

## Removed items

None. Core 1.1 → 1.2 removes no public APIs.

## See also

- [Changelog](../../CHANGELOG.md) — the full 1.2 release notes
- [Usage](../usage.md) — full documentation for every new service
- [API Reference](../api/reference.md) — exhaustive public-surface listing
