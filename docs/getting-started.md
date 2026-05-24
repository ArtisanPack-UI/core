---
title: Getting Started with ArtisanPack UI Core
---

# Getting Started

ArtisanPack UI Core is the foundation every other ArtisanPack UI package depends on. It provides the unified `config/artisanpack.php` file, the `ArtisanPackServiceProvider` base class, the logging and Blade-directive helpers, and the `artisanpack:*` Artisan commands that diagnose, audit, and update your ArtisanPack UI installation.

## Install

```bash
composer require artisanpack-ui/core
```

The package auto-registers through Laravel's package discovery.

## Scaffold the unified config

Generate `config/artisanpack.php` ( the single config file every ArtisanPack UI package merges into ):

```bash
php artisan artisanpack:scaffold-config
```

Re-run the command any time you add a new ArtisanPack UI package — it merges that package's section into the existing config and preserves any customisations you have already made. Pass `--force` to overwrite existing keys with the package defaults.

## Run diagnostics

Confirm the environment, installed packages, configuration, container bindings, and Artisan commands all line up:

```bash
php artisan artisanpack:diagnose
```

Pass `--json` for a machine-parseable payload, `--fix` to auto-repair fixable checks ( such as a missing configuration file ), or `--package=<id>` to focus on a single check.

## First config read

Read a value out of the unified config from any service provider, controller, or Livewire component:

```php
// Helper
$theme = artisanpack_config( 'livewire-ui-components.theme.primary', '#3b82f6' );

// Facade
use ArtisanPackUI\Core\Facades\ArtisanPackConfig;

$theme = ArtisanPackConfig::get( 'livewire-ui-components', 'theme.primary', '#3b82f6' );
```

## First log entry

Get a logger scoped to your package and write a structured entry:

```php
use ArtisanPackUI\Core\Facades\ArtisanPackLog;

$log = ArtisanPackLog::make( 'media-library' );

$log->info( 'Image uploaded.', [
    'media_id' => 42,
    'size'     => '1.2 MB',
] );

$log->audit( 'media.uploaded', [
    'media_id' => 42,
] );
```

Audit calls additionally dispatch an `AuditLogCreated` event your application can listen for and persist to a database.

## Next steps

- [Installation Guide](installation.md) — requirements, configuration management, environment variables
- [Usage Guide](usage.md) — full surface ( service provider, config manager, logging, diagnostics, compatibility, updates, Blade directives, testing utilities )
- [API Reference](api.md) — every public class, method, and helper
