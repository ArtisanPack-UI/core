---
title: Usage
---

# Usage

ArtisanPack UI Core exposes its functionality through five layers — a shared base service provider, a configuration manager, a logging stack, a Blade directive helper, and the diagnostic / compatibility / update inspectors — plus a set of Artisan commands and a testing-utility toolkit that consumers can lean on.

## Topics

- [Service provider](usage/service-provider.md) — `ArtisanPackServiceProvider`, the shared base class for every package
- [Configuration manager](usage/configuration-manager.md) — `ConfigurationManager`, `ConfigurationSchema`, `ValidationResult`, helpers, facade
- [Logging](usage/logging.md) — `LoggerFactory`, `Logger`, `ArtisanPackLogger`, audit entries, the `AuditLogCreated` event, `ArtisanPackLog` facade
- [Blade directives](usage/blade-directives.md) — `BladeDirectiveRegistrar`, intent-revealing directive registration patterns
- [Site resolution](usage/site-resolution.md) — `SiteResolver`, `SiteContext`, the shipped resolvers, the `artisanpack.core.multi_tenant` block
- [Diagnostics](usage/diagnostics.md) — `DiagnosticRunner`, the five default checks, custom checks, `php artisan artisanpack:diagnose`
- [Compatibility analysis](usage/compatibility.md) — `CompatibilityChecker`, `php artisan artisanpack:check-compatibility`
- [Updates](usage/updates.md) — `UpdateChecker`, `PackagistClient`, `php artisan artisanpack:check-updates`
- [Artisan commands](usage/artisan-commands.md) — full command reference (artisanpack:*)
- [Constants, enums, exceptions](usage/constants-enums-exceptions.md) — shared primitives every package consumes
- [Testing utilities](usage/testing-utilities.md) — `ArtisanPackTestCase`, the assertions trait, config + mock concerns

## Quick reference

```php
// Read configuration
$theme = artisanpack_config( 'livewire-ui-components.theme.primary', '#3b82f6' );

// Log + audit
use ArtisanPackUI\Core\Facades\ArtisanPackLog;
$log = ArtisanPackLog::make( 'media-library' );
$log->info( 'Image uploaded.', [ 'id' => 42 ] );
$log->audit( 'media.uploaded', [ 'id' => 42 ] );

// Register Blade directives
$blade = artisanpack_blade();
$blade->echo( 'cms_title', 'config( "cms-framework.site.title" )' );
$blade->escaped( 'cms_description', 'config( "cms-framework.site.description" )' );
$blade->conditional( 'cms_role', '$user->hasRole( %s )', 'admin' );

// Build a service provider
class MediaLibraryServiceProvider extends \ArtisanPackUI\Core\ArtisanPackServiceProvider
{
    protected ?string $configFile       = __DIR__ . '/../config/media-library.php';
    protected ?string $configKey        = 'artisanpack.media-library';
    protected ?string $configPublishTag = 'media-library-config';

    protected array $commands = [ \ArtisanPackUI\MediaLibrary\Commands\ProcessThumbnails::class ];

    protected array $singletons = [
        'media-library' => \ArtisanPackUI\MediaLibrary\MediaLibrary::class,
    ];

    protected function registerPackageBindings(): void
    {
        // Package-specific bindings unique to media-library.
    }
}
```

```bash
# Artisan
php artisan artisanpack:scaffold-config
php artisan artisanpack:install-packages
php artisan artisanpack:diagnose
php artisan artisanpack:check-compatibility
php artisan artisanpack:check-updates
php artisan artisanpack:make-package artisanpack-ui/new-package
```
