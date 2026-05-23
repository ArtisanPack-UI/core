<?php

namespace Tests\Fixtures\Testing;

class StubArtisanPackTestCaseWithProviders extends StubArtisanPackTestCase
{
	protected function getAdditionalProviders(): array
	{
		return [
			'Tests\\Fixtures\\TestPackageServiceProvider',
		];
	}
}
