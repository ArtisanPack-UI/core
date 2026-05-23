# ArtisanPack UI Core Changelog

## [1.2.0] - 2026-05-21

### Added

* **Blade directive registrar**: Added `ArtisanPackUI\Core\View\BladeDirectiveRegistrar` and the matching `artisanpack_blade()` helper. Packages can now declare common directive patterns through intent-revealing methods (`echo`, `escaped`, `conditional`, `wrap`, `livewireAction`, `facade`) instead of hand-rolling PHP-string templates with `Blade::directive()`. Directive names are validated at registration so typos surface immediately rather than at template render. Resolved as a singleton through `CoreServiceProvider`.
* **Shared logging infrastructure**: Added `ArtisanPackLogger`, `Logger`, and `LoggerFactory` under `ArtisanPackUI\Core\Logging`, plus the `AuditLogCreated` event and the `ArtisanPackLog` facade. Every ArtisanPack UI package can now route logs through a single channel with consistent `[package]` prefixes, structured context, and an `audit()` method that records compliance-relevant actions and dispatches an event applications can listen for. Configurable via `artisanpack.core.logging.channel` ( falls back to Laravel's default channel ) and `artisanpack.core.logging.audit.enabled`.
* **Laravel 13 support**: Widened the `illuminate/support` constraint from `^11.0|^12.0` to `^11.0|^12.0|^13.0`. Laravel 11 and 12 remain fully supported. This change is additive and backwards compatible — nothing is removed.

### Notes

* PHP 8.2+ remains the minimum requirement. Laravel 13 requires PHP 8.3+, so PHP 8.2 users continue to receive Laravel 11/12, while Laravel 13 is only selectable on PHP 8.3+ (enforced by Laravel's own PHP constraint).

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


