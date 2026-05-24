---
title: Configuration manager
---

# Configuration manager

`ConfigurationManager` is the runtime API every ArtisanPack UI package uses to read, write, merge, and validate the unified `config/artisanpack.php` configuration. It hides the `'artisanpack.<package>.<key>'` namespacing convention behind package-aware helpers so callers never have to assemble dotted strings themselves.

For the bigger picture — schemas, merging defaults, per-environment overrides — see [Installation → Configuration management](../installation/configuration-management.md). This page focuses on the runtime API.

## Resolution

Registered as a container singleton by `CoreServiceProvider`. Resolve it via the container, the facade, or the helper:

```php
use ArtisanPackUI\Core\Config\ConfigurationManager;
use ArtisanPackUI\Core\Facades\ArtisanPackConfig;

$config = app( ConfigurationManager::class );  // Container

ArtisanPackConfig::get( 'media-library', 'enable_webp', true );  // Facade

artisanpack_config( 'media-library.enable_webp', true );          // Helper
```

The helper `artisanpack_config()` accepts a single dot-notation key whose first segment is the package short name and whose remaining segments are relative to that package — e.g. `'media-library.enable_webp'` resolves to `ConfigurationManager::get( 'media-library', 'enable_webp' )`. Malformed keys return the supplied default rather than dispatching a nonsensical lookup against the manager.

## Read API

```php
// Single value
$value = $config->get( 'media-library', 'enable_webp', true );

// Existence check
$exists = $config->has( 'media-library', 'enable_webp' );

// Whole package
$mediaConfig = $config->package( 'media-library' );

// Whole tree
$all = $config->all();
```

## Write API

```php
// In-memory override (does NOT persist to config/artisanpack.php)
$config->set( 'media-library', 'enable_webp', false );
```

`set()` mutates Laravel's in-memory configuration repository. Use this for runtime feature flags, tests, or application-controlled overrides. For persistent changes, edit `config/artisanpack.php`.

## Merging defaults

`merge()` lets a package register its defaults at boot time without re-implementing `mergeConfigFrom()`. Existing user values win:

```php
$config->merge( 'media-library', [
    'enable_webp' => true,
    'enable_avif' => false,
    'sizes' => [
        'thumbnail' => [ 'width' => 150, 'height' => 150 ],
    ],
] );
```

`ArtisanPackServiceProvider` does this automatically when you declare `$configFile` and `$configKey` on a child provider.

## Schemas + validation

Register a schema for a package and validate any configuration array against it:

```php
$config->registerSchema( 'media-library', [
    'enable_webp' => [
        'type'     => 'bool',
        'required' => true,
    ],
    'default_disk' => [
        'type' => 'string',
        'in'   => [ 'public', 's3', 'local' ],
    ],
] );

$result = $config->validate( 'media-library', $config->package( 'media-library' ) );

if ( $result->fails() ) {
    throw new \ArtisanPackUI\Core\Exceptions\ConfigurationException(
        'Invalid media-library configuration: ' . implode( ', ', $result->messages() ),
    );
}
```

Each rule may declare:

- `type` — `string`, `int|integer`, `float|double`, `numeric`, `bool|boolean`, or `array`
- `required` — whether the key must be present
- `in` — list of allowed values

`ValidationResult` exposes `passes(): bool`, `fails(): bool`, `errors(): array<string, array<int, string>>` (keyed by key), and `messages(): array<int, string>` (flat list).

When no schema is registered for the package, `validate()` returns a passing result.

## Facade method map

The `ArtisanPackConfig` facade forwards to the singleton manager:

| Facade method | Manager method |
|---|---|
| `ArtisanPackConfig::get( $package, $key, $default = null )` | `get()` |
| `ArtisanPackConfig::set( $package, $key, $value )` | `set()` |
| `ArtisanPackConfig::has( $package, $key )` | `has()` |
| `ArtisanPackConfig::merge( $package, array $config )` | `merge()` |
| `ArtisanPackConfig::validate( $package, array $config )` | `validate()` |
| `ArtisanPackConfig::all()` | `all()` |
| `ArtisanPackConfig::package( $package )` | `package()` |
| `ArtisanPackConfig::registerSchema( $package, array $schema )` | `registerSchema()` |
| `ArtisanPackConfig::schema( $package )` | `schema()` |
