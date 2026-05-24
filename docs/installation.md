---
title: Installation
---

# Installation

## Install via Composer

```bash
composer require artisanpack-ui/core
```

The package auto-registers via Laravel's package discovery.

## Scaffold the unified config

```bash
php artisan artisanpack:scaffold-config
```

Generates `config/artisanpack.php`, the single configuration file every ArtisanPack UI package merges into. Re-run it any time you add or remove an ArtisanPack UI package — it merges in new sections and preserves any existing customisations. Pass `--force` to overwrite existing keys with the package defaults.

The publish tag `artisanpack-config` is also available if you prefer the standard `vendor:publish` workflow:

```bash
php artisan vendor:publish --tag=artisanpack-config
```

## Verify the install

```bash
php artisan artisanpack:diagnose
```

Runs the five default diagnostic checks — environment, installed packages, configuration, service bindings, and Artisan commands — and prints a report. Pass `--fix` to repair the fixable checks (the missing-config check, today). The command exits non-zero when any check reports an error.

## Deeper topics

- [Requirements](installation/requirements.md) — PHP and Laravel versions, required extensions
- [Configuration](installation/configuration.md) — full `config/artisanpack.php` reference and per-package sections
- [Configuration management](installation/configuration-management.md) — the `ConfigurationManager` service, schemas, validation, layered overrides
- [Environment variables](installation/environment-variables.md) — every env var the package reads
