---
title: ArtisanPack UI Core Documentation
---

# ArtisanPack UI Core

The **foundation** package every other ArtisanPack UI package depends on. Provides unified configuration, structured logging, shared site resolution, Blade directive helpers, a diagnostic runner, cross-package compatibility analysis, Packagist-backed update checking, a shared base service provider, and the package-management Artisan commands.

> **Core 1.3.1.** Adds `SiteContext::isPinned()`, which reports whether a site was put in context explicitly. `currentSiteId()` answers `null` both when nothing is pinned and when calling code pinned "no site" deliberately, and a consumer that applies a default site of its own needs to tell those apart. Additive; no other behaviour changed.

> **Core 1.3 — shared site resolution.** Adds the `SiteResolver` contract, the `SiteContext` service, the `HookSiteResolver` / `NullSiteResolver` / `ChainSiteResolver` implementations, the `ArtisanPackSite` facade, the `apCurrentSiteId()` helper, and the `artisanpack.core.multi_tenant` configuration block — the ecosystem's single answer to "which site is this code running for?". Additive and inert until tenancy is switched on. **Breaking:** Laravel 11 support is dropped ( `illuminate/support` narrows to `^12.0|^13.0` ) and `MIN_LARAVEL_VERSION` is corrected to `12.0.0`. See [UPGRADE.md](../UPGRADE.md).

> **Core 1.2 — package management.** Added the `ArtisanPackServiceProvider` base class, the `ConfigurationManager` + `ConfigurationSchema` validation pipeline, the `LoggerFactory` / `ArtisanPackLogger` logging stack, the `BladeDirectiveRegistrar` helper, the `DiagnosticRunner`, the `CompatibilityChecker`, the `UpdateChecker`, the shared `Constants` / Enums / Exceptions surface, the `ArtisanPackTestCase` testing utilities, and the `artisanpack:diagnose`, `artisanpack:check-compatibility`, `artisanpack:check-updates`, and `artisanpack:make-package` commands. See [CHANGELOG.md](../CHANGELOG.md) for the full release notes.

## What's in this package

- **Unified configuration** — `config/artisanpack.php`, the `ConfigurationManager` service, the `ConfigurationSchema` validator, the `ArtisanPackConfig` facade, and the `artisanpack_config()` helper
- **Shared base service provider** — `ArtisanPackServiceProvider` removes the boilerplate every sibling package previously hand-rolled (config merging, command registration, view loading, route loading, container singletons)
- **Logging stack** — `LoggerFactory`, `Logger`, the `ArtisanPackLogger` interface, the `ArtisanPackLog` facade, and the `AuditLogCreated` event for compliance-relevant actions
- **Shared site resolution** — the `SiteResolver` contract, the `SiteContext` service, the shipped `HookSiteResolver` / `NullSiteResolver` / `ChainSiteResolver`, the `ArtisanPackSite` facade, and the `apCurrentSiteId()` helper. One contract, one binding, one config block, so every package scopes to the same site
- **Blade directive registrar** — `BladeDirectiveRegistrar` + `artisanpack_blade()` helper. Replaces hand-rolled `Blade::directive()` template strings with intent-revealing methods (`echo`, `escaped`, `conditional`, `wrap`, `livewireAction`, `facade`)
- **Diagnostics** — `DiagnosticRunner` with five default checks (environment, installed packages, configuration, service bindings, Artisan commands), exposed via `php artisan artisanpack:diagnose`
- **Compatibility analysis** — `CompatibilityChecker` reports cross-package, PHP, Laravel, and PHP extension constraints, exposed via `php artisan artisanpack:check-compatibility`
- **Update checking** — `UpdateChecker` + `PackagistClient` report available updates, flag security releases, and classify changes as major / minor / patch, exposed via `php artisan artisanpack:check-updates`
- **Package management** — `php artisan artisanpack:install-packages`, `php artisan artisanpack:make-package`, `php artisan artisanpack:scaffold-config`
- **Shared primitives** — `Constants` (package identifiers, config keys, version baselines), `Enums` (`LogLevel`, `Package`, `Priority`, `Status`), `Exceptions` (`ArtisanPackException` + 5 specialised subclasses)
- **Testing utilities** — `ArtisanPackTestCase` plus the `ArtisanPackAssertions`, `InteractsWithConfiguration`, and `MocksArtisanPackServices` concerns

## What's NOT in this package (sibling packages)

| Capability | Package |
|---|---|
| WCAG color contrast utilities, accessible text colors | [`artisanpack-ui/accessibility`](https://github.com/ArtisanPack-UI/accessibility) |
| Sanitization, escaping, KSES, CSP, security headers, rate limiting | [`artisanpack-ui/security`](https://github.com/ArtisanPack-UI/security) |
| WordPress-style actions / filters for Laravel | [`artisanpack-ui/hooks`](https://github.com/ArtisanPack-UI/hooks) |
| Extensible icon registration on top of Blade Icons | [`artisanpack-ui/icons`](https://github.com/ArtisanPack-UI/icons) |
| 70+ pre-built Livewire UI components | [`artisanpack-ui/livewire-ui-components`](https://github.com/ArtisanPack-UI/livewire-ui-components) |
| Media upload, image processing, folder + tag management | [`artisanpack-ui/media-library`](https://github.com/ArtisanPack-UI/media-library) |
| CMS framework — posts, pages, taxonomies, settings | [`artisanpack-ui/cms-framework`](https://github.com/ArtisanPack-UI/cms-framework) |

## Documentation map

- [Getting Started](getting-started.md) — install, scaffold config, first `artisanpack:diagnose` run
- [Installation](installation.md) — requirements, configuration, environment variables, configuration management
- [Usage](usage.md) — service provider, configuration manager, logging, Blade directives, site resolution, diagnostics, compatibility, updates, Artisan commands, testing utilities, enums + exceptions
- [API Reference](api.md) — every public class, method, helper function, facade, exception, and enum the package exposes
- [Advanced](advanced.md) — creating new packages with `artisanpack:make-package`, upgrading from 1.2 to 1.3
- [FAQ](faq.md)
- [Troubleshooting](troubleshooting.md)
- [Changelog](../CHANGELOG.md)
