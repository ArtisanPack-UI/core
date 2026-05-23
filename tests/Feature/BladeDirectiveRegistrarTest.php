<?php

// Several assertions embed `$variable` substrings inside expectation
// strings to exercise the literal PHP that the registrar produces; those
// strings are intentionally single-quoted, so silence the sniff that wants
// them re-quoted as interpolated double-quoted strings.
// phpcs:disable ArtisanPackUI.Strings.Quotes.SingleQuotesWithVariable

use ArtisanPackUI\Core\View\BladeDirectiveRegistrar;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Compilers\BladeCompiler;

beforeEach( function (): void {
	$this->registrar = app( BladeDirectiveRegistrar::class );
} );

test( 'the registrar is registered as a singleton', function (): void {
	expect( app( BladeDirectiveRegistrar::class ) )
		->toBe( app( BladeDirectiveRegistrar::class ) );
} );

test( 'the artisanpack_blade helper resolves the registrar from the container', function (): void {
	expect( artisanpack_blade() )
		->toBeInstanceOf( BladeDirectiveRegistrar::class )
		->toBe( app( BladeDirectiveRegistrar::class ) );
} );

test( 'echo registers a directive that echoes the function result', function (): void {
	$this->registrar->echo( 'apEcho', 'strtoupper' );

	$directives = Blade::getCustomDirectives();

	expect( $directives )->toHaveKey( 'apEcho' );
	expect( $directives['apEcho']( "'hello'" ) )
		->toBe( "<?php echo strtoupper('hello'); ?>" );
} );

test( 'escaped registers a directive that echoes the escaped function result', function (): void {
	$this->registrar->escaped( 'apEscaped', 'strtoupper' );

	$directives = Blade::getCustomDirectives();

	expect( $directives )->toHaveKey( 'apEscaped' );
	expect( $directives['apEscaped']( '$value' ) )
		->toBe( '<?php echo e(strtoupper($value)); ?>' );
} );

test( 'conditional registers paired directives backed by the callable', function (): void {
	$this->registrar->conditional( 'apIf', 'is_string' );

	$directives = Blade::getCustomDirectives();

	expect( $directives )->toHaveKey( 'apIf' );
	expect( $directives )->toHaveKey( 'endapIf' );

	expect( $directives['apIf']( '$value' ) )
		->toBe( '<?php if (is_string($value)): ?>' );
	expect( $directives['endapIf']( null ) )
		->toBe( '<?php endif; ?>' );
} );

test( 'wrap registers paired directives that substitute the expression placeholder', function (): void {
	$this->registrar->wrap(
		'apCard',
		'<div class="card" data-title="{expression}">',
		'</div>',
	);

	$directives = Blade::getCustomDirectives();

	expect( $directives )->toHaveKey( 'apCard' );
	expect( $directives )->toHaveKey( 'endapCard' );

	expect( $directives['apCard']( "'title'" ) )
		->toBe( '<div class="card" data-title="<?php echo \'title\'; ?>">' );
	expect( $directives['endapCard']( null ) )
		->toBe( '</div>' );
} );

test( 'wrap drops the expression placeholder when no expression is supplied', function (): void {
	$this->registrar->wrap(
		'apBox',
		'<div data-x="{expression}">',
		'</div>',
	);

	$directives = Blade::getCustomDirectives();

	expect( $directives['apBox']( null ) )->toBe( '<div data-x="">' );
	expect( $directives['apBox']( '' ) )->toBe( '<div data-x="">' );
} );

test( 'wrap leaves markup untouched when no placeholder is supplied', function (): void {
	$this->registrar->wrap( 'apStatic', '<section>', '</section>' );

	$directives = Blade::getCustomDirectives();

	expect( $directives['apStatic']( "'ignored'" ) )->toBe( '<section>' );
	expect( $directives['endapStatic']( null ) )->toBe( '</section>' );
} );

test( 'livewireAction registers a directive that calls the action on the component', function (): void {
	$this->registrar->livewireAction( 'apLwAction', 'save' );

	$directives = Blade::getCustomDirectives();

	expect( $directives )->toHaveKey( 'apLwAction' );
	expect( $directives['apLwAction']( '$id' ) )
		->toBe( '<?php $this->save($id); ?>' );
} );

test( 'facade registers a directive that calls a static facade method', function (): void {
	$this->registrar->facade( 'apMedia', 'App\\Facades\\Media', 'find' );

	$directives = Blade::getCustomDirectives();

	expect( $directives )->toHaveKey( 'apMedia' );
	expect( $directives['apMedia']( '$id' ) )
		->toBe( '<?php echo App\\Facades\\Media::find($id); ?>' );
} );

test( 'directives are dispatched into the underlying BladeCompiler', function (): void {
	$compiler = app( BladeCompiler::class );
	$before   = array_keys( $compiler->getCustomDirectives() );

	$this->registrar->echo( 'apCompiler', 'strtoupper' );

	$after = array_keys( $compiler->getCustomDirectives() );

	expect( array_diff( $after, $before ) )->toContain( 'apCompiler' );
} );

test( 'the registrar throws for an empty directive name', function (): void {
	$this->registrar->echo( '', 'strtoupper' );
} )->throws( InvalidArgumentException::class );

test( 'the registrar throws for a directive name starting with a digit', function (): void {
	$this->registrar->echo( '1bad', 'strtoupper' );
} )->throws( InvalidArgumentException::class );

test( 'the registrar throws for a directive name with illegal characters', function (): void {
	$this->registrar->echo( 'bad-name', 'strtoupper' );
} )->throws( InvalidArgumentException::class );

test( 'escaped rejects invalid directive names', function (): void {
	$this->registrar->escaped( 'bad-name', 'strtoupper' );
} )->throws( InvalidArgumentException::class );

test( 'conditional rejects invalid directive names', function (): void {
	$this->registrar->conditional( 'bad-name', 'is_string' );
} )->throws( InvalidArgumentException::class );

test( 'wrap rejects invalid directive names', function (): void {
	$this->registrar->wrap( 'bad-name', '<div>', '</div>' );
} )->throws( InvalidArgumentException::class );

test( 'livewireAction rejects invalid directive names', function (): void {
	$this->registrar->livewireAction( 'bad-name', 'save' );
} )->throws( InvalidArgumentException::class );

test( 'facade rejects invalid directive names', function (): void {
	$this->registrar->facade( 'bad-name', 'Media', 'find' );
} )->throws( InvalidArgumentException::class );

test( 'a registered echo directive renders inside a real Blade template', function (): void {
	$this->registrar->echo( 'apReverse', 'strrev' );

	$compiled = Blade::compileString( "@apReverse('hello')" );

	expect( $compiled )->toBe( "<?php echo strrev('hello'); ?>" );
} );

test( 'a registered escaped directive renders inside a real Blade template', function (): void {
	$this->registrar->escaped( 'apEsc', 'strtoupper' );

	$compiled = Blade::compileString( "@apEsc('<b>bold</b>')" );

	expect( $compiled )->toBe( "<?php echo e(strtoupper('<b>bold</b>')); ?>" );
} );

test( 'a registered conditional renders inside a real Blade template', function (): void {
	$this->registrar->conditional( 'apIs', 'is_string' );

	$compiled = Blade::compileString( "@apIs('value') yes @endapIs" );

	expect( $compiled )->toBe( "<?php if (is_string('value')): ?> yes <?php endif; ?>" );
} );
