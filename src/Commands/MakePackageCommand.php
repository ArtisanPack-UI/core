<?php

/**
 * Artisan command to scaffold a new ArtisanPack UI package.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Commands
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand( name: 'artisanpack:make-package' )]
class MakePackageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artisanpack:make-package
                            {name : The package name in vendor/package format (e.g. artisanpack-ui/new-package)}
                            {--path= : Custom output path (default: ./<package-name>)}
                            {--with-livewire : Include Livewire component structure}
                            {--with-views : Include views directory and registration}
                            {--with-routes : Include routes directory and registration}
                            {--with-migrations : Include migrations directory and setup}
                            {--with-commands : Include example Artisan command}
                            {--force : Overwrite existing files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold a new ArtisanPack UI package with the standard structure and conventions.';

    /**
     * The filesystem instance.
     */
    protected Filesystem $files;

    public function __construct( Filesystem $files )
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = (string) $this->argument( 'name' );

        if ( ! $this->isValidPackageName( $name ) ) {
            $this->error( "Invalid package name '{$name}'. Expected format: vendor/package (e.g. artisanpack-ui/new-package)." );
            return self::FAILURE;
        }

        [ $vendor, $package ] = explode( '/', $name, 2 );

        $replacements = $this->buildReplacements( $vendor, $package );
        $targetPath   = $this->resolveTargetPath( $package );

        if ( $this->files->exists( $targetPath ) && ! $this->isDirectoryEmpty( $targetPath ) && ! $this->option( 'force' ) ) {
            $this->error( "Target directory '{$targetPath}' already exists and is not empty. Use --force to overwrite." );
            return self::FAILURE;
        }

        $this->files->ensureDirectoryExists( $targetPath );

        $this->info( "Scaffolding {$name} at {$targetPath}..." );

        $this->scaffoldBaseFiles( $targetPath, $replacements );

        if ( $this->option( 'with-views' ) ) {
            $this->scaffoldViews( $targetPath, $replacements );
        }

        if ( $this->option( 'with-routes' ) ) {
            $this->scaffoldRoutes( $targetPath, $replacements );
        }

        if ( $this->option( 'with-migrations' ) ) {
            $this->scaffoldMigrations( $targetPath );
        }

        if ( $this->option( 'with-commands' ) ) {
            $this->scaffoldExampleCommand( $targetPath, $replacements );
        }

        if ( $this->option( 'with-livewire' ) ) {
            $this->scaffoldLivewire( $targetPath, $replacements );
        }

        $this->info( "Package {$name} scaffolded successfully." );
        $this->line( "  Path: {$targetPath}" );
        $this->line( '  Next steps:' );
        $this->line( "    cd {$targetPath}" );
        $this->line( '    composer install' );

        return self::SUCCESS;
    }

    /**
     * Validate that the package name matches the vendor/package format.
     */
    protected function isValidPackageName( string $name ): bool
    {
        return (bool) preg_match( '/^[a-z0-9]([a-z0-9._-]*[a-z0-9])?\/[a-z0-9]([a-z0-9._-]*[a-z0-9])?$/', $name );
    }

    /**
     * Build the array of placeholder replacements based on the package name.
     *
     * @return array<string, string>
     */
    protected function buildReplacements( string $vendor, string $package ): array
    {
        $studlyVendor  = $this->studlyVendor( $vendor );
        $studlyPackage = Str::studly( str_replace( [ '-', '_', '.' ], ' ', $package ) );
        $namespace     = $studlyVendor . '\\' . $studlyPackage;

        return [
            '{{ vendor }}'           => $vendor,
            '{{ package }}'          => $package,
            '{{ PackageName }}'      => $studlyPackage,
            '{{ packageName }}'      => Str::camel( $package ),
            '{{ Namespace }}'        => $namespace,
            '{{ NamespaceEscaped }}' => str_replace( '\\', '\\\\', $namespace ),
            '{{ VendorNamespace }}'  => $studlyVendor,
            '{{ year }}'             => (string) date( 'Y' ),
        ];
    }

    /**
     * Produce the StudlyCase form of a vendor segment, with explicit overrides
     * for known ArtisanPack UI ecosystem vendors (so 'artisanpack-ui' becomes
     * 'ArtisanPackUI' instead of the default 'ArtisanpackUi').
     */
    protected function studlyVendor( string $vendor ): string
    {
        $overrides = [
            'artisanpack-ui' => 'ArtisanPackUI',
        ];

        if ( isset( $overrides[ $vendor ] ) ) {
            return $overrides[ $vendor ];
        }

        return Str::studly( str_replace( [ '-', '_', '.' ], ' ', $vendor ) );
    }

    /**
     * Resolve where the new package should live on disk.
     */
    protected function resolveTargetPath( string $package ): string
    {
        $path = (string) ( $this->option( 'path' ) ?: ( getcwd() . DIRECTORY_SEPARATOR . $package ) );

        if ( DIRECTORY_SEPARATOR === $path || 1 === preg_match( '/^[A-Za-z]:\\\\$/', $path ) ) {
            return $path;
        }

        return rtrim( $path, DIRECTORY_SEPARATOR );
    }

    /**
     * Determine whether the given directory exists and is empty.
     */
    protected function isDirectoryEmpty( string $path ): bool
    {
        if ( ! $this->files->isDirectory( $path ) ) {
            return true;
        }

        foreach ( $this->files->allFiles( $path, true ) as $file ) {
            return false;
        }

        foreach ( $this->files->directories( $path ) as $directory ) {
            return false;
        }

        return true;
    }

    /**
     * Scaffold the base files every package gets.
     *
     * @param array<string, string> $replacements
     */
    protected function scaffoldBaseFiles( string $targetPath, array $replacements ): void
    {
        $studlyPackage = $replacements['{{ PackageName }}'];

        $this->writeStub( 'composer.json.stub', $targetPath . '/composer.json', $replacements );
        $this->writeStub( 'README.md.stub', $targetPath . '/README.md', $replacements );
        $this->writeStub( 'CHANGELOG.md.stub', $targetPath . '/CHANGELOG.md', $replacements );
        $this->writeStub( 'CONTRIBUTING.md.stub', $targetPath . '/CONTRIBUTING.md', $replacements );
        $this->writeStub( 'LICENSE.stub', $targetPath . '/LICENSE', $replacements );
        $this->writeStub( 'gitignore.stub', $targetPath . '/.gitignore', $replacements );
        $this->writeStub( 'phpunit.xml.stub', $targetPath . '/phpunit.xml', $replacements );

        $this->writeStub( 'src/ServiceProvider.php.stub', $targetPath . "/src/{$studlyPackage}ServiceProvider.php", $replacements );
        $this->writeStub( 'src/Package.php.stub', $targetPath . "/src/{$studlyPackage}.php", $replacements );
        $this->writeStub( 'src/Facades/Facade.php.stub', $targetPath . "/src/Facades/{$studlyPackage}.php", $replacements );
        $this->writeStub( 'src/helpers.php.stub', $targetPath . '/src/helpers.php', $replacements );

        $this->writeStub( 'config/package.php.stub', $targetPath . '/config/' . $replacements['{{ package }}'] . '.php', $replacements );

        $this->writeStub( 'tests/TestCase.php.stub', $targetPath . '/tests/TestCase.php', $replacements );
        $this->writeStub( 'tests/Pest.php.stub', $targetPath . '/tests/Pest.php', $replacements );
        $this->writeStub( 'tests/Feature/ExampleTest.php.stub', $targetPath . '/tests/Feature/ExampleTest.php', $replacements );
        $this->writeStub( 'tests/Unit/ExampleTest.php.stub', $targetPath . '/tests/Unit/ExampleTest.php', $replacements );

        $this->files->ensureDirectoryExists( $targetPath . '/.gitlab/issue_templates' );
        $this->writeStub( 'gitlab/issue_template.md.stub', $targetPath . '/.gitlab/issue_templates/Default.md', $replacements );
    }

    /**
     * @param array<string, string> $replacements
     */
    protected function scaffoldViews( string $targetPath, array $replacements ): void
    {
        $this->files->ensureDirectoryExists( $targetPath . '/resources/views' );
        $this->writeStub( 'resources/views/example.blade.php.stub', $targetPath . '/resources/views/example.blade.php', $replacements );
    }

    /**
     * @param array<string, string> $replacements
     */
    protected function scaffoldRoutes( string $targetPath, array $replacements ): void
    {
        $this->files->ensureDirectoryExists( $targetPath . '/routes' );
        $this->writeStub( 'routes/web.php.stub', $targetPath . '/routes/web.php', $replacements );
    }

    protected function scaffoldMigrations( string $targetPath ): void
    {
        $this->files->ensureDirectoryExists( $targetPath . '/database/migrations' );
        $this->files->put(
            $targetPath . '/database/migrations/.gitkeep',
            '',
        );
    }

    /**
     * @param array<string, string> $replacements
     */
    protected function scaffoldExampleCommand( string $targetPath, array $replacements ): void
    {
        $studlyPackage = $replacements['{{ PackageName }}'];

        $this->writeStub(
            'src/Console/Commands/ExampleCommand.php.stub',
            $targetPath . "/src/Console/Commands/{$studlyPackage}ExampleCommand.php",
            $replacements,
        );
    }

    /**
     * @param array<string, string> $replacements
     */
    protected function scaffoldLivewire( string $targetPath, array $replacements ): void
    {
        $studlyPackage = $replacements['{{ PackageName }}'];

        $this->writeStub(
            'src/Livewire/ExampleComponent.php.stub',
            $targetPath . "/src/Livewire/{$studlyPackage}Example.php",
            $replacements,
        );
        $this->files->ensureDirectoryExists( $targetPath . '/resources/views/livewire' );
        $this->writeStub(
            'resources/views/livewire/example.blade.php.stub',
            $targetPath . '/resources/views/livewire/example.blade.php',
            $replacements,
        );
    }

    /**
     * Render a stub file into the target location with replacements applied.
     *
     * @param array<string, string> $replacements
     */
    protected function writeStub( string $stubPath, string $destination, array $replacements ): void
    {
        $fullStubPath = $this->stubBasePath() . DIRECTORY_SEPARATOR . $stubPath;

        if ( ! $this->files->exists( $fullStubPath ) ) {
            throw new RuntimeException( "Stub not found: {$fullStubPath}" );
        }

        $contents = $this->files->get( $fullStubPath );
        $contents = strtr( $contents, $replacements );

        $this->files->ensureDirectoryExists( dirname( $destination ) );

        if ( $this->files->exists( $destination ) && ! $this->option( 'force' ) ) {
            $this->line( "  Skipping (exists): {$destination}" );
            return;
        }

        $this->files->put( $destination, $contents );
        $this->line( "  Created: {$destination}" );
    }

    /**
     * Path to the stubs directory shipped with this package.
     */
    protected function stubBasePath(): string
    {
        return dirname( __DIR__, 2 ) . '/stubs/package';
    }
}
