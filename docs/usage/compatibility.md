---
title: Compatibility analysis
---

# Compatibility analysis

`CompatibilityChecker` walks every installed ArtisanPack UI package's Composer requirements and confirms that the local environment satisfies them. It surfaces four categories of issue:

1. **Inter-package dependencies** — does each `artisanpack-ui/*` requirement resolve to a compatible installed version?
2. **PHP version** — does the running PHP satisfy each package's `"php"` constraint?
3. **Laravel version** — does the running framework satisfy each package's `illuminate/*` constraint?
4. **PHP extensions** — is every required `ext-*` extension loaded?

A failing row also generates an actionable recommendation.

## Running the report

```bash
# Full report
php artisan artisanpack:check-compatibility

# JSON payload (suitable for CI / scripting)
php artisan artisanpack:check-compatibility --json

# Treat warnings as errors (non-zero exit on warnings)
php artisan artisanpack:check-compatibility --strict

# Restrict the report to a single package
php artisan artisanpack:check-compatibility --package=artisanpack-ui/media-library
```

The command exits non-zero when any error-severity row is reported (or any warning-severity row, when `--strict` is supplied).

## Programmatic use

Resolve the checker from the container and call `analyze()` directly:

```php
use ArtisanPackUI\Core\Compatibility\CompatibilityChecker;

$report = app( CompatibilityChecker::class )->analyze();

// Or restrict to a single package
$report = app( CompatibilityChecker::class )->analyze( 'artisanpack-ui/media-library' );

$report['php_version'];          // (string) e.g. '8.3.10'
$report['laravel_version'];      // (string) e.g. '12.1.0'
$report['installed_packages'];   // array<string, string> package => version
$report['dependency_results'];   // array<int, array<string, mixed>>
$report['php_results'];          // array<int, array<string, mixed>>
$report['laravel_results'];      // array<int, array<string, mixed>>
$report['extension_results'];    // array<int, array<string, mixed>>
$report['recommendations'];      // array<int, string>
$report['summary'];              // array{errors: int, warnings: int}
```

## Result severities

Each row's `severity` is `pass`, `warning`, or `error`:

| Severity | Meaning |
|---|---|
| `pass` | Constraint satisfied. |
| `warning` | Soft failure — typically a missing optional dependency. |
| `error` | Hard failure — constraint cannot be satisfied with the current install. |

The summary section rolls these up into `errors` and `warnings` counts.

## Test overrides

The checker exposes setter methods so tests can stub each input without touching the host environment:

```php
$checker = app( CompatibilityChecker::class );

$checker
    ->setPhpVersion( '8.2.0' )
    ->setLaravelVersion( '11.0.0' )
    ->setLoadedExtensions( [ 'ctype', 'json', 'mbstring' ] )
    ->setPackages( [
        [
            'name'    => 'artisanpack-ui/media-library',
            'version' => '1.2.0',
            'require' => [
                'php'                 => '^8.2',
                'illuminate/support'  => '^11.0|^12.0',
                'artisanpack-ui/core' => '^1.2',
                'ext-gd'              => '*',
            ],
        ],
    ] );

$report = $checker->analyze();
```

`setPackages()` accepts the raw composer-lock-shaped array ( `name`, `version`, `require` ) so fixtures can be written without booking up a real install.

## When to run this

- **In CI**, after every dependency upgrade.
- **Before publishing a release** to confirm the package's declared constraints still work with the current sibling-package versions.
- **After a major Laravel upgrade** (10 → 11, 11 → 12, 12 → 13) to catch sibling packages that have not yet widened their `illuminate/*` constraint.
