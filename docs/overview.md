---
title: ArtisanPack UI Core - Overview
---

# ArtisanPack UI Core - Overview

## Introduction

The ArtisanPack UI Core package is the foundational component of the ArtisanPack UI ecosystem. It serves as the central hub for managing and unifying configurations across all ArtisanPack UI packages in your Laravel application.

## Purpose

The primary goal of ArtisanPack UI Core is to eliminate configuration sprawl by providing a unified configuration management system. Instead of having separate configuration files scattered across multiple packages, all ArtisanPack UI package configurations are consolidated into a single `config/artisanpack.php` file.

## Key Benefits

### Centralized Configuration Management
- All ArtisanPack UI package settings in one place
- Easier to manage and maintain configurations
- Reduced configuration file clutter

### Automatic Discovery and Merging
- Automatically detects installed ArtisanPack UI packages
- Merges package configurations intelligently
- Preserves existing customizations

### Laravel Integration
- Seamless integration with Laravel's configuration system
- Uses standard Laravel configuration access patterns
- Compatible with Laravel's caching and optimization features

## Architecture Overview

The ArtisanPack UI Core package consists of several key components:

1. **CoreServiceProvider**: Registers services and publishes configuration files
2. **ScaffoldConfigCommand**: Artisan command for automatic configuration scaffolding
3. **Core Facade**: Provides easy access to core functionality
4. **Unified Configuration File**: Central `artisanpack.php` configuration file

## Package Ecosystem

ArtisanPack UI Core is designed to work with other ArtisanPack UI packages such as:

- **artisanpack-ui/cms-framework**: Content management system framework
- **artisanpack-ui/visual-editor**: Visual content editing tools
- **artisanpack-ui/ui-components**: Reusable UI components
- And many more...

Each of these packages can have their configurations automatically merged into the central configuration file, providing a unified configuration experience.

## Getting Started

To get started with ArtisanPack UI Core:

1. Install the package via Composer
2. Publish the base configuration file
3. Install additional ArtisanPack UI packages
4. Use the scaffold command to automatically merge configurations
5. Customize settings in the unified configuration file

For detailed installation and setup instructions, see the [Installation Guide](installation.md).

## Next Steps

- [Installation Guide](installation.md) - Learn how to install and set up the package
- [Configuration Guide](configuration.md) - Understand how to configure and customize settings
- [Usage Examples](usage-examples.md) - See practical examples of using the package