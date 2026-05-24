---
title: Artisan commands
---

# Artisan commands

ArtisanPack UI Core registers six Artisan commands. Every command supports `--help` for a full option listing.

| Command | Purpose | Added |
|---|---|---|
| `artisanpack:scaffold-config` | Generate or refresh the unified `config/artisanpack.php` file. | 1.0 |
| `artisanpack:install-packages` | Interactive installer for sibling ArtisanPack UI packages (Composer + NPM). | 1.1 |
| `artisanpack:diagnose` | Run the diagnostic suite and report installation health. | 1.2 |
| `artisanpack:check-compatibility` | Analyse cross-package / PHP / Laravel / extension compatibility. | 1.2 |
| `artisanpack:check-updates` | Check Packagist for available updates to installed packages. | 1.2 |
| `artisanpack:make-package` | Scaffold a new ArtisanPack UI package from the standard blueprint. | 1.2 |

## `artisanpack:scaffold-config`

Generates `config/artisanpack.php` ( the unified configuration file every ArtisanPack UI package merges into ) and adds a section for every installed sibling package.

```bash
php artisan artisanpack:scaffold-config            # preserves existing keys (default)
php artisan artisanpack:scaffold-config --force    # overwrites existing keys with package defaults
```

Re-run any time you install or remove a sibling package to keep the config in sync.

## `artisanpack:install-packages`

Interactive installer that walks you through choosing sibling Composer packages (and NPM packages), confirms before running, then triggers `artisanpack:scaffold-config` for you.

```bash
# Interactive
php artisan artisanpack:install-packages

# Non-interactive — explicit selection
php artisan artisanpack:install-packages \
    --packages=artisanpack-ui/security,artisanpack-ui/media-library \
    --npm-packages=@artisanpack-ui/livewire-drag-and-drop

# Non-interactive — install everything
php artisan artisanpack:install-packages --all

# Skip the NPM prompt entirely
php artisan artisanpack:install-packages --skip-npm

# Skip the automatic scaffold-config call
php artisan artisanpack:install-packages --skip-scaffold

# Skip per-package post-install commands
php artisan artisanpack:install-packages --skip-post-install

# Preview what would happen
php artisan artisanpack:install-packages --dry-run
```

## `artisanpack:diagnose`

Runs the five default diagnostic checks and prints a report:

```bash
php artisan artisanpack:diagnose

# JSON for scripting / CI
php artisan artisanpack:diagnose --json

# Restrict to a single check
php artisan artisanpack:diagnose --package=environment

# Auto-fix fixable checks (today: the missing-config check)
php artisan artisanpack:diagnose --fix
```

Exits non-zero when any check reports an error. See [Diagnostics](diagnostics.md) for the full check inventory.

## `artisanpack:check-compatibility`

Walks every installed ArtisanPack UI package's Composer requirements and confirms the local environment satisfies them.

```bash
php artisan artisanpack:check-compatibility

# JSON for scripting / CI
php artisan artisanpack:check-compatibility --json

# Treat warnings as errors (non-zero exit on warnings)
php artisan artisanpack:check-compatibility --strict

# Restrict the report to a single package
php artisan artisanpack:check-compatibility --package=artisanpack-ui/media-library
```

Exits non-zero when any error-severity row is reported. See [Compatibility analysis](compatibility.md) for the full report shape.

## `artisanpack:check-updates`

Reads `composer.lock`, asks Packagist for the latest stable release of every installed ArtisanPack UI package, and reports the available updates.

```bash
php artisan artisanpack:check-updates

# JSON for scripting / CI
php artisan artisanpack:check-updates --json

# Skip major updates (only minor + patch)
php artisan artisanpack:check-updates --minor-only

# Only security-related releases
php artisan artisanpack:check-updates --security-only

# Include changelog excerpts + source URLs
php artisan artisanpack:check-updates --changelog
```

Always exits zero. See [Updates](updates.md) for the full report shape and caching behaviour.

## `artisanpack:make-package`

Scaffolds a new ArtisanPack UI package from the standard blueprint. Use this when starting a new sibling package — it generates a `composer.json`, service provider, facade, config stub, tests, and the conventional directory layout in one command.

```bash
# Minimal package
php artisan artisanpack:make-package artisanpack-ui/new-package

# Custom output path
php artisan artisanpack:make-package artisanpack-ui/new-package --path=/tmp/scaffold

# Include Livewire components scaffolding
php artisan artisanpack:make-package artisanpack-ui/new-package --with-livewire

# Include views directory + Blade registration
php artisan artisanpack:make-package artisanpack-ui/new-package --with-views

# Include routes file + route loading
php artisan artisanpack:make-package artisanpack-ui/new-package --with-routes

# Include migrations directory + migration loading
php artisan artisanpack:make-package artisanpack-ui/new-package --with-migrations

# Include an example Artisan command
php artisan artisanpack:make-package artisanpack-ui/new-package --with-commands

# Combine flags — full-featured scaffold
php artisan artisanpack:make-package artisanpack-ui/new-package \
    --with-livewire --with-views --with-routes --with-migrations --with-commands

# Overwrite an existing scaffold
php artisan artisanpack:make-package artisanpack-ui/new-package --force
```

See [Creating new packages](../advanced/creating-packages.md) for a complete walk-through.
