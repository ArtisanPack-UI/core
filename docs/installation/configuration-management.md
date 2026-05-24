---
title: Configuration management
---

# Configuration management

`config/artisanpack.php` is the static, file-based source of truth. `ConfigurationManager` is the runtime API every ArtisanPack UI package uses to read, write, merge, and validate that configuration consistently — so callers never have to assemble `artisanpack.<package>.<key>` dotted strings or hand-roll merging logic.

Resolve the manager from the container, or use the `ArtisanPackConfig` facade / `artisanpack_config()` helper for one-off reads.

## Reading values

The dot-notation key is **relative to the package**. The `'artisanpack.'` prefix and the package key are added automatically.

```php
use ArtisanPackUI\Core\Config\ConfigurationManager;

$config = app( ConfigurationManager::class );

$theme   = $config->get( 'livewire-ui-components', 'theme.primary', '#3b82f6' );
$enabled = $config->has( 'media-library', 'enable_webp' );
$section = $config->package( 'media-library' );  // full array for the package
$all     = $config->all();                       // every package's configuration
```

Or via the helpers:

```php
$theme = artisanpack_config( 'livewire-ui-components.theme.primary', '#3b82f6' );

use ArtisanPackUI\Core\Facades\ArtisanPackConfig;
$theme = ArtisanPackConfig::get( 'livewire-ui-components', 'theme.primary', '#3b82f6' );
```

## Writing values at runtime

`set()` mutates the in-memory configuration repository — useful for tests, for runtime feature flags, or for application-controlled overrides:

```php
$config->set( 'media-library', 'enable_webp', false );
```

This does NOT write back to `config/artisanpack.php`. Persistent overrides belong in that file.

## Merging package defaults

When an ArtisanPack UI package wants to register its own defaults at boot time, it calls `merge()` rather than reimplementing `mergeConfigFrom()`. Existing user values win — the merge is recursive and `array_replace_recursive` semantics apply:

```php
$config->merge( 'media-library', [
    'enable_webp' => true,
    'enable_avif' => false,
    'sizes' => [
        'thumbnail' => [ 'width' => 150, 'height' => 150 ],
    ],
] );
```

`ArtisanPackServiceProvider` does this for you automatically when you declare `$configFile` and `$configKey` on a child provider.

## Schemas + validation

Register a lightweight schema for a package and call `validate()` to confirm a configuration array conforms to it. Each rule is keyed by a dot-notation key relative to the package and may declare any combination of:

- `type` — one of `string`, `int|integer`, `float|double`, `numeric`, `bool|boolean`, `array`
- `required` — whether the key must be present
- `in` — list of allowed values

```php
$config->registerSchema( 'media-library', [
    'enable_webp' => [
        'type'     => 'bool',
        'required' => true,
    ],
    'default_disk' => [
        'type'     => 'string',
        'in'       => [ 'public', 's3', 'local' ],
        'required' => true,
    ],
] );

$result = $config->validate( 'media-library', $config->package( 'media-library' ) );

if ( $result->fails() ) {
    throw new \ArtisanPackUI\Core\Exceptions\ConfigurationException(
        'Invalid media-library configuration: ' . implode( ', ', $result->messages() ),
    );
}
```

`ValidationResult` exposes:

- `passes(): bool` / `fails(): bool`
- `errors(): array<string, array<int, string>>` — keyed by configuration key
- `messages(): array<int, string>` — flat list of messages

When no schema has been registered for the package, `validate()` returns a passing result.

## Per-environment overrides

Mirror Laravel's standard pattern — let the file-based config read from environment variables and override values in `.env`:

```php
// config/artisanpack.php
'core' => [
    'logging' => [
        'channel' => env( 'ARTISANPACK_LOG_CHANNEL' ),
        'audit'   => [
            'enabled' => env( 'ARTISANPACK_AUDIT_ENABLED', true ),
        ],
    ],
],
```

```env
# .env.production
ARTISANPACK_LOG_CHANNEL=stack
ARTISANPACK_AUDIT_ENABLED=true

# .env.local
ARTISANPACK_AUDIT_ENABLED=false
```

## Container resolution

`ConfigurationManager` is registered as a singleton by `CoreServiceProvider`. The same instance is shared across requests, so `registerSchema()` calls persist for the lifetime of the application:

```php
$this->app->singleton(
    \ArtisanPackUI\Core\Config\ConfigurationManager::class,
    fn ( $app ) => new \ArtisanPackUI\Core\Config\ConfigurationManager(
        $app->make( \Illuminate\Contracts\Config\Repository::class ),
    ),
);
```
