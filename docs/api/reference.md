---
title: API Reference
---

# API Reference

Complete reference for the public surface of `artisanpack-ui/core`. Every class, method, helper function, facade, command, exception, and enum that downstream packages and applications can rely on.

## Helper functions

Defined globally in `src/helpers.php` and autoloaded via Composer.

### `artisanpack_config()`

```php
function artisanpack_config( string $key, mixed $default = null ): mixed;
```

Read a value out of the unified `config/artisanpack.php`. `$key` is dot-notation where the first segment is the package short name and the remaining segments are relative to that package — e.g. `'media-library.enable_webp'`.

Malformed keys ( missing the package segment, missing the config segment, or empty either side of the dot ) return `$default` rather than dispatching a nonsensical lookup against the `ConfigurationManager`.

### `artisanpack_blade()`

```php
function artisanpack_blade(): \ArtisanPackUI\Core\View\BladeDirectiveRegistrar;
```

Resolve the shared `BladeDirectiveRegistrar` from the container. See [Blade directives](../usage/blade-directives.md).

### `artisanpack_supports_laravel_13()`

```php
function artisanpack_supports_laravel_13(): bool;
```

Returns `true` when the current Laravel runtime is 13.0 or newer. Use this when guarding behaviour that depends on Laravel 13-only APIs. Prefer feature detection (`method_exists()`) for individual APIs; reach for this helper only when the gate is broader than a single method.

### `apCurrentSiteId()`

```php
function apCurrentSiteId(): int|string|null;
```

Returns the identifier of the site the current code is running for, by delegating to the shared `SiteContext`. Returns `null` when no site is in context, which consumers read as "do not scope". See [Site resolution](../usage/site-resolution.md).

### `getToastDuration()`

```php
function getToastDuration(): float|int;
```

Returns the configured toast notification duration in seconds. Reads from `artisanpack.core.toast_duration`, default `5`.

## Facades

### `ArtisanPackUI\Core\Facades\Core`

```php
class Core extends \Illuminate\Support\Facades\Facade
{
    protected static function getFacadeAccessor(): string;  // 'core'
}
```

Resolves the marker `Core` service.

### `ArtisanPackUI\Core\Facades\ArtisanPackConfig`

Facade for `ConfigurationManager`. Forwards every public method:

```php
ArtisanPackConfig::get( string $package, string $key, mixed $default = null ): mixed;
ArtisanPackConfig::set( string $package, string $key, mixed $value ): void;
ArtisanPackConfig::has( string $package, string $key ): bool;
ArtisanPackConfig::merge( string $package, array $config ): array;
ArtisanPackConfig::validate( string $package, array $config ): ValidationResult;
ArtisanPackConfig::all(): array;
ArtisanPackConfig::package( string $package ): array;
ArtisanPackConfig::registerSchema( string $package, array $schema ): void;
ArtisanPackConfig::schema( string $package ): ?ConfigurationSchema;
```

### `ArtisanPackUI\Core\Facades\ArtisanPackLog`

Facade for `LoggerFactory`:

```php
ArtisanPackLog::make( string $package ): ArtisanPackLogger;
ArtisanPackLog::has( string $package ): bool;
ArtisanPackLog::forget( ?string $package = null ): void;
ArtisanPackLog::all(): array<string, ArtisanPackLogger>;
```

### `ArtisanPackUI\Core\Facades\ArtisanPackSite`

Facade for `SiteContext`:

```php
ArtisanPackSite::currentSiteId(): int|string|null;
ArtisanPackSite::hasSite(): bool;
ArtisanPackSite::enabled(): bool;
ArtisanPackSite::resolver(): SiteResolver;
ArtisanPackSite::setSiteId( int|string|null $siteId ): SiteContext;
ArtisanPackSite::forget(): SiteContext;
ArtisanPackSite::forSite( int|string|null $siteId, Closure $callback ): mixed;
ArtisanPackSite::withoutSite( Closure $callback ): mixed;
ArtisanPackSite::flush(): SiteContext;
```

### `ArtisanPackUI\Core\Facades\ArtisanPackFacade`

Base facade class for every ArtisanPack UI facade. Provides consistent error messages when the underlying service is missing, plus helper methods for probing service availability. Extend it from your own package facades.

## Core service

### `ArtisanPackUI\Core\Core`

Marker class bound under the `'core'` container alias. Resolved through the `Core` facade. The class itself is intentionally empty — package functionality is exposed through the dedicated services below.

### `ArtisanPackUI\Core\CoreServiceProvider`

The application-side service provider. Registered automatically via Laravel's package discovery. Binds every shared service as a container singleton and publishes the `artisanpack-config` tag.

## Base service provider

### `ArtisanPackUI\Core\ArtisanPackServiceProvider`

Abstract base class for every ArtisanPack UI sibling-package service provider.

**Properties:**

| Property | Type | Purpose |
|---|---|---|
| `$configFile` | `?string` | Absolute path to the package config file. |
| `$configKey` | `?string` | Configuration key the package config is merged under. |
| `$configPublishTag` | `?string` | Publish tag (defaults to `<configKey>-config`). |
| `$commands` | `array<class-string>` | Artisan commands registered in console. |
| `$singletons` | `array<int\|string, callable\|class-string>` | Container singletons. |
| `$viewsPath` | `?string` | Absolute path to package views. |
| `$viewsNamespace` | `?string` | View namespace. |
| `$routesPath` | `?string` | Absolute path to package routes. |

**Required override:** `protected function registerPackageBindings(): void`

**Optional override:** `protected function bootPackage(): void`

See [Service provider](../usage/service-provider.md) for the boot order and full usage details.

## Constants

### `ArtisanPackUI\Core\Constants`

`final` class exposing package identifiers, config keys, default values, and version baselines. See [Constants, enums, exceptions](../usage/constants-enums-exceptions.md) for the full inventory.

## Configuration

### `ArtisanPackUI\Core\Config\ConfigurationManager`

```php
public function get( string $package, string $key, mixed $default = null ): mixed;
public function set( string $package, string $key, mixed $value ): void;
public function has( string $package, string $key ): bool;
public function merge( string $package, array $config ): array;
public function validate( string $package, array $config ): ValidationResult;
public function all(): array;
public function package( string $package ): array;
public function registerSchema( string $package, array $schema ): void;
public function schema( string $package ): ?ConfigurationSchema;
```

Container singleton resolved by `CoreServiceProvider`.

### `ArtisanPackUI\Core\Config\ConfigurationSchema`

```php
public function __construct( array $rules = [] );
public function rules(): array;
public function validate( array $config ): ValidationResult;
```

### `ArtisanPackUI\Core\Config\ValidationResult`

```php
public function passes(): bool;
public function fails(): bool;
public function errors(): array<string, array<int, string>>;
public function messages(): array<int, string>;
```

## Logging

### `ArtisanPackUI\Core\Logging\ArtisanPackLogger` (interface)

```php
public function debug( string $message, array $context = [] ): void;
public function info( string $message, array $context = [] ): void;
public function warning( string $message, array $context = [] ): void;
public function error( string $message, array $context = [] ): void;
public function audit( string $action, array $data = [] ): void;
public function package(): string;
```

### `ArtisanPackUI\Core\Logging\Logger`

Default `ArtisanPackLogger` implementation backed by Laravel's `LogManager`. Constructor signature:

```php
public function __construct(
    string $package,
    \Illuminate\Log\LogManager $logManager,
    \Illuminate\Contracts\Events\Dispatcher $dispatcher,
    ?string $channel = null,
    bool $auditEnabled = true,
    ?callable $actorResolver = null,
    ?callable $requestResolver = null,
);
```

### `ArtisanPackUI\Core\Logging\LoggerFactory`

```php
public function make( string $package ): ArtisanPackLogger;
public function has( string $package ): bool;
public function forget( ?string $package = null ): void;
public function all(): array<string, ArtisanPackLogger>;
```

Container singleton resolved by `CoreServiceProvider`. Caches one logger per package.

## Events

### `ArtisanPackUI\Core\Events\AuditLogCreated`

```php
public function __construct( public readonly array $data );
```

Dispatched by `Logger::audit()`. `$data` contains `package`, `action`, `data`, `user_id`, `ip_address`, `timestamp`.

## Blade directives

### `ArtisanPackUI\Core\View\BladeDirectiveRegistrar`

```php
public function echo( string $name, string $function ): void;
public function escaped( string $name, string $function ): void;
public function conditional( string $name, string $condition ): void;
public function wrap( string $name, string $openTag, string $closeTag ): void;
public function livewireAction( string $name, string $action ): void;
public function facade( string $name, string $facadeClass, string $method ): void;
```

Container singleton resolved by `CoreServiceProvider`. Names must start with a letter or underscore and contain only word characters; invalid names throw `InvalidArgumentException`.

## Diagnostics

### `ArtisanPackUI\Core\Diagnostics\DiagnosticRunner`

```php
public function __construct( \Illuminate\Contracts\Foundation\Application $app );
public function setChecks( array $checks ): self;
public function checks(): array<int, DiagnosticCheck>;
public function run( ?string $only = null ): DiagnosticReport;
public function fixableChecks( ?string $only = null ): array<int, DiagnosticCheck>;
```

### `ArtisanPackUI\Core\Diagnostics\DiagnosticReport`

```php
public readonly array $sections;
public function allResults(): array<int, DiagnosticResult>;
public function passedCount(): int;
public function warningCount(): int;
public function errorCount(): int;
public function hasErrors(): bool;
public function toArray(): array<string, mixed>;
```

### `ArtisanPackUI\Core\Diagnostics\DiagnosticResult`

```php
public function __construct(
    public readonly string $label,
    public readonly DiagnosticStatus $status,
    public readonly string $message = '',
    public readonly array $details = [],
);
public function passed(): bool;
public function isWarning(): bool;
public function isError(): bool;
public function toArray(): array<string, mixed>;
```

### `ArtisanPackUI\Core\Diagnostics\DiagnosticStatus` (enum)

`Pass`, `Warning`, `Error`, `Info`. Each case exposes `symbol(): string`.

### `ArtisanPackUI\Core\Diagnostics\Checks\DiagnosticCheck` (interface)

```php
public function id(): string;
public function name(): string;
public function run(): array<int, DiagnosticResult>;
public function supportsFix(): bool;
public function fix( callable $writer ): bool;
```

Default implementations: `ArtisanCommandsCheck`, `ConfigurationCheck`, `EnvironmentCheck`, `InstalledPackagesCheck`, `ServiceBindingsCheck`.

### `ArtisanPackUI\Core\Diagnostics\Support\ComposerLock`

```php
public static function packages(): array<int, array<string, mixed>>;
```

Defensive accessor for `composer.lock`. Returns an empty array when the file is missing, unreadable, or malformed.

## Compatibility

### `ArtisanPackUI\Core\Compatibility\CompatibilityChecker`

```php
public function __construct( \Illuminate\Contracts\Foundation\Application $app );
public function setPackages( array $packages ): self;
public function setPhpVersion( string $version ): self;
public function setLaravelVersion( string $version ): self;
public function setLoadedExtensions( array $extensions ): self;
public function analyze( ?string $packageFilter = null ): array<string, mixed>;
public function packageIsInstalled( string $name ): bool;
```

## Updates

### `ArtisanPackUI\Core\Updates\PackagistClient`

```php
public function __construct(
    \Illuminate\Http\Client\Factory $http,
    \Illuminate\Contracts\Cache\Repository $cache,
    \Illuminate\Contracts\Config\Repository $config,
);
public function getReleases( string $package ): array<int, array<string, mixed>>;
public function getLatestRelease( string $package ): ?array<string, mixed>;
public function forget( string $package ): void;
```

### `ArtisanPackUI\Core\Updates\UpdateChecker`

```php
public function __construct( PackagistClient $client );
public function setPackages( array $packages ): self;
public function check( array $filters = [] ): array<string, mixed>;
```

`$filters` accepts `minor_only: bool` and `security_only: bool` keys.

## Site resolution

Additive and inert until `artisanpack.core.multi_tenant.enabled` is switched on. See [Site resolution](../usage/site-resolution.md).

### `ArtisanPackUI\Core\Contracts\SiteResolver` (interface)

```php
public function currentSiteId(): int|string|null;
```

The ecosystem's single answer to "which site is this code running for?". Keyed on the site identifier rather than any package's `Site` model, and takes no `Request`, so it works unchanged inside console commands and queue workers. Returns `null` when no site resolves, which consumers read as "do not scope".

### `ArtisanPackUI\Core\MultiTenancy\SiteContext`

```php
public function __construct( SiteResolver $resolver, bool $enabled = false );
public function currentSiteId(): int|string|null;
public function hasSite(): bool;
public function enabled(): bool;
public function resolver(): SiteResolver;
public function setSiteId( int|string|null $siteId ): static;
public function forget(): static;
public function forSite( int|string|null $siteId, Closure $callback ): mixed;
public function withoutSite( Closure $callback ): mixed;
public function flush(): static;
```

The surface consumers actually talk to. Delegates to the bound resolver, applies the `enabled` flag, and adds pinning over a bounded stretch of work — `forSite()` and `withoutSite()` restore the previous context afterwards, including when the callback throws, and nest safely. A pinned site wins over the resolver and applies even while automatic resolution is switched off.

### Shipped resolvers

| Class | Behaviour |
|---|---|
| `ArtisanPackUI\Core\MultiTenancy\HookSiteResolver` | The default. Applies the `ap.cmsFramework.currentSite.resolve` filter, resolving to `null` where nothing answers, and raising `SiteResolutionException` where `artisanpack-ui/hooks` is not installed at all. |
| `ArtisanPackUI\Core\MultiTenancy\NullSiteResolver` | Never puts a site in context. |
| `ArtisanPackUI\Core\MultiTenancy\ChainSiteResolver` | Asks each configured resolver in turn and takes the first non-`null` answer. `fromConfig( array $resolverClasses, Container $container ): static` builds a chain from the configured class names, resolving each through the container; `resolvers(): array` returns the assembled chain. |

Nothing memoises: the resolver is asked on every call, because a worker looping over sites changes the answer within one process.

## Enums

| Enum | Cases |
|---|---|
| `ArtisanPackUI\Core\Enums\LogLevel` | `DEBUG`, `INFO`, `WARNING`, `ERROR`, `CRITICAL` |
| `ArtisanPackUI\Core\Enums\Package` | `CORE`, `ACCESSIBILITY`, `SECURITY`, `MEDIA_LIBRARY`, `LIVEWIRE_UI`, `HOOKS`, `ICONS`, `CMS` — each with `configKey(): string` and `shortName(): string` |
| `ArtisanPackUI\Core\Enums\Priority` | `LOW`, `MEDIUM`, `HIGH`, `CRITICAL` |
| `ArtisanPackUI\Core\Enums\Status` | `PENDING`, `ACTIVE`, `INACTIVE`, `DELETED` |

## Exceptions

All inherit from `ArtisanPackUI\Core\Exceptions\ArtisanPackException` (which extends `\Exception`):

| Exception | Thrown when |
|---|---|
| `ArtisanPackException` | Base class — catch this for any ArtisanPack UI error |
| `ConfigurationException` | Missing, invalid, or conflicting configuration |
| `DependencyException` | Required package dependency is missing or incompatible |
| `InstallationException` | Package failed to install or publish required assets |
| `ServiceException` | Unrecoverable runtime error in a package service |
| `SiteResolutionException` | A configured site resolver is missing, does not implement `SiteResolver`, the `resolvers` value is not a list, a hook listener answered with an unusable value, or `HookSiteResolver` ran without `artisanpack-ui/hooks` installed |
| `ValidationException` | Data failed a package's validation rules |

## Artisan commands

| Signature | Class |
|---|---|
| `artisanpack:scaffold-config` | `ArtisanPackUI\Core\Commands\ScaffoldConfigCommand` |
| `artisanpack:install-packages` | `ArtisanPackUI\Core\Commands\InstallPackagesCommand` |
| `artisanpack:diagnose` | `ArtisanPackUI\Core\Commands\DiagnoseCommand` |
| `artisanpack:check-compatibility` | `ArtisanPackUI\Core\Commands\CheckCompatibilityCommand` |
| `artisanpack:check-updates` | `ArtisanPackUI\Core\Commands\CheckUpdatesCommand` |
| `artisanpack:make-package` | `ArtisanPackUI\Core\Commands\MakePackageCommand` |

See [Artisan commands](../usage/artisan-commands.md) for option listings and examples.

## Testing utilities

| Class / trait | Purpose |
|---|---|
| `ArtisanPackUI\Core\Testing\ArtisanPackTestCase` | Abstract Orchestra Testbench base class that registers `CoreServiceProvider` and pulls in the three concerns below. |
| `ArtisanPackUI\Core\Testing\Concerns\ArtisanPackAssertions` | `assertPackageInstalled`, `assertConfigEquals`, `assertCommandExists`, `assertFacadeWorks`, `assertHelperExists`. |
| `ArtisanPackUI\Core\Testing\Concerns\InteractsWithConfiguration` | `withConfig( array )`, `withPackageConfig( string, array )`. |
| `ArtisanPackUI\Core\Testing\Concerns\MocksArtisanPackServices` | `swapArtisanPackService`, `mockArtisanPackService`, `swapConfigurationManager`, `swapLoggerFactory`, `swapBladeDirectiveRegistrar`. |

See [Testing utilities](../usage/testing-utilities.md) for examples.

## Container bindings

The following container bindings are registered by `CoreServiceProvider` as singletons:

| Abstract | Concrete |
|---|---|
| `'core'` | `\ArtisanPackUI\Core\Core` |
| `\ArtisanPackUI\Core\Config\ConfigurationManager` | self |
| `\ArtisanPackUI\Core\Logging\LoggerFactory` | self |
| `\ArtisanPackUI\Core\View\BladeDirectiveRegistrar` | self |
| `\ArtisanPackUI\Core\Diagnostics\DiagnosticRunner` | self |
| `\ArtisanPackUI\Core\Compatibility\CompatibilityChecker` | self |
| `\ArtisanPackUI\Core\Updates\PackagistClient` | self |
| `\ArtisanPackUI\Core\Updates\UpdateChecker` | self |

Site resolution is bound with `scoped()` rather than `singleton()`. Laravel forgets scoped instances between Octane requests and between queue jobs, which is what stops a pinned site outliving the job that pinned it, and what stops a request-dependent resolver freezing its first request's dependencies for the life of a worker:

| Abstract | Concrete |
|---|---|
| `\ArtisanPackUI\Core\Contracts\SiteResolver` | `ChainSiteResolver` assembled from `artisanpack.core.multi_tenant.resolvers` |
| `\ArtisanPackUI\Core\MultiTenancy\SiteContext` | self |

The `'a11y'` alias is owned by the `artisanpack-ui/accessibility` package and bound to `\ArtisanPack\Accessibility\Accessibility` when that package is installed.
