# ArtisanPack UI Core Changelog

## [1.2.0] - 2026-05-23

### Added

* **`ArtisanPackServiceProvider` base class**: Abstract base service provider that removes the boilerplate every sibling ArtisanPack UI package previously hand-rolled (config merging, command registration, view loading, route loading, container singletons). Child providers configure their behaviour declaratively through `$configFile`, `$configKey`, `$configPublishTag`, `$commands`, `$singletons`, `$viewsPath`, `$viewsNamespace`, and `$routesPath` properties and only need to implement the `registerPackageBindings()` hook for unique bindings. An optional `bootPackage()` hook covers any boot-time logic that doesn't map to the standard hooks. Reduces a typical sibling provider to a handful of properties.
* **Unified configuration manager**: Added `ArtisanPackUI\Core\Config\ConfigurationManager` for reading, writing, merging, and validating the configuration of every installed ArtisanPack UI package. Hides the shared `artisanpack.<package>.<key>` namespacing behind package-aware helpers (`get`, `set`, `has`, `merge`, `validate`, `all`, `package`, `registerSchema`, `schema`). Shipped alongside `ConfigurationSchema` (lightweight `type` / `required` / `in` rule validation), `ValidationResult` (immutable outcome object), the `ArtisanPackConfig` facade, and the `artisanpack_config()` helper for one-off reads. Resolved as a singleton through `CoreServiceProvider`.
* **Shared logging infrastructure**: Added `ArtisanPackLogger`, `Logger`, and `LoggerFactory` under `ArtisanPackUI\Core\Logging`, plus the `AuditLogCreated` event and the `ArtisanPackLog` facade. Every ArtisanPack UI package can now route logs through a single channel with consistent `[package]` prefixes, structured context, and an `audit()` method that records compliance-relevant actions and dispatches an event applications can listen for. Configurable via `artisanpack.core.logging.channel` (falls back to Laravel's default channel) and `artisanpack.core.logging.audit.enabled`.
* **Blade directive registrar**: Added `ArtisanPackUI\Core\View\BladeDirectiveRegistrar` and the matching `artisanpack_blade()` helper. Packages can now declare common directive patterns through intent-revealing methods (`echo`, `escaped`, `conditional`, `wrap`, `livewireAction`, `facade`) instead of hand-rolling PHP-string templates with `Blade::directive()`. Directive names are validated at registration so typos surface immediately rather than at template render. Resolved as a singleton through `CoreServiceProvider`.
* **`ArtisanPackFacade` base class**: Shared base for every ArtisanPack UI facade, providing consistent error messages when the underlying service is missing alongside helper methods for safely probing whether the service is available before resolving it. Subclasses override `getFacadeAccessor()` and may override `getPackageName()` so the missing-service exception names the package the consumer needs to install.
* **Constants + enums repository**: Added `ArtisanPackUI\Core\Constants` (package identifiers, configuration keys, default values, version baselines) and four enums under `ArtisanPackUI\Core\Enums` — `LogLevel`, `Package` (with `configKey()` and `shortName()` helpers), `Priority`, and `Status`. Single source of truth for primitives used across the ecosystem.
* **Base exception hierarchy**: Added `ArtisanPackUI\Core\Exceptions\ArtisanPackException` plus five specialised subclasses — `ConfigurationException`, `DependencyException`, `InstallationException`, `ServiceException`, `ValidationException`. Catch the base class for any ArtisanPack UI error, or the specific subclass for a specific failure mode.
* **Testing utilities base classes**: Added `ArtisanPackUI\Core\Testing\ArtisanPackTestCase` (Orchestra Testbench base that registers `CoreServiceProvider` automatically) plus three concerns — `ArtisanPackAssertions` (`assertPackageInstalled`, `assertConfigEquals`, `assertCommandExists`, `assertFacadeWorks`, `assertHelperExists`), `InteractsWithConfiguration` (`withConfig`, `withPackageConfig`), and `MocksArtisanPackServices` (`swapArtisanPackService`, `mockArtisanPackService`, plus dedicated shortcuts for the configuration manager, logger factory, and Blade directive registrar). Sibling packages can extend the base case and immediately reach for ecosystem-aware assertions and helpers.
* **`artisanpack:diagnose` command**: Runs the new `DiagnosticRunner` against five default checks — `EnvironmentCheck` (PHP / Laravel / Livewire versions + required PHP extensions), `InstalledPackagesCheck`, `ConfigurationCheck` (with auto-fix that invokes `artisanpack:scaffold-config`), `ServiceBindingsCheck` (verifies `core` and `a11y` aliases resolve to expected concretes), and `ArtisanCommandsCheck`. Supports `--json`, `--fix`, and `--package=<id>` flags. Exits non-zero when any check reports an error.
* **`artisanpack:check-compatibility` command**: Walks every installed ArtisanPack UI package's Composer requirements and reports inter-package, PHP version, Laravel version, and PHP extension constraint satisfaction via the new `CompatibilityChecker`. Supports `--json`, `--strict` (treat warnings as errors), and `--package=<name>` flags. Test overrides on the checker allow stubbing PHP version, Laravel version, loaded extensions, and the package list without touching the host environment.
* **`artisanpack:check-updates` command**: Reads `composer.lock`, asks Packagist for the latest stable release of every installed ArtisanPack UI package, and produces a structured update report via the new `UpdateChecker` and `PackagistClient`. Classifies each update as major / minor / patch, flags security-related releases by keyword scanning (security, vulnerability, cve-, xss, csrf, sql injection, remote code execution), and supports `--json`, `--minor-only`, `--security-only`, and `--changelog` flags. Packagist responses are cached for `artisanpack.core.updates.cache_ttl` seconds (default 3600), with cache entries refreshed on read so frequently-queried packages stay warm. Adopts Laravel 13's `Cache::touch()` behind a `method_exists()` feature gate so custom cache repositories on Laravel 11/12 benefit too.
* **`artisanpack:make-package` command**: Scaffolds a new ArtisanPack UI package from the standard blueprint — `composer.json` wired to the ecosystem's standard dev dependencies, a service provider that extends `ArtisanPackServiceProvider`, a facade, a config stub, Pest tests via Orchestra Testbench, and the conventional directory layout. Optional `--with-livewire`, `--with-views`, `--with-routes`, `--with-migrations`, and `--with-commands` flags add additional structure. Validates `vendor/package` naming format up front and protects existing non-empty directories ( opt-in via `--force` ).
* **Laravel 13 support**: Widened the `illuminate/support` constraint from `^11.0|^12.0` to `^11.0|^12.0|^13.0`. Laravel 11 and 12 remain fully supported. The CI matrix now runs against Laravel 11, 12, and 13.

### Changed

* **Hardened CI**: CI now runs on `release/**` branches so v1.2 PRs are validated, workflow permissions tightened, and the release workflow now fails on Packagist errors rather than swallowing them silently.

### Notes

* PHP 8.2+ remains the minimum requirement. Laravel 13 requires PHP 8.3+, so PHP 8.2 users continue to receive Laravel 11/12, while Laravel 13 is only selectable on PHP 8.3+ (enforced by Laravel's own PHP constraint).
* No public API was removed. Every Core 1.1 application continues to work after upgrading to 1.2 with no other changes required. Migration to the new services ( `ArtisanPackServiceProvider`, `ConfigurationManager`, `ArtisanPackLog`, `ArtisanPackTestCase` ) is opt-in. See `UPGRADE.md` for the full migration guide.

## [1.1.0] - 2025-01-09

### Breaking Changes

* **Dropped Laravel 10.x support**: The package now requires Laravel 11.0 or higher. Laravel 10.x is no longer supported due to PHP version compatibility requirements.
* **Removed accessibility helper functions**: The following functions have been moved to the `artisanpack-ui/accessibility` package:
  * `a11y()`
  * `a11yCSSVarBlackOrWhite()`
  * `a11yGetContrastColor()`
  * `a11yCheckContrastColor()`
  * `generateAccessibleTextColor()`

  To continue using these functions, install the accessibility package:
  ```bash
  composer require artisanpack-ui/accessibility
  ```

### Changed

* Updated `illuminate/support` constraint from `^10.0|^11.0|^12.0` to `^11.0|^12.0`
* Updated `laravel/prompts` constraint from `^0.1|^0.2|^0.3` to `^0.3` for stability (v0.3.0 introduced breaking changes to default-value handling)

### Notes

* PHP 8.2+ is still required
* The GitLab CI pipeline uses PHP 8.4, which is fully compatible with Laravel 11 and 12

## [1.0] - 2025-10-02

* Initial release of ArtisanPack UI Core package
* Added CoreServiceProvider for Laravel integration with singleton service registration
* Added artisanpack:scaffold-config command for unified configuration management
* Added Core facade for easy access to package functionality
* Added unified configuration system with artisanpack.php config file
* Added accessibility helper functions for color contrast and toast duration
* Added support for publishing and merging package configurations
* Added automatic config file scaffolding with --force option support
* Requires PHP 8.2+ and Laravel 10.0+


