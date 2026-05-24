# Upgrade Guide

## Upgrading from 1.1 to 1.2

Version 1.2.0 adds support for Laravel 13 while continuing to support Laravel 11 and 12. **This change is additive — no action is required for existing users.**

### Requirements Changes

#### Laravel Version

The `illuminate/support` constraint was widened from `^11.0|^12.0` to `^11.0|^12.0|^13.0`.

| Package Version | PHP  | Laravel Versions Supported |
|-----------------|------|----------------------------|
| 1.1.x           | 8.2+ | 11.x, 12.x                 |
| 1.2.x           | 8.2+ | 11.x, 12.x, 13.x           |

#### PHP Version

PHP 8.2+ remains the minimum requirement. Note that Laravel 13 itself requires PHP 8.3+, so:

- PHP 8.2 users continue to receive Laravel 11/12.
- Laravel 13 is only selectable on PHP 8.3+, as enforced by Laravel's own PHP constraint.

No need to raise your PHP floor or change any code.

### Upgrade Checklist

- [ ] Run `composer update artisanpack-ui/core` to get version 1.2
- [ ] (Optional) Upgrade to Laravel 13 if you are on PHP 8.3+
- [ ] Test your application to ensure compatibility

## Upgrading from 1.0 to 1.1

This guide covers the breaking changes introduced in version 1.1.0 and provides migration instructions.

### Requirements Changes

#### Laravel Version

Version 1.1.0 drops support for Laravel 10.x. You must be running Laravel 11.0 or higher to use this version.

| Package Version | Laravel Versions Supported |
|-----------------|---------------------------|
| 1.0.x           | 10.x, 11.x, 12.x          |
| 1.1.x           | 11.x, 12.x                |

If you are still using Laravel 10.x, you must either:
1. Upgrade your Laravel application to version 11.0 or higher, or
2. Continue using artisanpack-ui/core version 1.0.x

#### PHP Version

PHP 8.2+ is still the minimum requirement. PHP 8.4 is fully supported.

### Accessibility Helper Functions Removed

The following accessibility helper functions have been removed from the core package and moved to the dedicated `artisanpack-ui/accessibility` package:

- `a11y()`
- `a11yCSSVarBlackOrWhite()`
- `a11yGetContrastColor()`
- `a11yCheckContrastColor()`
- `generateAccessibleTextColor()`

#### Migration Steps

1. Install the accessibility package:

   ```bash
   composer require artisanpack-ui/accessibility
   ```

2. No code changes are required if you install the accessibility package - the function signatures remain identical.

#### Before (1.0.x - functions provided by core)

```php
// These functions were available via artisanpack-ui/core
$textColor = a11yGetContrastColor('#3b82f6');
$hasContrast = a11yCheckContrastColor('#3b82f6', '#ffffff');
```

#### After (1.1.x - functions require accessibility package)

```php
// Same functions, now provided by artisanpack-ui/accessibility
// Install: composer require artisanpack-ui/accessibility

$textColor = a11yGetContrastColor('#3b82f6');
$hasContrast = a11yCheckContrastColor('#3b82f6', '#ffffff');
```

### Dependency Changes

#### laravel/prompts

The `laravel/prompts` dependency has been constrained to `^0.3` (previously `^0.1|^0.2|^0.3`). This change was made because v0.3.0 introduced breaking changes to default-value handling in prompt functions.

If your project has a direct dependency on an older version of `laravel/prompts`, you may need to update it:

```bash
composer update laravel/prompts
```

### Upgrade Checklist

- [ ] Verify your Laravel version is 11.0 or higher
- [ ] Install `artisanpack-ui/accessibility` if you use any a11y helper functions
- [ ] Run `composer update artisanpack-ui/core` to get version 1.1
- [ ] Test your application to ensure compatibility
