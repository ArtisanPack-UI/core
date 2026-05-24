---
title: Diagnostics
---

# Diagnostics

The diagnostic runner asks five questions about your ArtisanPack UI installation and produces a structured report that the `artisanpack:diagnose` command can render as human-readable output or JSON. It's the first thing to run when an ArtisanPack UI install behaves unexpectedly.

## Default checks

| Check id | Class | Purpose |
|---|---|---|
| `environment` | `EnvironmentCheck` | PHP version (≥ 8.2), Laravel version (≥ 10.0), Livewire version (when installed), required PHP extensions (`ctype`, `json`, `mbstring`, `openssl`, `pdo`, `tokenizer`). |
| `packages` | `InstalledPackagesCheck` | Reports the installed version of every known ArtisanPack UI package and flags unmet optional packages. |
| `configuration` | `ConfigurationCheck` | Confirms `config/artisanpack.php` exists and contains a section for every installed package. Supports auto-fix via `artisanpack:scaffold-config`. |
| `service-bindings` | `ServiceBindingsCheck` | Resolves the `core` and `a11y` aliases out of the container and confirms they point at the expected concrete implementations. |
| `commands` | `ArtisanCommandsCheck` | Confirms the Artisan commands Core publishes (`artisanpack:scaffold-config`, `artisanpack:install-packages`, `artisanpack:diagnose`) are registered. |

## Running the report

```bash
# Full report
php artisan artisanpack:diagnose

# Restrict to a single check
php artisan artisanpack:diagnose --package=environment

# JSON payload (suitable for CI / scripting)
php artisan artisanpack:diagnose --json

# Auto-fix the fixable checks
php artisan artisanpack:diagnose --fix
```

The command exits non-zero when any check reports an error (or when `--fix` was supplied and a fix failed).

## Verbose output

Run with Symfony's `-v` flag to expand each result's `details` array in the report:

```bash
php artisan artisanpack:diagnose -v
```

## Programmatic use

Resolve the runner from the container and run it directly when you need the report inside your application:

```php
use ArtisanPackUI\Core\Diagnostics\DiagnosticRunner;

$report = app( DiagnosticRunner::class )->run();

if ( $report->hasErrors() ) {
    // Surface the report somewhere — admin dashboard, status endpoint, etc.
    $payload = $report->toArray();
}

$report->passedCount();   // (int) number of passing results
$report->warningCount();  // (int) number of warning results
$report->errorCount();    // (int) number of error results
$report->allResults();    // array<int, DiagnosticResult> — flattened
```

## Auto-fix

The runner exposes only the checks that opt into auto-fix via `supportsFix()`. Today the only fixable check is `ConfigurationCheck`, which delegates to `php artisan artisanpack:scaffold-config` to publish or repair the missing config file.

```bash
php artisan artisanpack:diagnose --fix
```

Programmatically:

```php
$runner = app( DiagnosticRunner::class );

foreach ( $runner->fixableChecks() as $check ) {
    $check->fix( fn ( string $message ) => echo "  {$message}\n" );
}
```

## Custom checks

Implement the `DiagnosticCheck` interface and register your check with the runner. The interface requires:

- `id(): string` — kebab-case identifier consumed by the `--package` filter
- `name(): string` — human-readable heading shown in the report
- `run(): array<int, DiagnosticResult>` — the result rows for the check
- `supportsFix(): bool`
- `fix( callable $writer ): bool` — return `true` on success ( including no-op )

```php
use ArtisanPackUI\Core\Diagnostics\Checks\DiagnosticCheck;
use ArtisanPackUI\Core\Diagnostics\DiagnosticResult;
use ArtisanPackUI\Core\Diagnostics\DiagnosticStatus;
use ArtisanPackUI\Core\Diagnostics\DiagnosticRunner;

class MailHealthCheck implements DiagnosticCheck
{
    public function id(): string { return 'mail-health'; }

    public function name(): string { return 'Mail Health'; }

    public function run(): array
    {
        $reachable = $this->probeMailServer();

        return [ new DiagnosticResult(
            label:   'SMTP reachable',
            status:  $reachable ? DiagnosticStatus::Pass : DiagnosticStatus::Error,
            message: $reachable ? '' : 'Could not connect to SMTP server.',
        ) ];
    }

    public function supportsFix(): bool { return false; }

    public function fix( callable $writer ): bool { return false; }

    protected function probeMailServer(): bool
    {
        // ...
    }
}

// In a service provider's boot() method
app( DiagnosticRunner::class )->setChecks(
    array_merge( app( DiagnosticRunner::class )->checks(), [ new MailHealthCheck ] ),
);
```

## Result severities

Every result row carries one of four severities ( `DiagnosticStatus` enum cases ):

| Severity | Symbol | Meaning |
|---|---|---|
| `Pass` | ✓ | The check passed. |
| `Warning` | ⚠ | Non-fatal issue ( e.g. optional package missing, config drift ). |
| `Error` | ✗ | Fatal issue — block deploy / cause `--fix` to trigger. |
| `Info` | ℹ | Informational only; reports metadata without grading it. |
