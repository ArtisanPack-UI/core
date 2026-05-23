<?php

/**
 * Lightweight service double used to exercise ArtisanPackFacade resolution.
 *
 * @package Tests\Fixtures
 */

namespace Tests\Fixtures;

/**
 * Stub service bound into the container by ArtisanPackFacade tests.
 *
 * @package Tests\Fixtures
 */
class TestFacadeService
{
	/**
	 * Return a deterministic value used by tests to confirm the facade
	 * forwarded a method call to the bound service.
	 *
	 * @return string The literal string "pong".
	 */
	public function ping(): string
	{
		return 'pong';
	}
}
