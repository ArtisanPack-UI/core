<?php

namespace Tests\Fixtures;

use ArtisanPackUI\Core\ArtisanPackServiceProvider;

class TestPackageServiceProvider extends ArtisanPackServiceProvider
{
	public bool $bindingsRegistered = false;

	public bool $packageBooted = false;

	protected ?string $configFile = __DIR__ . '/test-config.php';

	protected ?string $configKey = 'artisanpack-test';

	protected ?string $viewsNamespace = 'artisanpack-test';

	protected ?string $viewsPath = __DIR__ . '/views';

	protected ?string $routesPath = __DIR__ . '/routes.php';

	protected array $commands = [ TestCommand::class ];

	protected array $singletons = [
		'artisanpack-test-binding' => TestService::class,
		TestService::class,
	];

	protected function registerPackageBindings(): void
	{
		$this->bindingsRegistered = true;
	}

	protected function bootPackage(): void
	{
		$this->packageBooted = true;
	}
}
