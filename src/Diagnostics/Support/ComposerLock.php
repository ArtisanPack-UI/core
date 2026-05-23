<?php

/**
 * Safely reads the application's composer.lock.
 *
 * @since 1.2.0
 */

namespace ArtisanPackUI\Core\Diagnostics\Support;

class ComposerLock
{
    /**
     * Return the merged production + dev package list from composer.lock,
     * or an empty array when the file is missing, unreadable, or malformed.
     *
     * @return array<int, array<string, mixed>>
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
