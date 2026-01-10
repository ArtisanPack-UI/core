<?php

use ArtisanPackUI\Core\Commands\InstallPackagesCommand;
use Illuminate\Support\Facades\Artisan;

/**
 * Helper function to get protected/private property value.
 */
function getProtectedProperty(object $object, string $propertyName): mixed
{
    $reflection = new ReflectionClass($object);
    $property = $reflection->getProperty($propertyName);
    $property->setAccessible(true);

    return $property->getValue($object);
}

/**
 * Helper function to call protected/private method.
 */
function callProtectedMethod(object $object, string $methodName, array $args = []): mixed
{
    $reflection = new ReflectionClass($object);
    $method = $reflection->getMethod($methodName);
    $method->setAccessible(true);

    return $method->invokeArgs($object, $args);
}

test('command is registered', function () {
    $commands = Artisan::all();

    expect($commands)->toHaveKey('artisanpack:install-packages');
    expect($commands['artisanpack:install-packages'])->toBeInstanceOf(InstallPackagesCommand::class);
});

test('command has correct signature options', function () {
    $command = new InstallPackagesCommand;

    $definition = $command->getDefinition();

    expect($definition->hasOption('packages'))->toBeTrue();
    expect($definition->hasOption('npm-packages'))->toBeTrue();
    expect($definition->hasOption('skip-npm'))->toBeTrue();
    expect($definition->hasOption('skip-scaffold'))->toBeTrue();
    expect($definition->hasOption('skip-post-install'))->toBeTrue();
    expect($definition->hasOption('all'))->toBeTrue();
    expect($definition->hasOption('dry-run'))->toBeTrue();
});

test('composer packages array is properly structured', function () {
    $command = new InstallPackagesCommand;
    $packages = getProtectedProperty($command, 'composerPackages');

    expect($packages)->toBeArray();
    expect($packages)->not->toBeEmpty();

    foreach ($packages as $name => $config) {
        expect($name)->toStartWith('artisanpack-ui/');
        expect($config)->toHaveKeys(['description', 'dev', 'postInstall']);
        expect($config['description'])->toBeString();
        expect($config['dev'])->toBeBool();
    }
});

test('npm packages array is properly structured', function () {
    $command = new InstallPackagesCommand;
    $packages = getProtectedProperty($command, 'npmPackages');

    expect($packages)->toBeArray();

    foreach ($packages as $name => $description) {
        expect($name)->toBeString();
        expect($description)->toBeString();
    }
});

test('livewire-ui-components has generate-theme post-install command', function () {
    $command = new InstallPackagesCommand;
    $packages = getProtectedProperty($command, 'composerPackages');

    expect($packages)->toHaveKey('artisanpack-ui/livewire-ui-components');
    expect($packages['artisanpack-ui/livewire-ui-components']['postInstall'])->toBe('artisanpack:generate-theme');
});

test('dev packages are correctly identified', function () {
    $command = new InstallPackagesCommand;
    $packages = getProtectedProperty($command, 'composerPackages');

    $devPackages = array_filter($packages, fn ($config) => $config['dev'] === true);

    expect($devPackages)->toHaveKey('artisanpack-ui/code-style');
    expect($devPackages)->toHaveKey('artisanpack-ui/code-style-pint');
    expect(count($devPackages))->toBe(2);
});

test('non-dev packages are correctly identified', function () {
    $command = new InstallPackagesCommand;
    $packages = getProtectedProperty($command, 'composerPackages');

    $nonDevPackages = array_filter($packages, fn ($config) => $config['dev'] === false);

    expect($nonDevPackages)->toHaveKey('artisanpack-ui/accessibility');
    expect($nonDevPackages)->toHaveKey('artisanpack-ui/hooks');
    expect($nonDevPackages)->toHaveKey('artisanpack-ui/livewire-ui-components');
    expect($nonDevPackages)->toHaveKey('artisanpack-ui/security');
});

test('version compatibility passes with current PHP version', function () {
    $command = new InstallPackagesCommand;
    $minVersion = getProtectedProperty($command, 'minPhpVersion');

    expect(version_compare(PHP_VERSION, $minVersion, '>='))->toBeTrue();
});

test('version compatibility passes with current Laravel version', function () {
    $command = new InstallPackagesCommand;
    $minVersion = getProtectedProperty($command, 'minLaravelVersion');

    expect(version_compare(app()->version(), $minVersion, '>='))->toBeTrue();
});

test('get installed packages returns array', function () {
    $command = new InstallPackagesCommand;
    $installed = callProtectedMethod($command, 'getInstalledPackages');

    expect($installed)->toBeArray();
});

test('get post install commands returns only commands for packages with post install', function () {
    $command = new InstallPackagesCommand;

    // Test with livewire-ui-components (has post-install)
    $commands = callProtectedMethod($command, 'getPostInstallCommands', [['artisanpack-ui/livewire-ui-components']]);
    expect($commands)->toHaveKey('livewire-ui-components');
    expect($commands['livewire-ui-components'])->toBe('artisanpack:generate-theme');

    // Test with hooks (no post-install)
    $commands = callProtectedMethod($command, 'getPostInstallCommands', [['artisanpack-ui/hooks']]);
    expect($commands)->toBeEmpty();

    // Test with mixed packages
    $commands = callProtectedMethod($command, 'getPostInstallCommands', [[
        'artisanpack-ui/hooks',
        'artisanpack-ui/livewire-ui-components',
        'artisanpack-ui/security',
    ]]);
    expect($commands)->toHaveCount(1);
    expect($commands)->toHaveKey('livewire-ui-components');
});

test('post install commands are not returned for packages not in selection', function () {
    $command = new InstallPackagesCommand;

    // Only selecting hooks and security (no post-install commands)
    $commands = callProtectedMethod($command, 'getPostInstallCommands', [[
        'artisanpack-ui/hooks',
        'artisanpack-ui/security',
    ]]);

    expect($commands)->toBeEmpty();
});

test('command description is set', function () {
    $command = new InstallPackagesCommand;

    expect($command->getDescription())->toBe('Install ArtisanPack UI packages interactively');
});

test('all expected packages are available', function () {
    $command = new InstallPackagesCommand;
    $packages = getProtectedProperty($command, 'composerPackages');

    $expectedPackages = [
        'artisanpack-ui/accessibility',
        'artisanpack-ui/cms-framework',
        'artisanpack-ui/code-style',
        'artisanpack-ui/code-style-pint',
        'artisanpack-ui/hooks',
        'artisanpack-ui/icons',
        'artisanpack-ui/livewire-ui-components',
        'artisanpack-ui/media-library',
        'artisanpack-ui/security',
    ];

    foreach ($expectedPackages as $expected) {
        expect($packages)->toHaveKey($expected);
    }

    expect(count($packages))->toBe(count($expectedPackages));
});
