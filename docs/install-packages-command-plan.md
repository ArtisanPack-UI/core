# Implementation Plan: ArtisanPack UI Package Installer Command

## Overview

This document outlines the implementation plan for adding an Artisan command to the `artisanpack-ui/core` package that allows developers to quickly install ArtisanPack UI packages from the command line.

## Problem Statement

Currently, developers must manually run individual `composer require` commands for each ArtisanPack UI package they want to install. This is tedious and error-prone, especially when installing multiple packages.

## Proposed Solution

Create a new Artisan command `artisanpack:install-packages` that presents an interactive multi-select menu allowing developers to choose which ArtisanPack UI packages to install, then installs them all in one operation.

---

## Implementation Details

### 1. Create the Console Command

**File:** `src/Commands/InstallPackagesCommand.php`

**Command Signature:** `artisanpack:install-packages`

**Description:** Install ArtisanPack UI packages interactively

### 2. Command Features

#### 2.1 Composer Packages Selection

Present a multi-select menu with all available ArtisanPack UI Composer packages:

| Package | Description |
|---------|-------------|
| `artisanpack-ui/accessibility` | Color contrast and WCAG compliance utilities |
| `artisanpack-ui/cms-framework` | CMS framework and content management |
| `artisanpack-ui/code-style` | PHP_CodeSniffer standards (dev dependency) |
| `artisanpack-ui/code-style-pint` | Laravel Pint configuration (dev dependency) |
| `artisanpack-ui/hooks` | WordPress-style actions and filters for Laravel |
| `artisanpack-ui/icons` | Extensible icon registration system |
| `artisanpack-ui/livewire-ui-components` | 70+ pre-built UI components |
| `artisanpack-ui/media-library` | Media management with image processing |
| `artisanpack-ui/security` | Security utilities, sanitization, 2FA |

#### 2.2 NPM Packages Selection

Present a second multi-select menu for NPM packages:

| Package | Description |
|---------|-------------|
| `@artisanpack-ui/livewire-drag-and-drop` | Drag and drop functionality for Livewire |

#### 2.3 Post-Installation Actions

After installing packages:
1. Automatically run `artisanpack:scaffold-config` to merge configurations
2. Run package-specific post-install commands (e.g., `artisanpack:generate-theme` for livewire-ui-components)
3. Display a summary of installed packages
4. Provide next steps guidance (run migrations, publish assets, etc.)

### 3. Command Flow

```
┌─────────────────────────────────────────────────────────┐
│  artisanpack:install-packages                           │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  Check PHP/Laravel version compatibility                │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  Multi-select: Which Composer packages to install?      │
│  [ ] artisanpack-ui/accessibility                       │
│  [ ] artisanpack-ui/cms-framework                       │
│  [ ] artisanpack-ui/code-style (dev)                    │
│  [ ] artisanpack-ui/code-style-pint (dev)               │
│  [ ] artisanpack-ui/hooks                               │
│  [ ] artisanpack-ui/icons                               │
│  [ ] artisanpack-ui/livewire-ui-components              │
│  [ ] artisanpack-ui/media-library                       │
│  [ ] artisanpack-ui/security                            │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  Multi-select: Which NPM packages to install?           │
│  [ ] @artisanpack-ui/livewire-drag-and-drop             │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  Display summary of what will be installed              │
│  (--dry-run stops here)                                 │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  Confirmation: "Proceed with installation?" (Y/n)       │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  composer require [selected-packages]                   │
│  composer require --dev [selected-dev-packages]         │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  npm install [selected-npm-packages]                    │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  Run artisanpack:scaffold-config                        │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  Run package-specific post-install commands             │
│  (e.g., artisanpack:generate-theme for UI components)   │
└─────────────────────────────────────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────────┐
│  Display success message and next steps                 │
└─────────────────────────────────────────────────────────┘
```

### 4. Code Structure

```php
<?php

namespace ArtisanPackUI\Core\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\warning;

class InstallPackagesCommand extends Command
{
    protected $signature = 'artisanpack:install-packages
                            {--packages= : Comma-separated list of packages for non-interactive mode}
                            {--npm-packages= : Comma-separated list of NPM packages for non-interactive mode}
                            {--skip-npm : Skip NPM packages prompt}
                            {--skip-scaffold : Skip running scaffold-config after installation}
                            {--skip-post-install : Skip package-specific post-install commands}
                            {--all : Install all packages (non-interactive)}
                            {--dry-run : Show what would be installed without executing}';

    protected $description = 'Install ArtisanPack UI packages interactively';

    /**
     * Minimum PHP version required for all packages.
     */
    protected string $minPhpVersion = '8.2.0';

    /**
     * Minimum Laravel version required for all packages.
     */
    protected string $minLaravelVersion = '10.0.0';

    /**
     * Composer packages available for installation.
     * Key = package name, Value = array with 'description', 'dev' flag, and optional 'postInstall' command
     */
    protected array $composerPackages = [
        'artisanpack-ui/accessibility' => [
            'description' => 'Color contrast and WCAG compliance utilities',
            'dev' => false,
            'postInstall' => null,
        ],
        'artisanpack-ui/cms-framework' => [
            'description' => 'CMS framework and content management',
            'dev' => false,
            'postInstall' => null,
        ],
        'artisanpack-ui/code-style' => [
            'description' => 'PHP_CodeSniffer standards',
            'dev' => true,
            'postInstall' => null,
        ],
        'artisanpack-ui/code-style-pint' => [
            'description' => 'Laravel Pint configuration',
            'dev' => true,
            'postInstall' => null,
        ],
        'artisanpack-ui/hooks' => [
            'description' => 'WordPress-style actions and filters',
            'dev' => false,
            'postInstall' => null,
        ],
        'artisanpack-ui/icons' => [
            'description' => 'Extensible icon registration system',
            'dev' => false,
            'postInstall' => null,
        ],
        'artisanpack-ui/livewire-ui-components' => [
            'description' => '70+ pre-built UI components',
            'dev' => false,
            'postInstall' => 'artisanpack:generate-theme',
        ],
        'artisanpack-ui/media-library' => [
            'description' => 'Media management with image processing',
            'dev' => false,
            'postInstall' => null,
        ],
        'artisanpack-ui/security' => [
            'description' => 'Security utilities, sanitization, 2FA',
            'dev' => false,
            'postInstall' => null,
        ],
    ];

    /**
     * NPM packages available for installation.
     */
    protected array $npmPackages = [
        '@artisanpack-ui/livewire-drag-and-drop' => 'Drag and drop for Livewire',
    ];

    public function handle(): int
    {
        // 1. Check version compatibility
        // 2. Select Composer packages (or use --packages/--all)
        // 3. Select NPM packages (or use --npm-packages/--skip-npm)
        // 4. Display installation summary
        // 5. If --dry-run, stop here
        // 6. Confirm before proceeding
        // 7. Install Composer packages (regular and dev separately)
        // 8. Install NPM packages
        // 9. Run scaffold-config (unless --skip-scaffold)
        // 10. Run post-install commands (unless --skip-post-install)
        // 11. Display success and next steps
    }

    /**
     * Check if the environment meets minimum version requirements.
     */
    protected function checkVersionCompatibility(): bool
    {
        // Check PHP version
        // Check Laravel version
        // Return false and display error if requirements not met
    }

    /**
     * Get the list of already-installed packages to exclude or mark.
     */
    protected function getInstalledPackages(): array
    {
        // Read composer.json/composer.lock to detect installed packages
    }

    /**
     * Display a summary of what will be installed.
     */
    protected function displayInstallationSummary(array $composerPackages, array $npmPackages): void
    {
        // Show formatted list of packages to be installed
    }

    /**
     * Run post-install commands for specific packages.
     */
    protected function runPostInstallCommands(array $installedPackages): void
    {
        // Loop through installed packages and run their postInstall commands
    }
}
```

### 5. Implementation Steps

#### Step 1: Create the Command File

Create `src/Commands/InstallPackagesCommand.php` with the full implementation.

#### Step 2: Register the Command

Update `CoreServiceProvider.php` to register the new command in the `boot()` method:

```php
if ( $this->app->runningInConsole() ) {
    $this->commands( [
        Commands\ScaffoldConfigCommand::class,
        Commands\InstallPackagesCommand::class, // Add this line
    ] );
}
```

#### Step 3: Add Laravel Prompts Dependency

Ensure `laravel/prompts` is available. It's included with Laravel 10+ by default, but we should add it as a dependency in `composer.json` for standalone usage:

```json
"require": {
    "php": "^8.2",
    "illuminate/support": "^10.0|^11.0|^12.0",
    "laravel/prompts": "^0.1|^0.2|^0.3"
}
```

#### Step 4: Write Tests

Create tests in `tests/Feature/InstallPackagesCommandTest.php`:

- Test command exists and is registered
- Test multiselect options are displayed correctly
- Test Composer command generation (mock shell_exec/Process)
- Test NPM command generation (mock shell_exec/Process)
- Test scaffold-config is called after installation
- Test version compatibility checking
- Test dry-run mode
- Test confirmation prompt
- Test post-install command execution

### 6. Additional Considerations

#### 6.1 Version Compatibility Checking

Check PHP and Laravel versions before allowing installation:

```php
protected function checkVersionCompatibility(): bool
{
    // Check PHP version
    if ( version_compare( PHP_VERSION, $this->minPhpVersion, '<' ) ) {
        $this->error( "PHP {$this->minPhpVersion} or higher is required. You have " . PHP_VERSION );
        return false;
    }

    // Check Laravel version
    $laravelVersion = app()->version();
    if ( version_compare( $laravelVersion, $this->minLaravelVersion, '<' ) ) {
        $this->error( "Laravel {$this->minLaravelVersion} or higher is required. You have {$laravelVersion}" );
        return false;
    }

    return true;
}
```

#### 6.2 Handling Dev Dependencies

The command should separate dev dependencies and run them with `--dev` flag:

```php
// Regular packages
composer require artisanpack-ui/hooks artisanpack-ui/security --with-all-dependencies

// Dev packages
composer require --dev artisanpack-ui/code-style artisanpack-ui/code-style-pint --with-all-dependencies
```

#### 6.3 Error Handling

- Check if Composer is available
- Check if NPM is available (only if NPM packages selected)
- Handle installation failures gracefully
- Display meaningful error messages

#### 6.4 Non-Interactive Mode

Add support for `--no-interaction` flag with options to specify packages directly:

```bash
php artisan artisanpack:install-packages --packages=hooks,security,livewire-ui-components
```

#### 6.5 Package Detection

Detect already-installed packages and exclude them from the selection list or mark them as "(installed)":

```php
protected function getInstalledPackages(): array
{
    $composerLock = base_path( 'composer.lock' );

    if ( ! file_exists( $composerLock ) ) {
        return [];
    }

    $lockData = json_decode( file_get_contents( $composerLock ), true );
    $installed = [];

    foreach ( $lockData['packages'] ?? [] as $package ) {
        if ( str_starts_with( $package['name'], 'artisanpack-ui/' ) ) {
            $installed[] = $package['name'];
        }
    }

    foreach ( $lockData['packages-dev'] ?? [] as $package ) {
        if ( str_starts_with( $package['name'], 'artisanpack-ui/' ) ) {
            $installed[] = $package['name'];
        }
    }

    return $installed;
}
```

#### 6.6 Dry Run Mode

The `--dry-run` option shows what would be installed without executing:

```php
if ( $this->option( 'dry-run' ) ) {
    $this->info( 'Dry run mode - no changes will be made.' );
    $this->displayInstallationSummary( $selectedComposerPackages, $selectedNpmPackages );
    return Command::SUCCESS;
}
```

#### 6.7 Confirmation Prompt

Before executing installation, confirm with the user:

```php
$this->displayInstallationSummary( $selectedComposerPackages, $selectedNpmPackages );

if ( ! confirm( 'Proceed with installation?', default: true ) ) {
    $this->info( 'Installation cancelled.' );
    return Command::SUCCESS;
}
```

#### 6.8 Post-Install Commands

Run package-specific commands **only for packages being installed in the current operation**. This ensures that if a user already has `livewire-ui-components` installed and runs the command to install other packages, the `artisanpack:generate-theme` command won't run again and potentially overwrite their existing theme customizations.

```php
/**
 * Run post-install commands for packages installed in this session.
 *
 * @param array $newlyInstalledPackages Packages selected for installation in this operation
 */
protected function runPostInstallCommands( array $newlyInstalledPackages ): void
{
    foreach ( $newlyInstalledPackages as $package ) {
        $config = $this->composerPackages[$package] ?? null;

        if ( $config && $config['postInstall'] ) {
            $this->info( "Running post-install command for {$package}..." );
            $this->call( $config['postInstall'] );
        }
    }
}
```

**Important:** The `$newlyInstalledPackages` array should contain only the packages the user selected for installation in this session, NOT packages that were already installed. This is the array of user selections from the multiselect prompt (after filtering out already-installed packages).

For `livewire-ui-components`, this will run `artisanpack:generate-theme` which generates the CSS theme file with default colors (sky primary, slate secondary, amber accent) - but only when the package is being freshly installed.

### 7. Command Options

| Option | Description |
|--------|-------------|
| `--packages=` | Comma-separated list of packages for non-interactive mode |
| `--npm-packages=` | Comma-separated list of NPM packages for non-interactive mode |
| `--skip-npm` | Skip NPM packages prompt |
| `--skip-scaffold` | Skip running scaffold-config after installation |
| `--skip-post-install` | Skip package-specific post-install commands |
| `--all` | Install all packages (non-interactive) |
| `--dry-run` | Show what would be installed without executing |

### 8. Example Output

#### Interactive Mode

```
 INFO  Checking version compatibility...

 ✓ PHP 8.3.0 meets minimum requirement (8.2.0)
 ✓ Laravel 12.0.0 meets minimum requirement (10.0.0)

 ┌ Which ArtisanPack UI packages would you like to install? ─────────────┐
 │ ◻ artisanpack-ui/accessibility - Color contrast and WCAG utilities    │
 │ ◻ artisanpack-ui/cms-framework - CMS framework and content management │
 │ ◼ artisanpack-ui/hooks - WordPress-style actions and filters          │
 │ ◼ artisanpack-ui/livewire-ui-components - 70+ pre-built UI components │
 │ ◻ artisanpack-ui/media-library - Media management                     │
 │ ◼ artisanpack-ui/security - Security utilities, sanitization, 2FA     │
 │ ◻ artisanpack-ui/code-style (dev) - PHP_CodeSniffer standards         │
 │ ◻ artisanpack-ui/code-style-pint (dev) - Laravel Pint configuration   │
 │ ◻ artisanpack-ui/icons - Extensible icon registration (installed)     │
 └───────────────────────────────────────────────────────────────────────┘

 ┌ Which NPM packages would you like to install? ────────────────────────┐
 │ ◻ @artisanpack-ui/livewire-drag-and-drop - Drag and drop for Livewire │
 └───────────────────────────────────────────────────────────────────────┘

 ┌─────────────────────────────────────────────────────────────────────────┐
 │ Installation Summary                                                    │
 ├─────────────────────────────────────────────────────────────────────────┤
 │ Composer packages (3):                                                  │
 │   • artisanpack-ui/hooks                                                │
 │   • artisanpack-ui/livewire-ui-components                               │
 │   • artisanpack-ui/security                                             │
 │                                                                         │
 │ NPM packages (0):                                                       │
 │   (none selected)                                                       │
 │                                                                         │
 │ Post-install commands:                                                  │
 │   • artisanpack:generate-theme (livewire-ui-components)                 │
 └─────────────────────────────────────────────────────────────────────────┘

 ┌ Proceed with installation? ───────────────────────────────────────────┐
 │ ● Yes / ○ No                                                          │
 └───────────────────────────────────────────────────────────────────────┘

 INFO  Installing Composer packages...

 Running: composer require artisanpack-ui/hooks artisanpack-ui/livewire-ui-components artisanpack-ui/security --with-all-dependencies

 INFO  Composer packages installed successfully.

 INFO  Scaffolding ArtisanPack configuration...

 INFO  Running post-install commands...

 Running: artisanpack:generate-theme
 ✅ ArtisanPack UI theme CSS file generated successfully!

 INFO  Installation complete!

 Next steps:
 • Run migrations: php artisan migrate
 • Import theme CSS in your main stylesheet
 • Recompile assets: npm run dev
 • Review configuration: config/artisanpack.php
```

#### Dry Run Mode

```bash
php artisan artisanpack:install-packages --dry-run
```

```
 INFO  Dry run mode - no changes will be made.

 ┌─────────────────────────────────────────────────────────────────────────┐
 │ Installation Summary (DRY RUN)                                          │
 ├─────────────────────────────────────────────────────────────────────────┤
 │ Composer packages (3):                                                  │
 │   • artisanpack-ui/hooks                                                │
 │   • artisanpack-ui/livewire-ui-components                               │
 │   • artisanpack-ui/security                                             │
 │                                                                         │
 │ Commands that would run:                                                │
 │   1. composer require artisanpack-ui/hooks artisanpack-ui/livewire...   │
 │   2. php artisan artisanpack:scaffold-config                            │
 │   3. php artisan artisanpack:generate-theme                             │
 └─────────────────────────────────────────────────────────────────────────┘
```

---

## Testing Plan

### Unit Tests

1. Test package arrays are properly structured
2. Test dev package separation logic
3. Test command string generation
4. Test version comparison logic
5. Test installed package detection

### Feature Tests

1. Test command registration
2. Test interactive prompts (using Pest's interactive testing)
3. Test `--no-interaction` mode with `--packages` option
4. Test `--all` flag installs all packages
5. Test `--skip-npm`, `--skip-scaffold`, and `--skip-post-install` flags
6. Test `--dry-run` mode displays summary without executing
7. Test confirmation prompt cancellation
8. Test version compatibility failures
9. Test already-installed package detection
10. Test post-install command execution for livewire-ui-components when freshly installed
11. Test post-install command does NOT run for already-installed packages (e.g., generate-theme should not run if livewire-ui-components is already installed and user is installing other packages)

---

## Implementation Steps

1. **Create command file** - Basic structure with package lists and version checking
2. **Implement version compatibility** - PHP and Laravel version checks
3. **Implement package detection** - Detect already-installed packages
4. **Implement Composer installation** - With dev dependency separation
5. **Implement NPM installation** - Similar pattern
6. **Add dry-run mode** - Display summary without executing
7. **Add confirmation prompt** - Require user confirmation before install
8. **Add scaffold-config integration** - Call existing command
9. **Add post-install commands** - Run artisanpack:generate-theme for livewire-ui-components
10. **Add command options** - Non-interactive mode, skip flags
11. **Write tests** - Full test coverage
12. **Update documentation** - Add command to package docs

---

## Files to Create/Modify

| File | Action |
|------|--------|
| `src/Commands/InstallPackagesCommand.php` | Create |
| `src/CoreServiceProvider.php` | Modify (register command) |
| `composer.json` | Modify (add laravel/prompts dependency, update illuminate/support version) |
| `tests/Feature/InstallPackagesCommandTest.php` | Create |
| `docs/usage-examples.md` | Modify (add command documentation) |
| `README.md` | Modify (add command to available commands section) |
