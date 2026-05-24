---
title: Service provider
---

# Service provider

`ArtisanPackServiceProvider` is the abstract base class every ArtisanPack UI package should extend instead of `Illuminate\Support\ServiceProvider`. It removes the boilerplate previously hand-rolled by each package — config merging, command registration, view loading, route loading, container singleton registration — and reduces a typical package's service provider to a handful of declarative properties.

## Why use it

A typical package's service provider previously looked like this:

```php
public function register(): void
{
    $this->mergeConfigFrom( __DIR__ . '/../config/media-library.php', 'artisanpack.media-library' );

    $this->app->singleton( 'media-library', fn () => new MediaLibrary );
    $this->app->singleton( ThumbnailGenerator::class );
}

public function boot(): void
{
    if ( $this->app->runningInConsole() ) {
        $this->publishes( [
            __DIR__ . '/../config/media-library.php' => config_path( 'artisanpack/media-library.php' ),
        ], 'media-library-config' );

        $this->commands( [ ProcessThumbnails::class, GenerateAvif::class ] );
    }

    $this->loadViewsFrom( __DIR__ . '/../resources/views', 'media-library' );
    $this->loadRoutesFrom( __DIR__ . '/../routes/web.php' );

    Blade::component( 'media-library::picker', MediaLibraryPicker::class );
}
```

With `ArtisanPackServiceProvider` it becomes:

```php
class MediaLibraryServiceProvider extends \ArtisanPackUI\Core\ArtisanPackServiceProvider
{
    protected ?string $configFile       = __DIR__ . '/../config/media-library.php';
    protected ?string $configKey        = 'artisanpack.media-library';
    protected ?string $configPublishTag = 'media-library-config';

    protected ?string $viewsPath      = __DIR__ . '/../resources/views';
    protected ?string $viewsNamespace = 'media-library';

    protected ?string $routesPath = __DIR__ . '/../routes/web.php';

    protected array $commands = [
        ProcessThumbnails::class,
        GenerateAvif::class,
    ];

    protected array $singletons = [
        'media-library' => MediaLibrary::class,
        ThumbnailGenerator::class, // self-bound when key is numeric
    ];

    protected function registerPackageBindings(): void
    {
        // Bindings unique to media-library that don't fit the $singletons array.
    }

    protected function bootPackage(): void
    {
        Blade::component( 'media-library::picker', MediaLibraryPicker::class );
    }
}
```

## Configurable properties

| Property | Type | Purpose |
|---|---|---|
| `$configFile` | `string\|null` | Absolute path to the package's config file. Merged in `register()` and published in `boot()`. |
| `$configKey` | `string\|null` | Configuration key the package config is merged under (e.g. `artisanpack.media-library`). |
| `$configPublishTag` | `string\|null` | Publish tag used by `vendor:publish`. Defaults to `<configKey>-config`. |
| `$commands` | `array<class-string>` | Artisan commands registered when the application runs in the console. |
| `$singletons` | `array<int\|string, callable\|class-string>` | Container singletons keyed by abstract. Numeric keys are treated as self-bound class-strings. Closures and class-strings both work as values. |
| `$viewsPath` | `string\|null` | Absolute path to the package's views directory. |
| `$viewsNamespace` | `string\|null` | View namespace used when loading the views. |
| `$routesPath` | `string\|null` | Absolute path to the package's routes file. |

Any property left as `null` ( or as an empty array, for `$commands` / `$singletons` ) is skipped at boot time, so you only opt into the parts your package needs.

## Required hook

Child providers must implement `registerPackageBindings()` for any bindings that do not fit the `$singletons` array — interface-to-implementation contracts, scoped bindings, conditional bindings, etc.

```php
protected function registerPackageBindings(): void
{
    $this->app->bind(
        \ArtisanPackUI\MediaLibrary\Contracts\ThumbnailGenerator::class,
        \ArtisanPackUI\MediaLibrary\Services\GdThumbnailGenerator::class,
    );

    $this->app->scoped( RequestScopedCache::class );
}
```

## Optional hook

Override `bootPackage()` for any boot-time logic that does not map to the standard hooks — Blade components, Livewire components, gates, policies, event listeners, custom middleware aliases, etc.

```php
protected function bootPackage(): void
{
    \Illuminate\Support\Facades\Blade::component( 'media-library::picker', MediaLibraryPicker::class );

    \Livewire\Livewire::component( 'media-library.gallery', \ArtisanPackUI\MediaLibrary\Livewire\Gallery::class );

    \Illuminate\Support\Facades\Gate::define(
        'delete-media',
        [ \ArtisanPackUI\MediaLibrary\Policies\MediaPolicy::class, 'delete' ],
    );
}
```

## Boot order

When Laravel boots the provider, `ArtisanPackServiceProvider` runs the following in order:

**`register()`:**
1. `registerSingletons()` — every entry in `$singletons` is bound as a container singleton.
2. `mergePackageConfig()` — `$configFile` is merged under `$configKey` when both are set.
3. `registerPackageBindings()` — the abstract method you implement.

**`boot()`:**
1. When the application runs in the console: `publishPackageAssets()` (publishes `$configFile` to the application's config directory) and `registerPackageCommands()` (registers `$commands`).
2. `loadPackageViews()` — when both `$viewsPath` and `$viewsNamespace` are set.
3. `loadPackageRoutes()` — when `$routesPath` is set.
4. `bootPackage()` — the optional hook you may override.

This order matches the conventions Laravel itself uses, and matches what `SecurityServiceProvider`, `MediaLibraryServiceProvider`, and the other sibling-package providers used to do by hand. Migrating to `ArtisanPackServiceProvider` does not change observed behaviour for downstream applications.
