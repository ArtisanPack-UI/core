---
title: Blade directives
---

# Blade directives

`BladeDirectiveRegistrar` replaces the hand-rolled `Blade::directive()` PHP-string templates most ArtisanPack UI packages used to write. Each method on the registrar declares a common directive pattern in a single line, validates the directive name at registration ( so typos surface immediately rather than at template render ), and emits the matching compiled PHP.

Resolve it through the container, via the helper, or by injecting it into your service provider's `bootPackage()`:

```php
use ArtisanPackUI\Core\View\BladeDirectiveRegistrar;

$blade = app( BladeDirectiveRegistrar::class );

// Or the helper
$blade = artisanpack_blade();
```

## `echo()` — surface a helper as a directive

Given the function `cmsTitle()` and the directive name `cms_title`, `@cms_title` compiles to `<?php echo cmsTitle(); ?>`:

```php
$blade->echo( 'cms_title', 'cmsTitle' );
```

```blade
{{-- Template --}}
<title>@cms_title — My Site</title>

{{-- With arguments --}}
@cms_excerpt( $post, 30 )
```

## `escaped()` — surface a helper as an escaped directive

Wraps the helper's return value with Laravel's `e()`, so any potentially-untrusted output is HTML-escaped:

```php
$blade->escaped( 'cms_description', 'cmsDescription' );
```

```blade
<meta name="description" content="@cms_description( $post )"/>
```

Use this whenever the helper might return user-supplied content.

## `conditional()` — paired conditional directives

Generates `@name(...)` / `@endname` directives backed by a callable check. Useful for role / capability / feature-flag style conditionals:

```php
$blade->conditional( 'hasRole', 'auth()->user()?->hasRole' );
```

```blade
@hasRole( 'admin' )
    <a href="/admin">Dashboard</a>
@endhasRole
```

Compiles to `<?php if ( auth()->user()?->hasRole( 'admin' ) ): ?> ... <?php endif; ?>`.

## `wrap()` — paired wrapping directives

Generates `@name(...)` / `@endname` directives that wrap their contents in arbitrary markup. The opening tag may contain the literal `{expression}` placeholder, which is replaced with the echoed value of the supplied expression at render time:

```php
$blade->wrap( 'sectionCard', '<section class="card" id="{expression}">', '</section>' );
```

```blade
@sectionCard( $slug )
    <h2>{{ $title }}</h2>
    <p>{{ $body }}</p>
@endsectionCard
```

Compiles to `<section class="card" id="<?php echo $slug; ?>"> ... </section>`.

## `livewireAction()` — fire a Livewire action

Generates a directive that calls `$this->action( $expression )` on the current Livewire component. Useful for shorthand directives that fire actions from inside a component's view:

```php
$blade->livewireAction( 'refreshGallery', 'refreshGallery' );
```

```blade
{{-- Inside a Livewire component view --}}
@refreshGallery
```

Compiles to `<?php $this->refreshGallery(); ?>`.

## `facade()` — surface a static facade method

Generates a directive that echoes the result of `FacadeClass::method( $expression )`. Useful for exposing facade-backed helpers without making templates import the facade:

```php
$blade->facade( 'mediaUrl', \App\Facades\Media::class, 'urlFor' );
```

```blade
<img src="@mediaUrl( $post->image_id )" alt="{{ $post->title }}"/>
```

Compiles to `<?php echo App\Facades\Media::urlFor( $post->image_id ); ?>`.

## Name validation

Directive names must start with a letter or underscore and contain only word characters ( `[A-Za-z_][A-Za-z0-9_]*` ). Names that violate this rule throw `InvalidArgumentException` at registration time, so typos surface immediately rather than at template render.

## When to use this vs `Blade::directive()`

Stick with the registrar for the patterns above — `echo`, `escaped`, `conditional`, `wrap`, `livewireAction`, `facade` cover the vast majority of directives an ArtisanPack UI package needs. Fall back to `Blade::directive()` directly only for genuinely custom compiled output that doesn't fit any of those patterns.
