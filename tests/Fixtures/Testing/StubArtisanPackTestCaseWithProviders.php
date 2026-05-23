<?php

namespace Tests\Fixtures\Testing;

use Tests\Fixtures\TestPackageServiceProvider;

class StubArtisanPackTestCaseWithProviders extends StubArtisanPackTestCase
{
	protected function getAdditionalProviders(): array
	{
		return [
			TestPackageServiceProvider::class,
		];
	}
}
