<?php

/**
 * Artisan command to install ArtisanPack UI packages interactively.
 *
 * @since      1.1.0
 */

namespace ArtisanPackUI\Core\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;

#[AsCommand(name: 'artisanpack:install-packages')]
class InstallPackagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artisanpack:install-packages
                            {--packages= : Comma-separated list of packages for non-interactive mode}
                            {--npm-packages= : Comma-separated list of NPM packages for non-interactive mode}
                            {--skip-npm : Skip NPM packages prompt}
                            {--skip-scaffold : Skip running scaffold-config after installation}
                            {--skip-post-install : Skip package-specific post-install commands}
                            {--all : Install all packages (non-interactive)}
                            {--dry-run : Show what would be installed without executing}';

    /**
     * The console command description.
     *
     * @var string
     */
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
     *
     * @var array<string, array{description: string, dev: bool, postInstall: string|null}>
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
     *
     * @var array<string, string>
     */
    protected array $npmPackages = [
        '@artisanpack-ui/livewire-drag-and-drop' => 'Drag and drop for Livewire',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Check version compatibility
        if (! $this->checkVersionCompatibility()) {
            return self::FAILURE;
        }

        // Get already installed packages
        $installedPackages = $this->getInstalledPackages();

        // Select Composer packages
        $selectedComposerPackages = $this->selectComposerPackages($installedPackages);

        // Select NPM packages
        $selectedNpmPackages = [];
        if (! $this->option('skip-npm')) {
            $selectedNpmPackages = $this->selectNpmPackages();
        }

        // If nothing selected, exit early
        if (empty($selectedComposerPackages) && empty($selectedNpmPackages)) {
            $this->info('No packages selected for installation.');

            return self::SUCCESS;
        }

        // Display installation summary
        $this->displayInstallationSummary($selectedComposerPackages, $selectedNpmPackages);

        // If dry-run, stop here
        if ($this->option('dry-run')) {
            info('Dry run mode - no changes were made.');

            return self::SUCCESS;
        }

        // Confirm before proceeding
        if (! confirm(__('Proceed with installation?'), default: true)) {
            $this->info('Installation cancelled.');

            return self::SUCCESS;
        }

        // Install Composer packages
        if (! empty($selectedComposerPackages)) {
            $this->installComposerPackages($selectedComposerPackages);
        }

        // Install NPM packages
        if (! empty($selectedNpmPackages)) {
            $this->installNpmPackages($selectedNpmPackages);
        }

        // Run scaffold-config
        if (! $this->option('skip-scaffold')) {
            $this->info('Scaffolding ArtisanPack configuration...');
            $this->call('artisanpack:scaffold-config');
        }

        // Run post-install commands for newly installed packages only
        if (! $this->option('skip-post-install')) {
            $this->runPostInstallCommands($selectedComposerPackages);
        }

        // Display success message
        $this->displayNextSteps($selectedComposerPackages, $selectedNpmPackages);

        return self::SUCCESS;
    }

    /**
     * Check if the environment meets minimum version requirements.
     */
    protected function checkVersionCompatibility(): bool
    {
        $this->info('Checking version compatibility...');

        // Check PHP version
        if (version_compare(PHP_VERSION, $this->minPhpVersion, '<')) {
            $this->error("PHP {$this->minPhpVersion} or higher is required. You have ".PHP_VERSION);

            return false;
        }

        $this->line('  ✓ PHP '.PHP_VERSION." meets minimum requirement ({$this->minPhpVersion})");

        // Check Laravel version
        $laravelVersion = app()->version();
        if (version_compare($laravelVersion, $this->minLaravelVersion, '<')) {
            $this->error("Laravel {$this->minLaravelVersion} or higher is required. You have {$laravelVersion}");

            return false;
        }

        $this->line("  ✓ Laravel {$laravelVersion} meets minimum requirement ({$this->minLaravelVersion})");
        $this->newLine();

        return true;
    }

    /**
     * Get the list of already-installed ArtisanPack UI packages.
     *
     * @return array<string>
     */
    protected function getInstalledPackages(): array
    {
        $composerLock = base_path('composer.lock');

        if (! file_exists($composerLock)) {
            return [];
        }

        $lockData = json_decode(file_get_contents($composerLock), true);
        $installed = [];

        foreach ($lockData['packages'] ?? [] as $package) {
            if (str_starts_with($package['name'], 'artisanpack-ui/')) {
                $installed[] = $package['name'];
            }
        }

        foreach ($lockData['packages-dev'] ?? [] as $package) {
            if (str_starts_with($package['name'], 'artisanpack-ui/')) {
                $installed[] = $package['name'];
            }
        }

        return $installed;
    }

    /**
     * Select Composer packages to install.
     *
     * @param  array<string>  $installedPackages  Already installed packages.
     * @return array<string>
     */
    protected function selectComposerPackages(array $installedPackages): array
    {
        // Handle --all flag
        if ($this->option('all')) {
            return array_diff(array_keys($this->composerPackages), $installedPackages);
        }

        // Handle --packages flag for non-interactive mode
        if ($this->option('packages')) {
            $requestedPackages = array_map('trim', explode(',', $this->option('packages')));
            $validPackages = [];

            foreach ($requestedPackages as $package) {
                // Handle short names (without vendor prefix)
                $fullName = str_contains($package, '/') ? $package : "artisanpack-ui/{$package}";

                if (isset($this->composerPackages[$fullName]) && ! in_array($fullName, $installedPackages, true)) {
                    $validPackages[] = $fullName;
                }
            }

            return $validPackages;
        }

        // Build options for multiselect
        $options = [];
        foreach ($this->composerPackages as $package => $config) {
            $label = $package.' - '.$config['description'];

            if ($config['dev']) {
                $label .= ' (dev)';
            }

            if (in_array($package, $installedPackages, true)) {
                $label .= ' (installed)';
            }

            $options[$package] = $label;
        }

        // Filter out already installed packages from selectable options
        $selectableOptions = array_filter(
            $options,
            fn ($package) => ! in_array($package, $installedPackages, true),
            ARRAY_FILTER_USE_KEY
        );

        if (empty($selectableOptions)) {
            $this->info('All ArtisanPack UI Composer packages are already installed.');

            return [];
        }

        return multiselect(
            label: __('Which ArtisanPack UI packages would you like to install?'),
            options: $selectableOptions,
            hint: __('Use space to select, enter to confirm')
        );
    }

    /**
     * Select NPM packages to install.
     *
     * @return array<string>
     */
    protected function selectNpmPackages(): array
    {
        // Handle --npm-packages flag for non-interactive mode
        if ($this->option('npm-packages')) {
            $requestedPackages = array_map('trim', explode(',', $this->option('npm-packages')));

            return array_filter(
                $requestedPackages,
                fn ($package) => isset($this->npmPackages[$package])
            );
        }

        // Handle --all flag
        if ($this->option('all')) {
            return array_keys($this->npmPackages);
        }

        if (empty($this->npmPackages)) {
            return [];
        }

        // Build options for multiselect
        $options = [];
        foreach ($this->npmPackages as $package => $description) {
            $options[$package] = $package.' - '.$description;
        }

        return multiselect(
            label: __('Which NPM packages would you like to install?'),
            options: $options,
            hint: __('Use space to select, enter to confirm')
        );
    }

    /**
     * Display a summary of what will be installed.
     *
     * @param  array<string>  $composerPackages  Composer packages to install.
     * @param  array<string>  $npmPackages  NPM packages to install.
     */
    protected function displayInstallationSummary(array $composerPackages, array $npmPackages): void
    {
        $isDryRun = $this->option('dry-run');
        $title = $isDryRun ? 'Installation Summary (DRY RUN)' : 'Installation Summary';

        $this->newLine();
        $this->line('┌─────────────────────────────────────────────────────────────────────────┐');
        $this->line("│ {$title}".str_repeat(' ', 73 - strlen($title) - 2).'│');
        $this->line('├─────────────────────────────────────────────────────────────────────────┤');

        // Composer packages
        $regularPackages = [];
        $devPackages = [];

        foreach ($composerPackages as $package) {
            if ($this->composerPackages[$package]['dev'] ?? false) {
                $devPackages[] = $package;
            } else {
                $regularPackages[] = $package;
            }
        }

        $this->line('│ Composer packages ('.count($composerPackages).'):'.str_repeat(' ', 51 - strlen((string) count($composerPackages))).'│');

        if (empty($composerPackages)) {
            $this->line('│   (none selected)'.str_repeat(' ', 54).'│');
        } else {
            foreach ($regularPackages as $package) {
                $line = "  • {$package}";
                $this->line("│{$line}".str_repeat(' ', 73 - strlen($line)).'│');
            }
            foreach ($devPackages as $package) {
                $line = "  • {$package} (dev)";
                $this->line("│{$line}".str_repeat(' ', 73 - strlen($line)).'│');
            }
        }

        $this->line('│'.str_repeat(' ', 73).'│');

        // NPM packages
        $this->line('│ NPM packages ('.count($npmPackages).'):'.str_repeat(' ', 56 - strlen((string) count($npmPackages))).'│');

        if (empty($npmPackages)) {
            $this->line('│   (none selected)'.str_repeat(' ', 54).'│');
        } else {
            foreach ($npmPackages as $package) {
                $line = "  • {$package}";
                $this->line("│{$line}".str_repeat(' ', 73 - strlen($line)).'│');
            }
        }

        // Post-install commands
        $postInstallCommands = $this->getPostInstallCommands($composerPackages);

        if (! empty($postInstallCommands) && ! $this->option('skip-post-install')) {
            $this->line('│'.str_repeat(' ', 73).'│');
            $this->line('│ Post-install commands:'.str_repeat(' ', 50).'│');

            foreach ($postInstallCommands as $package => $command) {
                $line = "  • {$command} ({$package})";
                if (strlen($line) > 71) {
                    $line = substr($line, 0, 68).'...';
                }
                $this->line("│{$line}".str_repeat(' ', 73 - strlen($line)).'│');
            }
        }

        // Commands that would run (for dry-run)
        if ($isDryRun) {
            $this->line('│'.str_repeat(' ', 73).'│');
            $this->line('│ Commands that would run:'.str_repeat(' ', 48).'│');

            $commandNum = 1;

            if (! empty($regularPackages)) {
                $cmd = "{$commandNum}. composer require ".implode(' ', array_slice($regularPackages, 0, 2));
                $cmd .= count($regularPackages) > 2 ? '...' : '';
                if (strlen($cmd) > 71) {
                    $cmd = substr($cmd, 0, 68).'...';
                }
                $this->line("│   {$cmd}".str_repeat(' ', 71 - strlen($cmd)).'│');
                $commandNum++;
            }

            if (! empty($devPackages)) {
                $cmd = "{$commandNum}. composer require --dev ".implode(' ', $devPackages);
                if (strlen($cmd) > 71) {
                    $cmd = substr($cmd, 0, 68).'...';
                }
                $this->line("│   {$cmd}".str_repeat(' ', 71 - strlen($cmd)).'│');
                $commandNum++;
            }

            if (! empty($npmPackages)) {
                $cmd = "{$commandNum}. npm install ".implode(' ', $npmPackages);
                if (strlen($cmd) > 71) {
                    $cmd = substr($cmd, 0, 68).'...';
                }
                $this->line("│   {$cmd}".str_repeat(' ', 71 - strlen($cmd)).'│');
                $commandNum++;
            }

            if (! $this->option('skip-scaffold')) {
                $cmd = "{$commandNum}. php artisan artisanpack:scaffold-config";
                $this->line("│   {$cmd}".str_repeat(' ', 71 - strlen($cmd)).'│');
                $commandNum++;
            }

            foreach ($postInstallCommands as $command) {
                $cmd = "{$commandNum}. php artisan {$command}";
                $this->line("│   {$cmd}".str_repeat(' ', 71 - strlen($cmd)).'│');
                $commandNum++;
            }
        }

        $this->line('└─────────────────────────────────────────────────────────────────────────┘');
        $this->newLine();
    }

    /**
     * Get post-install commands for the selected packages.
     *
     * @param  array<string>  $packages  Selected packages.
     * @return array<string, string>
     */
    protected function getPostInstallCommands(array $packages): array
    {
        $commands = [];

        foreach ($packages as $package) {
            $config = $this->composerPackages[$package] ?? null;

            if ($config && $config['postInstall']) {
                $shortName = str_replace('artisanpack-ui/', '', $package);
                $commands[$shortName] = $config['postInstall'];
            }
        }

        return $commands;
    }

    /**
     * Install Composer packages.
     *
     * @param  array<string>  $packages  Packages to install.
     */
    protected function installComposerPackages(array $packages): void
    {
        $regularPackages = [];
        $devPackages = [];

        foreach ($packages as $package) {
            if ($this->composerPackages[$package]['dev'] ?? false) {
                $devPackages[] = $package;
            } else {
                $regularPackages[] = $package;
            }
        }

        // Install regular packages
        if (! empty($regularPackages)) {
            $this->info('Installing Composer packages...');
            $command = 'composer require '.implode(' ', $regularPackages).' --with-all-dependencies';
            $this->line("Running: {$command}");
            $this->newLine();

            passthru($command, $exitCode);

            if ($exitCode !== 0) {
                $this->error('Failed to install Composer packages.');

                return;
            }

            $this->newLine();
            $this->info('Composer packages installed successfully.');
        }

        // Install dev packages
        if (! empty($devPackages)) {
            $this->info('Installing Composer dev packages...');
            $command = 'composer require --dev '.implode(' ', $devPackages).' --with-all-dependencies';
            $this->line("Running: {$command}");
            $this->newLine();

            passthru($command, $exitCode);

            if ($exitCode !== 0) {
                $this->error('Failed to install Composer dev packages.');

                return;
            }

            $this->newLine();
            $this->info('Composer dev packages installed successfully.');
        }
    }

    /**
     * Install NPM packages.
     *
     * @param  array<string>  $packages  Packages to install.
     */
    protected function installNpmPackages(array $packages): void
    {
        $this->info('Installing NPM packages...');
        $command = 'npm install '.implode(' ', $packages);
        $this->line("Running: {$command}");
        $this->newLine();

        passthru($command, $exitCode);

        if ($exitCode !== 0) {
            $this->error('Failed to install NPM packages.');

            return;
        }

        $this->newLine();
        $this->info('NPM packages installed successfully.');
    }

    /**
     * Run post-install commands for packages installed in this session.
     *
     * @param  array<string>  $newlyInstalledPackages  Packages selected for installation in this operation.
     */
    protected function runPostInstallCommands(array $newlyInstalledPackages): void
    {
        $commands = $this->getPostInstallCommands($newlyInstalledPackages);

        if (empty($commands)) {
            return;
        }

        $this->info('Running post-install commands...');

        foreach ($commands as $packageName => $command) {
            $this->line("Running {$command} for {$packageName}...");
            $this->call($command);
        }
    }

    /**
     * Display next steps after installation.
     *
     * @param  array<string>  $composerPackages  Installed Composer packages.
     * @param  array<string>  $npmPackages  Installed NPM packages.
     */
    protected function displayNextSteps(array $composerPackages, array $npmPackages): void
    {
        $this->newLine();
        $this->info('Installation complete!');
        $this->newLine();
        $this->line('Next steps:');

        // Check if any package might need migrations
        $packagesNeedingMigrations = ['artisanpack-ui/media-library', 'artisanpack-ui/cms-framework'];
        $needsMigrations = ! empty(array_intersect($composerPackages, $packagesNeedingMigrations));

        if ($needsMigrations) {
            $this->line('  • Run migrations: php artisan migrate');
        }

        // Check if livewire-ui-components was installed
        if (in_array('artisanpack-ui/livewire-ui-components', $composerPackages, true)) {
            $this->line('  • Import theme CSS in your main stylesheet');
        }

        if (! empty($npmPackages) || in_array('artisanpack-ui/livewire-ui-components', $composerPackages, true)) {
            $this->line('  • Recompile assets: npm run dev');
        }

        $this->line('  • Review configuration: config/artisanpack.php');
    }
}
