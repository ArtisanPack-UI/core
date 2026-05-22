<?php

namespace Tests\Fixtures;

use ArtisanPackUI\Core\ArtisanPackServiceProvider;

class MinimalServiceProvider extends ArtisanPackServiceProvider
{
	public bool $bindingsRegistered = false;

	protected function registerPackageBindings(): void
	{
		$this->bindingsRegistered = true;
	}
}
