---
title: Requirements
---

# Requirements

ArtisanPack UI Core is the foundation for every other ArtisanPack UI package, so its baseline requirements are the floor for the entire ecosystem.

## PHP

- **PHP 8.2 or newer** is required. PHP 8.2 users continue to receive Laravel 11 / 12; PHP 8.3+ unlocks Laravel 13 (Laravel enforces its own PHP constraint).

## Laravel

- **Laravel 11, 12, or 13** is supported. The composer constraint is `^11.0|^12.0|^13.0`.
- Laravel 10 is no longer supported as of Core 1.1.
- Laravel 13 support was added in Core 1.2 — the package transparently degrades to Laravel 11 / 12 APIs on older framework versions and uses the `artisanpack_supports_laravel_13()` helper or `method_exists()` feature detection where Laravel 13-only APIs are involved.

## PHP extensions

The same extensions Laravel core itself requires are enforced by the diagnostic runner's `EnvironmentCheck`:

- `ctype`
- `json`
- `mbstring`
- `openssl`
- `pdo`
- `tokenizer`

A missing extension is reported as an error by `php artisan artisanpack:diagnose`.

## Other ArtisanPack UI packages

Every sibling ArtisanPack UI package declares `artisanpack-ui/core: ^1.0` in its composer requirements. Adding any sibling package therefore pulls Core in automatically — you only install Core directly if you want the diagnostic / compatibility / update tooling on its own.

Use `php artisan artisanpack:check-compatibility` to confirm every installed sibling package satisfies its declared constraints against the running PHP / Laravel / extension stack.
