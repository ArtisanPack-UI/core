<?php

/**
 * Helper for registering common Blade directive patterns.
 *
 * @since      1.2.0
 *
 * @package    ArtisanPackUI\Core\View
 */

namespace ArtisanPackUI\Core\View;

// PHP requires the lowercase __construct magic method; the
// MagicMethodNotUppercase sniff misfires on the constructor here.
// phpcs:disable ArtisanPackUI.TypeHints.TypeDeclaration.MagicMethodNotUppercase

// Closures register the compiled PHP for each directive and necessarily
// reference the surrounding values via `use ( ... )`; the ClassStructure
// sniff mistakes those closure imports for trait use statements.
// phpcs:disable ArtisanPackUI.Classes.ClassStructure.TraitUseNotAtTop

// The compiled PHP strings emitted here are intentionally raw because the
// directive author—not this registrar—controls whether to escape the value
// (see the dedicated escaped() method for the escaped variant).
// phpcs:disable ArtisanPackUI.Security.EscapeOutput.OutputNotEscaped

use Illuminate\View\Compilers\BladeCompiler;
use InvalidArgumentException;

/**
 * Registrar that simplifies the boilerplate involved in adding Blade directives.
 *
 * ArtisanPack UI packages frequently expose their helper functions through
 * matching Blade directives, which typically requires hand-rolling PHP-string
 * templates. The registrar replaces those error-prone strings with
 * intent-revealing methods like {@see echo()}, {@see escaped()},
 * {@see conditional()}, {@see wrap()}, {@see livewireAction()}, and
 * {@see facade()} so packages can declare common patterns in a single line.
 *
 * The registrar is resolved through the container by
 * {@see \ArtisanPackUI\Core\CoreServiceProvider} and can be injected directly,
 * resolved via `app()`, or obtained through the {@see artisanpack_blade()}
 * helper.
 *
 * @since 1.2.0
 *
 * @package ArtisanPackUI\Core\View
 */
class BladeDirectiveRegistrar
{
	/**
	 * Regex that matches every valid Blade directive identifier.
	 *
	 * Directive names are reused verbatim inside generated PHP method names and
	 * are exposed in templates as `@name`. They must therefore start with a
	 * letter or underscore and only contain word characters.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	protected const DIRECTIVE_NAME_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*$/';

	/**
	 * Placeholder substituted into the opening tag of a {@see wrap()} directive.
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	protected const WRAP_EXPRESSION_PLACEHOLDER = '{expression}';

	/**
	 * Create a new Blade directive registrar.
	 *
	 * @since 1.2.0
	 *
	 * @param  BladeCompiler  $compiler  The Blade compiler used to register directives.
	 */
	public function __construct( protected BladeCompiler $compiler )
	{
	}

	/**
	 * Register a directive that echoes the return value of a function.
	 *
	 * Useful for surfacing global helper functions to Blade templates without
	 * hand-crafting a PHP-string template. Given the function `myFunction()`
	 * and the directive name `myDirective`, `@myDirective( $value )` compiles
	 * to `<?php echo myFunction( $value ); ?>`.
	 *
	 * @since 1.2.0
	 *
	 * @param  string  $name      The directive name without the leading `@`.
	 * @param  string  $function  The fully-qualified function to invoke at render time.
	 *
	 * @return void
	 */
	public function echo( string $name, string $function ): void
	{
		$this->validateName( $name );

		$this->compiler->directive( $name, function ( ?string $expression ) use ( $function ): string {
			return "<?php echo {$function}({$expression}); ?>";
		} );
	}

	/**
	 * Register a directive that echoes the escaped return value of a function.
	 *
	 * Mirrors {@see echo()} but wraps the value with Laravel's `e()` helper so
	 * the output is HTML-escaped. Useful for surfacing helper functions that
	 * might return user-supplied content.
	 *
	 * @since 1.2.0
	 *
	 * @param  string  $name      The directive name without the leading `@`.
	 * @param  string  $function  The fully-qualified function to invoke at render time.
	 *
	 * @return void
	 */
	public function escaped( string $name, string $function ): void
	{
		$this->validateName( $name );

		$this->compiler->directive( $name, function ( ?string $expression ) use ( $function ): string {
			return "<?php echo e({$function}({$expression})); ?>";
		} );
	}

	/**
	 * Register a paired conditional directive backed by a callable check.
	 *
	 * Generates both `@name(...)` and `@endname` directives. The opening
	 * directive expands to `<?php if ( condition( $expression ) ): ?>` and the
	 * closing directive expands to `<?php endif; ?>`, enabling conditional
	 * blocks like `@hasRole( 'admin' ) ... @endhasRole`.
	 *
	 * @since 1.2.0
	 *
	 * @param  string  $name       The directive name without the leading `@`.
	 * @param  string  $condition  The fully-qualified callable to evaluate at render time.
	 *
	 * @return void
	 */
	public function conditional( string $name, string $condition ): void
	{
		$this->validateName( $name );

		$this->compiler->directive( $name, function ( ?string $expression ) use ( $condition ): string {
			return "<?php if ({$condition}({$expression})): ?>";
		} );

		$this->compiler->directive( 'end' . $name, function (): string {
			return '<?php endif; ?>';
		} );
	}

	/**
	 * Register a paired directive that wraps its contents in arbitrary markup.
	 *
	 * Generates both `@name(...)` and `@endname` directives. The opening tag may
	 * include the literal `{expression}` placeholder, which is replaced with the
	 * echoed value of the supplied expression at render time. The closing tag is
	 * emitted verbatim.
	 *
	 * @since 1.2.0
	 *
	 * @param  string  $name      The directive name without the leading `@`.
	 * @param  string  $openTag   The HTML emitted by the opening directive, optionally containing `{expression}`.
	 * @param  string  $closeTag  The HTML emitted by the closing directive.
	 *
	 * @return void
	 */
	public function wrap( string $name, string $openTag, string $closeTag ): void
	{
		$this->validateName( $name );

		$this->compiler->directive( $name, function ( ?string $expression ) use ( $openTag ): string {
			$replacement = null === $expression || '' === $expression
				? ''
				: "<?php echo {$expression}; ?>";

			return str_replace( self::WRAP_EXPRESSION_PLACEHOLDER, $replacement, $openTag );
		} );

		$this->compiler->directive( 'end' . $name, function () use ( $closeTag ): string {
			return $closeTag;
		} );
	}

	/**
	 * Register a directive that invokes a method on the current Livewire component.
	 *
	 * The generated PHP calls `$this->action( $expression )`, making it suitable
	 * for declaring shorthand directives that fire Livewire actions from inside
	 * a component's view. The action is invoked for its side effects; its return
	 * value is discarded.
	 *
	 * @since 1.2.0
	 *
	 * @param  string  $name    The directive name without the leading `@`.
	 * @param  string  $action  The Livewire component method to invoke at render time.
	 *
	 * @return void
	 */
	public function livewireAction( string $name, string $action ): void
	{
		$this->validateName( $name );

		$this->compiler->directive( $name, function ( ?string $expression ) use ( $action ): string {
			return "<?php \$this->{$action}({$expression}); ?>";
		} );
	}

	/**
	 * Register a directive that echoes the return value of a static facade method.
	 *
	 * Useful for surfacing facade-backed helpers without exposing the facade
	 * import inside templates. Given the facade `App\Facades\Media` and method
	 * `find`, the generated PHP is `<?php echo App\Facades\Media::find( $expression ); ?>`.
	 *
	 * @since 1.2.0
	 *
	 * @param  string  $name         The directive name without the leading `@`.
	 * @param  string  $facadeClass  The fully-qualified facade class to call.
	 * @param  string  $method       The static method to invoke on the facade.
	 *
	 * @return void
	 */
	public function facade( string $name, string $facadeClass, string $method ): void
	{
		$this->validateName( $name );

		$this->compiler->directive( $name, function ( ?string $expression ) use ( $facadeClass, $method ): string {
			return "<?php echo {$facadeClass}::{$method}({$expression}); ?>";
		} );
	}

	/**
	 * Ensure the supplied directive name is a valid Blade identifier.
	 *
	 * Blade derives the compiled directive method name from this value, so any
	 * character outside `[A-Za-z0-9_]`—or a leading digit—would either fail to
	 * compile or be silently ignored. Rejecting invalid names early surfaces
	 * the bug at registration rather than at template render.
	 *
	 * @since 1.2.0
	 *
	 * @param  string  $name  The directive name to validate.
	 *
	 * @throws InvalidArgumentException When the name is empty or contains illegal characters.
	 *
	 * @return void
	 */
	protected function validateName( string $name ): void
	{
		if ( '' === $name || 1 !== preg_match( self::DIRECTIVE_NAME_PATTERN, $name ) ) {
			throw new InvalidArgumentException(
				sprintf(
					'The Blade directive name "%s" is invalid. Names must start with a letter or'
						. ' underscore and contain only word characters.',
					$name,
				),
			);
		}
	}
}
