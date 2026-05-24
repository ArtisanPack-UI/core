<?php

use Illuminate\Foundation\Application;

test( 'artisanpack_supports_laravel_13 returns a boolean', function (): void {
    expect( artisanpack_supports_laravel_13() )->toBeBool();
} );

test( 'artisanpack_supports_laravel_13 reflects the current Laravel version', function (): void {
    $expected = version_compare( Application::VERSION, '13.0.0', '>=' );

    expect( artisanpack_supports_laravel_13() )->toBe( $expected );
} );
