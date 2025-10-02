# ArtisanPack UI Core

The ArtisanPack UI Core package provides the foundational functionality for the ArtisanPack UI ecosystem. Its primary purpose is to **unify all configuration files** from multiple ArtisanPack UI packages into a single, centralized `artisanpack.php` configuration file. This approach eliminates configuration sprawl and provides a single source of truth for all your ArtisanPack UI package settings.

## Features

- **Unified Configuration Management**: Merge all ArtisanPack UI package configurations into a single `config/artisanpack.php` file
- **Automatic Configuration Scaffolding**: Use the `artisanpack:scaffold-config` command to automatically detect and merge configurations from all installed ArtisanPack UI packages
- **Preservation of Custom Settings**: Existing customizations are preserved when scaffolding configurations
- **Laravel Integration**: Seamless integration with Laravel's service container and configuration system
- **Force Override Option**: Ability to force-update existing configuration keys when needed

## Installation

You can install the ArtisanPack UI Core package by running the following composer command:

```bash
composer require artisanpack-ui/core
```

The package will automatically register its service provider in Laravel applications.

## Configuration

### Publishing the Configuration File

To publish the base configuration file to your Laravel application:

```bash
php artisan vendor:publish --tag=artisanpack-config
```

This creates a `config/artisanpack.php` file where all your ArtisanPack UI package configurations will be centralized.

### Automatic Configuration Scaffolding

The core package provides a powerful command to automatically scaffold your configuration file with settings from all installed ArtisanPack UI packages:

```bash
php artisan artisanpack:scaffold-config
```

**Options:**
- `--force`: Overwrite existing configuration keys (by default, existing keys are preserved)

### Manual Configuration

You can also manually configure individual packages in your `config/artisanpack.php` file:

```php
<?php

return [
    'cms-framework' => [
        'settings' => [
            'site_title' => 'My Awesome Site',
            'theme' => 'modern',
        ],
    ],
    
    'visual-editor' => [
        'autosave_interval' => 300,
        'toolbar_style' => 'minimal',
    ],
    
    'ui-components' => [
        'default_theme' => 'dark',
        'enable_animations' => true,
    ],
];
```

## Usage

### Service Provider Integration

The CoreServiceProvider automatically:
- Registers the Core singleton service
- Publishes the configuration file when running in console mode
- Registers the scaffold configuration command

### Using the Core Facade

```php
use ArtisanPackUI\Core\Facades\Core;

// Access core functionality through the facade
$core = Core::getInstance();
```

### Configuration Access

Access your unified configurations using Laravel's standard config helper:

```php
// Get a specific package's configuration
$cmsSettings = config('artisanpack.cms-framework');

// Get a specific setting with a default value
$siteTitle = config('artisanpack.cms-framework.settings.site_title', 'Default Title');

// Get all ArtisanPack configurations
$allConfigs = config('artisanpack');
```

## How It Works

1. **Installation**: When you install ArtisanPack UI packages, they tag their configuration files for discovery
2. **Detection**: The `artisanpack:scaffold-config` command automatically detects all tagged configuration files
3. **Merging**: Configurations are merged into the central `config/artisanpack.php` file, with each package getting its own section
4. **Preservation**: Your existing customizations are preserved unless you use the `--force` flag
5. **Access**: All configurations are accessible through Laravel's standard configuration system

## Example Workflow

```bash
# Install core package
composer require artisanpack-ui/core

# Install other ArtisanPack UI packages
composer require artisanpack-ui/cms-framework
composer require artisanpack-ui/visual-editor

# Publish the base configuration
php artisan vendor:publish --tag=artisanpack-config

# Automatically scaffold configurations from all packages
php artisan artisanpack:scaffold-config

# Your config/artisanpack.php now contains unified settings for all packages
```

## Requirements

- PHP ^8.2
- Laravel ^5.3 (illuminate/support)

## Contributing

As an open source project, this package is open to contributions from anyone. Please [read through the contributing guidelines](CONTRIBUTING.md) to learn more about how you can contribute to this project.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
