---
title: Configuration
---

# Configuration

ArtisanPack UI Core publishes a single `config/artisanpack.php` file. Every ArtisanPack UI package merges its section into that file, so the application owner has one place to override defaults across the whole ecosystem.

## Publishing the config

```bash
php artisan artisanpack:scaffold-config
```

Generates `config/artisanpack.php`. Re-run it after installing or removing a sibling package to keep the file in sync. Existing keys are preserved by default; pass `--force` to overwrite them with the package defaults.

If you prefer Laravel's standard publishing workflow:

```bash
php artisan vendor:publish --tag=artisanpack-config
```

## File structure

The file is a flat map keyed by the kebab-cased package name. The Core package owns the `core` key:

```php
<?php

return [

    'core' => [

        // Shared logging configuration (consumed by every package's logger).
        'logging' => [
            'channel' => env( 'ARTISANPACK_LOG_CHANNEL' ),
            'audit'   => [
                'enabled' => true,
            ],
        ],

    ],

    // Other ArtisanPack UI packages merge their keys here, e.g. 'media-library',
    // 'cms-framework', 'livewire-ui-components', 'security'.

];
```

## Core keys

### `core.logging.channel`

Type: `string|null`. Default: `null` ( falls back to Laravel's default log channel ).

Name of the Laravel log channel that every ArtisanPack UI package writes through. The channel must exist in your application's `config/logging.php`. Setting it routes every entry — informational, warning, error, and audit — to that channel so aggregated streams are easy to filter.

The default value reads from the `ARTISANPACK_LOG_CHANNEL` environment variable.

### `core.logging.audit.enabled`

Type: `bool`. Default: `true`.

Whether `$log->audit()` calls actually persist an entry and dispatch an `AuditLogCreated` event. Set to `false` to silently drop audit calls in environments that should not record them ( e.g. local development that does not want production-style audit log noise ).

## Updates keys

### `core.updates.cache_ttl`

Type: `int` ( seconds ). Default: `3600`.

How long the `PackagistClient` caches Packagist release metadata for. The `artisanpack:check-updates` command refreshes this cache on every read so frequently-queried packages keep their entries warm.

## Adding a package-specific section

After installing a sibling package — say `artisanpack-ui/media-library` — re-run `artisanpack:scaffold-config` and the command merges the package's defaults into a new top-level key:

```php
return [

    'core' => [
        // ...
    ],

    'media-library' => [
        // Defaults merged from artisanpack-ui/media-library's published config.
    ],

];
```

Override individual values in place. Re-running the scaffold command will not clobber your overrides unless you pass `--force`.

## Reading values

Prefer the `artisanpack_config()` helper or the `ArtisanPackConfig` facade over `config('artisanpack.…')` — they go through `ConfigurationManager`, which supports schema validation and runtime overrides:

```php
$theme = artisanpack_config( 'livewire-ui-components.theme.primary', '#3b82f6' );

use ArtisanPackUI\Core\Facades\ArtisanPackConfig;

$theme = ArtisanPackConfig::get( 'livewire-ui-components', 'theme.primary', '#3b82f6' );
```

See [Configuration management](configuration-management.md) for the full `ConfigurationManager` surface ( `set`, `merge`, `validate`, schemas ).
