<?php

/**
 * Base facade class shared across the ArtisanPack UI ecosystem.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Facades
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Facades;

use ArtisanPackUI\Core\Exceptions\ServiceException;
use Illuminate\Support\Facades\Facade;

/**
 * Provides a shared base class for every ArtisanPack UI facade so that
 * packages get consistent, descriptive error messages when their underlying
 * service is missing, alongside helper methods for safely probing whether
 * the service is available before resolving it.
 *
 * Subclasses are expected to override {@see static::getFacadeAccessor()} to
 * return their container binding key. They may also override
 * {@see static::getPackageName()} so that the exception thrown when the
 * service is missing names the package the consumer needs to install.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Facades
 *
 * @since      1.2.0
 */
abstract class ArtisanPackFacade extends Facade
{
	/**
	 * Determine whether the facade's underlying service is bound and resolvable.
	 *
	 * @since 1.2.0
	 *
	 * @return bool True when the application container has a binding for the accessor.
	 */
	public static function isAvailable(): bool
	{
		return null !== static::$app
			&& static::$app->bound( static::getFacadeAccessor() );
	}

	/**
	 * Attempt to resolve the facade root, returning null when unavailable.
	 *
	 * Unlike {@see static::getFacadeRoot()}, this method does not throw when
	 * the underlying service is missing, allowing callers to perform optional
	 * lookups against packages that may or may not be installed.
	 *
	 * @since 1.2.0
	 *
	 * @return object|null The resolved service instance, or null when unavailable.
	 */
	public static function tryGet(): ?object
	{
		if ( ! static::isAvailable() ) {
			return null;
		}

		$instance = static::getFacadeRoot();

		return is_object( $instance ) ? $instance : null;
	}

	/**
	 * Get the composer package name that provides this facade's service.
	 *
	 * Subclasses should override this method to surface their own package
	 * name in the exception message thrown when the service is not bound,
	 * making it clear which composer package needs to be installed.
	 *
	 * @since 1.2.0
	 *
	 * @return string The composer package name (e.g., "artisanpack-ui/core").
	 */
	protected static function getPackageName(): string
	{
		return 'artisanpack-ui/core';
	}

	/**
	 * Resolve the facade root instance from the container.
	 *
	 * Overrides the default Laravel behaviour so that a clear
	 * {@see ServiceException} is raised when the underlying service is not
	 * bound, replacing the generic "Target class does not exist" error with
	 * a message that names the missing package.
	 *
	 * @since 1.2.0
	 *
	 * @param  mixed  $name  The container binding key.
	 *
	 * @throws ServiceException When the application container is unavailable or
	 *                          has no binding for the requested service.
	 *
	 * @return mixed The resolved service instance.
	 */
	protected static function resolveFacadeInstance( mixed $name ): mixed
	{
		if ( isset( static::$resolvedInstance[ $name ] ) ) {
			return static::$resolvedInstance[ $name ];
		}

		if ( null === static::$app || ! static::$app->bound( $name ) ) {
			throw new ServiceException(
				sprintf(
					'Service [%s] is not bound in the container. ' .
					'Make sure the %s package is properly installed and configured.',
					$name,
					static::getPackageName(),
				),
				0,
				null,
				'SERVICE_NOT_BOUND',
			);
		}

		return parent::resolveFacadeInstance( $name );
	}
}
