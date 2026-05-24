<?php

/**
 * Safely reads the application's composer.lock.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Diagnostics\Support
 *
 * @author     Jacob Martella <me@jacobmartella.com>
 *
 * @since      1.2.0
 */

namespace ArtisanPackUI\Core\Diagnostics\Support;

/**
 * Defensive accessor for `composer.lock` that powers every diagnostic and
 * update / compatibility checker.
 *
 * The application's lockfile may be absent ( fresh checkout ), unreadable
 * ( permission issues ), or malformed ( interrupted composer run ); this
 * helper short-circuits each failure mode and returns an empty list so that
 * callers can rely on a stable shape.
 *
 * @package    ArtisanPack_UI
 * @subpackage Core\Diagnostics\Support
 *
 * @since      1.2.0
 */
class ComposerLock
{
    /**
     * Return the merged production + dev package list from composer.lock,
     * or an empty array when the file is missing, unreadable, or malformed.
     *
     * @since 1.2.0
     *
     * @return array<int, array<string, mixed>> The flattened package metadata list.
     */
    public static function packages(): array
    {
        $lockPath = base_path( 'composer.lock' );

        if ( ! file_exists( $lockPath ) ) {
            return [];
        }

        $contents = @file_get_contents( $lockPath );

        if ( false === $contents ) {
            return [];
        }

        $lock = json_decode( $contents, true );

        if ( ! is_array( $lock ) ) {
            return [];
        }

        $production = is_array( $lock['packages'] ?? null ) ? $lock['packages'] : [];
        $dev        = is_array( $lock['packages-dev'] ?? null ) ? $lock['packages-dev'] : [];

        return array_merge( $production, $dev );
    }
}
