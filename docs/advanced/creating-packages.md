---
title: Creating new packages
---

# Creating new ArtisanPack UI packages

`artisanpack:make-package` scaffolds a new sibling package from the blueprint that every existing ArtisanPack UI package follows. The generated package includes a `composer.json` ( wired to the ecosystem's standard dev dependencies ), a service provider that extends `ArtisanPackServiceProvider`, a facade, a config stub, Pest tests via Orchestra Testbench, and the conventional directory layout. Optional flags add views, routes, migrations, an example command, and Livewire components.

## Quick start

```bash
# Minimal package — service provider, facade, config, tests
php artisan artisanpack:make-package artisanpack-ui/new-package

cd new-package
composer install
```

## Validating the name

Package names must match `vendor/package` format and use kebab-case. The command validates this up front:

```
Invalid package name 'NewPackage'. Expected format: vendor/package (e.g. artisanpack-ui/new-package).
```

For sibling packages in this ecosystem, the vendor is always `artisanpack-ui`.

## Output path

By default the package is created in a directory named after the kebab-cased package, relative to the working directory:

```bash
php artisan artisanpack:make-package artisanpack-ui/new-package
# → ./new-package
```

Override with `--path`:

```bash
php artisan artisanpack:make-package artisanpack-ui/new-package --path=/Users/me/Code/ArtisanPack/new-package
```

Existing non-empty directories are protected by default. Pass `--force` to overwrite.

## Optional structure

Combine flags to scaffold additional pieces:

| Flag | Adds |
|---|---|
| `--with-views` | `resources/views/` directory + view loading in the service provider's `bootPackage()` |
| `--with-routes` | `routes/web.php` + route loading in the service provider |
| `--with-migrations` | `database/migrations/` directory + migration loading |
| `--with-commands` | An example Artisan command in `src/Commands/` registered with the service provider |
| `--with-livewire` | An example Livewire component in `src/Livewire/` + Livewire registration in `bootPackage()` |

```bash
# Full-featured scaffold
php artisan artisanpack:make-package artisanpack-ui/new-package \
    --with-livewire --with-views --with-routes --with-migrations --with-commands
```

## What the scaffolded package looks like

```
new-package/
├── composer.json
├── README.md
├── CHANGELOG.md
├── LICENSE
├── phpcs.xml
├── .php-cs-fixer.dist.php
├── phpunit.xml
├── config/
│   └── new-package.php
├── src/
│   ├── NewPackage.php
│   ├── NewPackageServiceProvider.php
│   └── Facades/
│       └── NewPackage.php
└── tests/
    ├── Pest.php
    ├── TestCase.php
    ├── Feature/
    │   └── ExampleTest.php
    └── Unit/
        └── ExampleTest.php
```

The service provider extends `ArtisanPackUI\Core\ArtisanPackServiceProvider` and pre-fills the declarative properties so the package boots correctly without further setup.

The test case extends `ArtisanPackUI\Core\Testing\ArtisanPackTestCase` so you can immediately use the shared assertions, config helpers, and mocking utilities.

## After scaffolding

```bash
cd new-package
composer install
composer test       # run the Pest suite
composer fix        # auto-format with php-cs-fixer
composer lint       # combined fix --dry-run + phpcs check
```

Then iterate:

1. Add your services to `src/`.
2. Register them in the service provider (`$singletons`, `$commands`, `registerPackageBindings()`, `bootPackage()`).
3. Add tests under `tests/Feature/` and `tests/Unit/`.
4. Document the public surface in `docs/`.
5. Update `CHANGELOG.md` and tag a release.

## Adding the package to the dev app

The development app at `/Users/jacobmartella/Herd/artisanpack-ui-dev/` symlinks every ArtisanPack UI package for real-time editing. To wire your new package in:

1. Add a path repository entry to the dev app's `composer.json`:

   ```json
   {
       "type": "path",
       "url": "../../Desktop/ArtisanPack UI Packages/new-package",
       "options": { "symlink": true }
   }
   ```

2. Add the requirement:

   ```bash
   composer require artisanpack-ui/new-package:@dev
   ```

3. Run `php artisan artisanpack:scaffold-config` to merge the new package's config section into the unified file.

## Wiring the package into the InstallPackagesCommand catalog

The interactive installer (`artisanpack:install-packages`) reads its catalog from `$composerPackages` on `InstallPackagesCommand`. Once your new package is published to Packagist, send a PR to the core package adding an entry:

```php
'artisanpack-ui/new-package' => [
    'description' => 'Short description shown in the installer.',
    'dev'         => false,
    'postInstall' => null,  // or an Artisan command to run after install
],
```
