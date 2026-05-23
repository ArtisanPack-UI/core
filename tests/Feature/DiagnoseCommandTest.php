<?php

use ArtisanPackUI\Core\Commands\DiagnoseCommand;
use ArtisanPackUI\Core\Diagnostics\Checks\DiagnosticCheck;
use ArtisanPackUI\Core\Diagnostics\DiagnosticResult;
use ArtisanPackUI\Core\Diagnostics\DiagnosticRunner;
use ArtisanPackUI\Core\Diagnostics\DiagnosticStatus;
use Illuminate\Support\Facades\Artisan;

function fakeCheck(
    string $id,
    string $name,
    DiagnosticStatus $status = DiagnosticStatus::Pass,
    bool $supportsFix = false,
    bool $fixReturns = true,
): DiagnosticCheck {
    return new class( $id, $name, $status, $supportsFix, $fixReturns ) implements DiagnosticCheck {
        public function __construct(
            private string $id,
            private string $name,
            private DiagnosticStatus $status,
            private bool $supportsFix,
            private bool $fixReturns,
            public int $fixCallCount = 0,
        ) {
        }

        public function id(): string
        {
            return $this->id;
        }

        public function name(): string
        {
            return $this->name;
        }

        public function run(): array
        {
            return [
                new DiagnosticResult( label: "{$this->name} check", status: $this->status ),
            ];
        }

        public function supportsFix(): bool
        {
            return $this->supportsFix;
        }

        public function fix( callable $writer ): bool
        {
            $this->fixCallCount++;
            $writer( "fixing {$this->id}" );

            return $this->fixReturns;
        }
    };
}

beforeEach( function (): void {
    $this->runner = new DiagnosticRunner( $this->app );
    $this->app->instance( DiagnosticRunner::class, $this->runner );
} );

test( 'command is registered', function (): void {
    $commands = Artisan::all();

    expect( $commands )->toHaveKey( 'artisanpack:diagnose' );
    expect( $commands['artisanpack:diagnose'] )->toBeInstanceOf( DiagnoseCommand::class );
} );

test( 'command exposes documented options', function (): void {
    $definition = (new DiagnoseCommand)->getDefinition();

    expect( $definition->hasOption( 'json' ) )->toBeTrue();
    expect( $definition->hasOption( 'fix' ) )->toBeTrue();
    expect( $definition->hasOption( 'package' ) )->toBeTrue();
} );

test( 'report renders sections and summary with passing checks', function (): void {
    $this->runner->setChecks( [
        fakeCheck( 'one', 'Section One' ),
        fakeCheck( 'two', 'Section Two' ),
    ] );

    $this->artisan( 'artisanpack:diagnose' )
        ->expectsOutputToContain( 'ArtisanPack UI Diagnostic Report' )
        ->expectsOutputToContain( 'Section One:' )
        ->expectsOutputToContain( 'Section Two:' )
        ->expectsOutputToContain( '2 checks passed' )
        ->assertExitCode( 0 );
} );

test( 'command exits with failure when an error result is produced', function (): void {
    $this->runner->setChecks( [
        fakeCheck( 'broken', 'Broken', DiagnosticStatus::Error ),
    ] );

    $this->artisan( 'artisanpack:diagnose' )
        ->expectsOutputToContain( '1 errors' )
        ->assertExitCode( 1 );
} );

test( 'json output emits structured report', function (): void {
    $this->runner->setChecks( [
        fakeCheck( 'env', 'Environment' ),
    ] );

    $exitCode = Artisan::call( 'artisanpack:diagnose', ['--json' => true] );
    $output   = Artisan::output();

    expect( $exitCode )->toBe( 0 );

    $decoded = json_decode( trim( $output ), true );

    expect( $decoded )->toBeArray();
    expect( $decoded )->toHaveKeys( ['sections', 'summary'] );
    expect( $decoded['summary']['passed'] )->toBe( 1 );
    expect( $decoded['sections'] )->toHaveKey( 'env' );
} );

test( 'package option restricts to a single check', function (): void {
    $this->runner->setChecks( [
        fakeCheck( 'one', 'Section One' ),
        fakeCheck( 'two', 'Section Two' ),
    ] );

    $this->artisan( 'artisanpack:diagnose', ['--package' => 'two'] )
        ->expectsOutputToContain( 'Section Two:' )
        ->doesntExpectOutputToContain( 'Section One:' )
        ->assertExitCode( 0 );
} );

test( 'fix option invokes fixable checks', function (): void {
    $fixable    = fakeCheck( 'fixme', 'Fixable', DiagnosticStatus::Warning, supportsFix: true );
    $notFixable = fakeCheck( 'static', 'Read Only' );

    $this->runner->setChecks( [$fixable, $notFixable] );

    $this->artisan( 'artisanpack:diagnose', ['--fix' => true] )
        ->expectsOutputToContain( 'Attempting to fix: Fixable' )
        ->expectsOutputToContain( 'fixing fixme' )
        ->doesntExpectOutputToContain( 'Attempting to fix: Read Only' )
        ->assertExitCode( 0 );

    expect( $fixable->fixCallCount )->toBe( 1 );
} );

test( 'unknown package option fails fast with explicit error', function (): void {
    $this->runner->setChecks( [
        fakeCheck( 'one', 'Section One' ),
    ] );

    $this->artisan( 'artisanpack:diagnose', ['--package' => 'nonexistent'] )
        ->expectsOutputToContain( "Unknown package check id: 'nonexistent'" )
        ->doesntExpectOutputToContain( 'Section One:' )
        ->assertExitCode( 1 );
} );

test( 'fix failure causes command to exit non-zero', function (): void {
    $failingFixable = fakeCheck( 'broken', 'Broken Fix', DiagnosticStatus::Warning, supportsFix: true, fixReturns: false );

    $this->runner->setChecks( [$failingFixable] );

    $this->artisan( 'artisanpack:diagnose', ['--fix' => true] )
        ->expectsOutputToContain( 'Attempting to fix: Broken Fix' )
        ->expectsOutputToContain( 'Fix failed for: Broken Fix' )
        ->assertExitCode( 1 );

    expect( $failingFixable->fixCallCount )->toBe( 1 );
} );

test( 'fix option with package restricts fixes', function (): void {
    $fixableA = fakeCheck( 'a', 'A', DiagnosticStatus::Warning, supportsFix: true );
    $fixableB = fakeCheck( 'b', 'B', DiagnosticStatus::Warning, supportsFix: true );

    $this->runner->setChecks( [$fixableA, $fixableB] );

    $this->artisan( 'artisanpack:diagnose', ['--fix' => true, '--package' => 'b'] )
        ->expectsOutputToContain( 'Attempting to fix: B' )
        ->doesntExpectOutputToContain( 'Attempting to fix: A' )
        ->assertExitCode( 0 );

    expect( $fixableA->fixCallCount )->toBe( 0 );
    expect( $fixableB->fixCallCount )->toBe( 1 );
} );

test( 'verbose flag prints result details', function (): void {
    $detailed = new class implements DiagnosticCheck {
        public function id(): string
        {
            return 'env';
        }

        public function name(): string
        {
            return 'Environment';
        }

        public function run(): array
        {
            return [
                new DiagnosticResult(
                    label: 'PHP Version',
                    status: DiagnosticStatus::Pass,
                    details: ['current' => '8.4.3', 'minimum' => '8.2.0'],
                ),
            ];
        }

        public function supportsFix(): bool
        {
            return false;
        }

        public function fix( callable $writer): bool
        {
            return false;
        }
    };

    $this->runner->setChecks( [$detailed]);

    $this->artisan( 'artisanpack:diagnose', ['--verbose' => true])
        ->expectsOutputToContain( 'current: 8.4.3')
        ->expectsOutputToContain( 'minimum: 8.2.0')
        ->assertExitCode( 0);
});
