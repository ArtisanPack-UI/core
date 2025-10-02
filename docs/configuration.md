---
title: ArtisanPack UI Core - Configuration Guide
---

# ArtisanPack UI Core - Configuration Guide

## Overview

The ArtisanPack UI Core package provides a unified configuration system that consolidates all ArtisanPack UI package settings into a single `config/artisanpack.php` file. This guide explains how to configure and customize your settings effectively.

## Configuration File Structure

The unified configuration file follows a simple structure where each ArtisanPack UI package has its own section:

```php
<?php

return [
    'package-name' => [
        'setting1' => 'value1',
        'setting2' => 'value2',
        // ... more settings
    ],
    
    'another-package' => [
        'setting' => 'value',
        // ... more settings
    ],
];
```

## Publishing Configuration

### Initial Setup

First, publish the base configuration file:

```bash
php artisan vendor:publish --tag=artisanpack-config
```

This creates `config/artisanpack.php` with an empty structure ready for your customizations.

### Automatic Scaffolding

To automatically populate the configuration file with default settings from all installed ArtisanPack UI packages:

```bash
php artisan artisanpack:scaffold-config
```

#### Command Options

- `--force`: Overwrite existing configuration keys (preserves existing keys by default)

```bash
# Preserve existing customizations (default behavior)
php artisan artisanpack:scaffold-config

# Force overwrite all keys with package defaults
php artisan artisanpack:scaffold-config --force
```

## Manual Configuration

You can manually configure any package settings by editing `config/artisanpack.php`:

### Example Configuration

```php
<?php

return [
    // CMS Framework Configuration
    'cms-framework' => [
        'settings' => [
            'site_title' => 'My Awesome Website',
            'site_description' => 'A powerful website built with ArtisanPack UI',
            'theme' => 'modern',
            'enable_caching' => true,
        ],
        'database' => [
            'table_prefix' => 'ap_',
            'connection' => 'mysql',
        ],
    ],

    // Visual Editor Configuration
    'visual-editor' => [
        'autosave_interval' => 300, // seconds
        'toolbar_style' => 'minimal',
        'enable_markdown' => true,
        'upload_path' => 'uploads/editor',
        'allowed_file_types' => ['jpg', 'png', 'gif', 'pdf'],
    ],

    // UI Components Configuration
    'ui-components' => [
        'theme' => [
            'primary_color' => '#3b82f6',
            'secondary_color' => '#64748b',
            'dark_mode' => false,
        ],
        'components' => [
            'enable_animations' => true,
            'loading_spinner' => 'dots',
            'notification_position' => 'top-right',
        ],
    ],
];
```

## Configuration Access

### Using Laravel's Config Helper

Access your configurations using Laravel's standard `config()` helper:

```php
// Get an entire package configuration
$cmsConfig = config('artisanpack.cms-framework');

// Get a specific setting with dot notation
$siteTitle = config('artisanpack.cms-framework.settings.site_title');

// Get a setting with a default value
$theme = config('artisanpack.cms-framework.settings.theme', 'default');

// Get all ArtisanPack configurations
$allConfigs = config('artisanpack');
```

### In Service Providers

```php
public function boot()
{
    $siteTitle = config('artisanpack.cms-framework.settings.site_title', 'Default Site');
    
    // Use the configuration value
    view()->share('siteTitle', $siteTitle);
}
```

### In Controllers

```php
class HomeController extends Controller
{
    public function index()
    {
        $editorConfig = config('artisanpack.visual-editor');
        
        return view('home', compact('editorConfig'));
    }
}
```

## Environment-Specific Configuration

### Using Environment Variables

You can use environment variables in your configuration:

```php
return [
    'cms-framework' => [
        'settings' => [
            'site_title' => env('SITE_TITLE', 'Default Site Title'),
            'enable_caching' => env('CMS_ENABLE_CACHING', true),
        ],
    ],
];
```

Add corresponding entries to your `.env` file:

```env
SITE_TITLE="My Production Site"
CMS_ENABLE_CACHING=true
```

### Different Environments

Create environment-specific configurations:

#### Development (.env.local)
```env
SITE_TITLE="Dev Site"
CMS_ENABLE_CACHING=false
```

#### Production (.env.production)
```env
SITE_TITLE="Production Site"
CMS_ENABLE_CACHING=true
```

## Configuration Validation

### Basic Validation

You can validate configuration values in your service providers:

```php
public function boot()
{
    $config = config('artisanpack.cms-framework.settings');
    
    if (empty($config['site_title'])) {
        throw new InvalidArgumentException('Site title is required');
    }
}
```

### Using Laravel Validation

```php
use Illuminate\Support\Facades\Validator;

public function boot()
{
    $config = config('artisanpack.visual-editor');
    
    $validator = Validator::make($config, [
        'autosave_interval' => 'required|integer|min:60',
        'upload_path' => 'required|string',
        'allowed_file_types' => 'required|array',
    ]);
    
    if ($validator->fails()) {
        throw new InvalidArgumentException('Invalid visual editor configuration');
    }
}
```

## Configuration Caching

### Development

In development, configuration changes are reflected immediately. No caching is typically used.

### Production

For production, cache your configurations for better performance:

```bash
# Cache configurations
php artisan config:cache

# Clear configuration cache when needed
php artisan config:clear
```

## Best Practices

### 1. Use Environment Variables for Sensitive Data

```php
// Good
'database' => [
    'password' => env('DB_PASSWORD'),
],

// Bad
'database' => [
    'password' => 'hardcoded-password',
],
```

### 2. Provide Sensible Defaults

```php
'settings' => [
    'items_per_page' => env('CMS_ITEMS_PER_PAGE', 20),
    'enable_search' => env('CMS_ENABLE_SEARCH', true),
],
```

### 3. Group Related Settings

```php
'cms-framework' => [
    'ui' => [
        'theme' => 'modern',
        'sidebar_position' => 'left',
    ],
    'performance' => [
        'enable_caching' => true,
        'cache_duration' => 3600,
    ],
],
```

### 4. Document Your Configuration

```php
return [
    'visual-editor' => [
        // Autosave interval in seconds (minimum: 60)
        'autosave_interval' => 300,
        
        // Allowed file types for uploads
        'allowed_file_types' => ['jpg', 'png', 'gif'],
    ],
];
```

## Troubleshooting

### Configuration Not Loading

1. Clear configuration cache: `php artisan config:clear`
2. Check file syntax: `php -l config/artisanpack.php`
3. Verify file permissions: `ls -la config/artisanpack.php`

### Settings Not Taking Effect

1. Clear all caches: `php artisan optimize:clear`
2. Check for typos in configuration keys
3. Verify environment variables are loaded correctly

### Scaffold Command Issues

1. Ensure packages are properly installed
2. Check if packages have tagged their configuration files
3. Use `--force` flag to overwrite existing settings

## Next Steps

- [Usage Examples](usage-examples.md) - See practical examples of using configurations
- [Overview](overview.md) - Return to the package overview