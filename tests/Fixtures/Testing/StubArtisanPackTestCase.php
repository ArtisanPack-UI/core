<?php

namespace Tests\Fixtures\Testing;

use ArtisanPackUI\Core\Testing\ArtisanPackTestCase;
use Illuminate\Foundation\Application;

class StubArtisanPackTestCase extends ArtisanPackTestCase
{
	public function callGetArtisanPackProviders(): array
	{
		return $this->getArtisanPackProviders();
	}

	public function callGetAdditionalProviders(): array
	{
		return $this->getAdditionalProviders();
	}

	public function callGetPackageProviders( Application $app ): array
	{
		return $this->getPackageProviders( $app );
	}
}
