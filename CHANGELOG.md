# ArtisanPack UI Core Changelog

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


