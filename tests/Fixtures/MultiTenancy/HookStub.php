<?php

namespace Tests\Fixtures\MultiTenancy;

/**
 * Stands in for artisanpack-ui/hooks in the test suite.
 *
 * Core does not depend on the hooks package, so the tests supply their own
 * `applyFilters()` and record the answers here.
 */
class HookStub
{
	/**
	 * The value each hook answers with, keyed by hook name.
	 *
	 * @var array<string, mixed>
	 */
	public static array $answers = [];

	/**
	 * Records the value a hook should answer with.
	 *
	 * @param  string  $hook  The hook name.
	 * @param  mixed  $value  The value the hook answers with.
	 *
	 * @return void
	 */
	public static function answer( string $hook, mixed $value ): void
	{
		self::$answers[ $hook ] = $value;
	}

	/**
	 * Clears every recorded answer.
	 *
	 * @return void
	 */
	public static function reset(): void
	{
		self::$answers = [];
	}

	/**
	 * Applies a hook, falling back to the supplied default.
	 *
	 * @param  string  $hook  The hook name.
	 * @param  mixed  $default  The value returned when nothing answers.
	 *
	 * @return mixed The recorded answer, or the default.
	 */
	public static function apply( string $hook, mixed $default ): mixed
	{
		return array_key_exists( $hook, self::$answers ) ? self::$answers[ $hook ] : $default;
	}
}
