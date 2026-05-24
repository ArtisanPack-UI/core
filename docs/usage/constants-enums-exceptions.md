---
title: Constants, enums, exceptions
---

# Constants, enums, exceptions

The shared primitives every ArtisanPack UI package consumes. None of them require additional configuration — they're just stable, importable values that prevent the ecosystem from re-inventing the wheel.

## `Constants`

`ArtisanPackUI\Core\Constants` exposes the canonical package identifiers, configuration keys, default values, and version baselines used across the ecosystem.

### Package identifiers

```php
use ArtisanPackUI\Core\Constants;

Constants::PACKAGE_CORE;             // 'artisanpack-ui/core'
Constants::PACKAGE_ACCESSIBILITY;    // 'artisanpack-ui/accessibility'
Constants::PACKAGE_SECURITY;         // 'artisanpack-ui/security'
Constants::PACKAGE_MEDIA_LIBRARY;    // 'artisanpack-ui/media-library'
Constants::PACKAGE_LIVEWIRE_UI;      // 'artisanpack-ui/livewire-ui-components'
Constants::PACKAGE_HOOKS;            // 'artisanpack-ui/hooks'
Constants::PACKAGE_ICONS;            // 'artisanpack-ui/icons'
Constants::PACKAGE_CMS;              // 'artisanpack-ui/cms-framework'
Constants::PACKAGE_CODE_STYLE;       // 'artisanpack-ui/code-style'
Constants::PACKAGE_CODE_STYLE_PINT;  // 'artisanpack-ui/code-style-pint'
```

### Configuration keys

```php
Constants::CONFIG_KEY;                 // 'artisanpack'
Constants::CONFIG_KEY_ACCESSIBILITY;   // 'artisanpack.accessibility'
Constants::CONFIG_KEY_SECURITY;        // 'artisanpack.security'
Constants::CONFIG_KEY_MEDIA;           // 'artisanpack.media-library'
Constants::CONFIG_KEY_LIVEWIRE_UI;     // 'artisanpack.livewire-ui-components'
Constants::CONFIG_KEY_HOOKS;           // 'artisanpack.hooks'
Constants::CONFIG_KEY_ICONS;           // 'artisanpack.icons'
Constants::CONFIG_KEY_CMS;             // 'artisanpack.cms-framework'
```

### Defaults

```php
Constants::DEFAULT_TOAST_DURATION;     // 3000 (ms)
Constants::DEFAULT_PAGINATION_SIZE;    // 15
Constants::DEFAULT_MAX_FILE_SIZE;      // 10240 (KB — 10 MB)
```

### Version baselines

```php
Constants::MIN_PHP_VERSION;            // '8.2.0'
Constants::MIN_LARAVEL_VERSION;        // '10.0.0'
```

## Enums

### `LogLevel`

```php
use ArtisanPackUI\Core\Enums\LogLevel;

LogLevel::DEBUG;     // 'debug'
LogLevel::INFO;      // 'info'
LogLevel::WARNING;   // 'warning'
LogLevel::ERROR;     // 'error'
LogLevel::CRITICAL;  // 'critical'
```

Standard PSR-3-aligned severity levels used by the shared `Logger`.

### `Package`

```php
use ArtisanPackUI\Core\Enums\Package;

Package::CORE;            // 'artisanpack-ui/core'
Package::ACCESSIBILITY;   // 'artisanpack-ui/accessibility'
Package::SECURITY;        // 'artisanpack-ui/security'
Package::MEDIA_LIBRARY;   // 'artisanpack-ui/media-library'
Package::LIVEWIRE_UI;     // 'artisanpack-ui/livewire-ui-components'
Package::HOOKS;           // 'artisanpack-ui/hooks'
Package::ICONS;           // 'artisanpack-ui/icons'
Package::CMS;             // 'artisanpack-ui/cms-framework'
```

Each case exposes:

- `configKey(): string` — returns the dot-notation configuration key (e.g. `Package::MEDIA_LIBRARY->configKey()` → `'artisanpack.media-library'`)
- `shortName(): string` — strips the vendor prefix (e.g. `Package::MEDIA_LIBRARY->shortName()` → `'media-library'`)

The dev-tooling packages (`code-style`, `code-style-pint`) are intentionally omitted because they ship no runtime configuration. Their identifiers remain available as `Constants::PACKAGE_*` constants.

### `Priority`

```php
use ArtisanPackUI\Core\Enums\Priority;

Priority::LOW;       // 'low'
Priority::MEDIUM;    // 'medium'
Priority::HIGH;      // 'high'
Priority::CRITICAL;  // 'critical'
```

Generic priority enum used for ordering and triage across the ecosystem.

### `Status`

```php
use ArtisanPackUI\Core\Enums\Status;

Status::PENDING;   // 'pending'
Status::ACTIVE;    // 'active'
Status::INACTIVE;  // 'inactive'
Status::DELETED;   // 'deleted'
```

Generic lifecycle status enum suitable for records across the ecosystem.

## Exceptions

`ArtisanPackException` is the base class every ArtisanPack UI exception extends. Catch it to handle any error from the ecosystem in a single block; catch a specialised subclass to handle a specific failure mode.

```php
use ArtisanPackUI\Core\Exceptions\ArtisanPackException;
use ArtisanPackUI\Core\Exceptions\ConfigurationException;
use ArtisanPackUI\Core\Exceptions\DependencyException;
use ArtisanPackUI\Core\Exceptions\InstallationException;
use ArtisanPackUI\Core\Exceptions\ServiceException;
use ArtisanPackUI\Core\Exceptions\ValidationException;

try {
    // ...
} catch ( ConfigurationException $e ) {
    // Missing, invalid, or conflicting configuration
} catch ( ValidationException $e ) {
    // Data failed validation rules
} catch ( DependencyException $e ) {
    // Required package dependency is missing or incompatible
} catch ( InstallationException $e ) {
    // Package failed to install or publish required assets
} catch ( ServiceException $e ) {
    // Unrecoverable runtime error in a package service
} catch ( ArtisanPackException $e ) {
    // Anything else from the ArtisanPack UI ecosystem
}
```

### Throwing your own

Sibling packages should throw the specialised subclass whose semantics best match the failure:

```php
use ArtisanPackUI\Core\Exceptions\ConfigurationException;

if ( ! $config->has( 'media-library', 'default_disk' ) ) {
    throw new ConfigurationException(
        'media-library: the "default_disk" key is required.',
    );
}
```

For brand-new failure categories, extend `ArtisanPackException` rather than throwing a vanilla `Exception` — downstream applications can then catch the new failure via the base class without needing to update their try/catch.
