---
title: ArtisanPack UI Core - Installation Guide
---

# ArtisanPack UI Core - Installation Guide

## Requirements

Before installing ArtisanPack UI Core, ensure your environment meets the following requirements:

- **PHP**: ^8.2
- **Laravel**: ^5.3 (illuminate/support package)
- **Composer**: Latest version recommended

## Installation Steps

### Step 1: Install via Composer

Install the ArtisanPack UI Core package using Composer:

```bash
composer require artisanpack-ui/core
```

### Step 2: Service Provider Registration

If you're using Laravel 5.5 or higher, the package will automatically register its service provider through auto-discovery. For older versions of Laravel, manually add the service provider to your `config/app.php`:

```php
'providers' => [
    // Other service providers...
    ArtisanPackUI\Core\CoreServiceProvider::class,
],
```

### Step 3: Publish Configuration File

Publish the base configuration file to your Laravel application:

```bash
php artisan vendor:publish --tag=artisanpack-config
```

This command creates a `config/artisanpack.php` file in your Laravel application where all ArtisanPack UI package configurations will be centralized.

### Step 4: Verify Installation

You can verify that the installation was successful by checking if the configuration file exists:

```bash
ls -la config/artisanpack.php
```

You should also be able to see the available Artisan commands:

```bash
php artisan list | grep artisanpack
```

This should show the `artisanpack:scaffold-config` command.

## Installing Additional ArtisanPack UI Packages

To get the full benefit of the unified configuration system, install additional ArtisanPack UI packages:

### Example Packages

```bash
# Install CMS Framework package
composer require artisanpack-ui/cms-framework

# Install Visual Editor package  
composer require artisanpack-ui/visual-editor

# Install UI Components package
composer require artisanpack-ui/ui-components
```

### Automatic Configuration Scaffolding

After installing additional packages, use the scaffold command to automatically merge their configurations:

```bash
php artisan artisanpack:scaffold-config
```

This command will:
- Detect all installed ArtisanPack UI packages
- Merge their default configurations into `config/artisanpack.php`
- Preserve any existing customizations you've made

## Installation in Different Environments

### Development Environment

For development, you may want to install with dev dependencies:

```bash
composer require artisanpack-ui/core --dev
```

### Production Environment

For production installations, use the `--no-dev` flag to exclude development dependencies:

```bash
composer install --no-dev --optimize-autoloader
```

### Docker Environment

If you're using Docker, add the installation commands to your Dockerfile or docker-compose setup:

```dockerfile
RUN composer require artisanpack-ui/core
RUN php artisan vendor:publish --tag=artisanpack-config
```

## Troubleshooting

### Common Issues

**Issue**: Service provider not found
**Solution**: Clear your configuration cache:
```bash
php artisan config:clear
```

**Issue**: Configuration file not published
**Solution**: Ensure you have write permissions to the config directory and try publishing again:
```bash
chmod 755 config/
php artisan vendor:publish --tag=artisanpack-config --force
```

**Issue**: Command not found
**Solution**: Clear your application cache:
```bash
php artisan cache:clear
php artisan config:clear
```

### Getting Help

If you encounter issues during installation:

1. Check the [GitHub Issues](https://github.com/artisanpack-ui/core/issues) page
2. Review the Laravel logs: `storage/logs/laravel.log`
3. Ensure all requirements are met
4. Try clearing all caches: `php artisan optimize:clear`

## Next Steps

After successful installation:

1. [Configuration Guide](configuration.md) - Learn how to configure the package
2. [Usage Examples](usage-examples.md) - See practical usage examples
3. Install additional ArtisanPack UI packages to see the unified configuration system in action

## Uninstallation

To remove ArtisanPack UI Core:

```bash
# Remove the package
composer remove artisanpack-ui/core

# Optionally remove the configuration file
rm config/artisanpack.php
```

**Note**: Be careful when removing the configuration file as it may contain settings for other ArtisanPack UI packages.