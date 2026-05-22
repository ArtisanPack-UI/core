<?php

use Illuminate\Support\Facades\Route;

Route::get( 'artisanpack-test-route', static fn (): string => 'ok' )->name( 'artisanpack.test-route' );
