<?php
/**
 * Class autoloading.
 *
 * @package BooksList
 */

declare( strict_types = 1 );

namespace BooksList;

defined( 'ABSPATH' ) || exit;

/**
 * Minimal PSR-4 autoloader, scoped to the plugin namespace.
 *
 * The plugin has no runtime dependency: requiring `composer install` just
 * for autoloading would break a plugin copied into wp-content/plugins/.
 */
final class Autoloader {

	/**
	 * Registers the autoloader on the SPL stack.
	 *
	 * @return void
	 */
	public static function register(): void {
		spl_autoload_register( array( self::class, 'load' ) );
	}

	/**
	 * Maps a fully qualified class name to a file in this directory.
	 *
	 * @param string $class_name Fully qualified name of the requested class.
	 * @return void
	 */
	private static function load( string $class_name ): void {
		$prefix = __NAMESPACE__ . '\\';

		if ( ! str_starts_with( $class_name, $prefix ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( $prefix ) );

		// Rules out directory traversal in the resolved path.
		if ( ! preg_match( '/^[A-Za-z0-9_\\\\]+$/', $relative ) ) {
			return;
		}

		$path = __DIR__ . '/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
}
