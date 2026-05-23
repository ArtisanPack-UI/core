<?php

use ArtisanPackUI\Core\Diagnostics\Checks\ArtisanCommandsCheck;
use ArtisanPackUI\Core\Diagnostics\Checks\ConfigurationCheck;
use ArtisanPackUI\Core\Diagnostics\Checks\DiagnosticCheck;
use ArtisanPackUI\Core\Diagnostics\Checks\EnvironmentCheck;
use ArtisanPackUI\Core\Diagnostics\Checks\InstalledPackagesCheck;
use ArtisanPackUI\Core\Diagnostics\Checks\ServiceBindingsCheck;
use ArtisanPackUI\Core\Diagnostics\DiagnosticResult;
use ArtisanPackUI\Core\Diagnostics\DiagnosticRunner;
use ArtisanPackUI\Core\Diagnostics\DiagnosticStatus;

test( 'runner registers default checks', function (): void {
    $runner = new DiagnosticRunner( app() );

    $ids = array_map( fn ( DiagnosticCheck $c ) => $c->id(), $runner->checks() );

    expect( $ids )->toContain( 'environment' );
    expect( $ids )->toContain( 'packages' );
    expect( $ids )->toContain( 'configuration' );
    expect( $ids )->toContain( 'service-bindings' );
    expect( $ids )->toContain( 'commands' );
} );

test( 'default checks are the expected concrete classes', function (): void {
    $checks = (new DiagnosticRunner( app() ))->checks();

    expect( $checks[0] )->toBeInstanceOf( EnvironmentCheck::class );
    expect( $checks[1] )->toBeInstanceOf( InstalledPackagesCheck::class );
    expect( $checks[2] )->toBeInstanceOf( ConfigurationCheck::class );
    expect( $checks[3] )->toBeInstanceOf( ServiceBindingsCheck::class );
    expect( $checks[4] )->toBeInstanceOf( ArtisanCommandsCheck::class );
} );

test( 'report aggregates pass/warn/error counts', function (): void {
    $runner = new DiagnosticRunner( app() );

    $runner->setChecks( [
        new class implements DiagnosticCheck {
            public function id(): string
            {
                return 'one';
            }

            public function name(): string
            {
                return 'One';
            }

            public function run(): array
            {
                return [
                    new DiagnosticResult( label: 'a', status: DiagnosticStatus::Pass ),
                    new DiagnosticResult( label: 'b', status: DiagnosticStatus::Warning ),
                    new DiagnosticResult( label: 'c', status: DiagnosticStatus::Error ),
                ];
            }

            public function supportsFix(): bool
            {
                return false;
            }

            public function fix( callable $writer ): bool
            {
                return false;
            }
        },
    ] );

    $report = $runner->run();

    expect( $report->passedCount() )->toBe( 1 );
    expect( $report->warningCount() )->toBe( 1 );
    expect( $report->errorCount() )->toBe( 1 );
    expect( $report->hasErrors() )->toBeTrue();
} );

test( 'run filters by check id', function (): void {
    $runner = new DiagnosticRunner( app() );

    $report = $runner->run( 'environment' );

    expect( $report->sections )->toHaveCount( 1 );
    expect( $report->sections[0]['check']->id() )->toBe( 'environment' );
} );

test( 'environment check verifies the running php version', function (): void {
    $check   = new EnvironmentCheck( app() );
    $results = $check->run();

    expect( $results )->not->toBeEmpty();
    expect( $results[0]->label )->toContain( PHP_VERSION );
    expect( $results[0]->status )->toBe( DiagnosticStatus::Pass );
} );

test( 'installed packages check identifies the core package', function (): void {
    $results = (new InstalledPackagesCheck)->run();

    $coreResult = collect( $results )->first( fn ( DiagnosticResult $r ) => str_contains( $r->label, 'artisanpack-ui/core' ) );

    expect( $coreResult )->not->toBeNull();
} );

test( 'artisan commands check finds registered diagnose command', function (): void {
    $results = (new ArtisanCommandsCheck( app() ))->run();

    $diagnoseResult = collect( $results )->first(
        fn ( DiagnosticResult $r ) => 'artisanpack:diagnose' === $r->label,
    );

    expect( $diagnoseResult )->not->toBeNull();
    expect( $diagnoseResult->status )->toBe( DiagnosticStatus::Pass );
} );

test( 'fixable checks filter excludes static checks', function (): void {
    $runner = new DiagnosticRunner( app());

    $fixable = $runner->fixableChecks();

    foreach ( $fixable as $check) {
        expect( $check->supportsFix())->toBeTrue();
    }
});
