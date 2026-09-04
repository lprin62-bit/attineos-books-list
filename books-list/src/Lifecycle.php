<?php
/**
 * Plugin activation.
 *
 * @package BooksList
 */

declare( strict_types = 1 );

namespace BooksList;

defined( 'ABSPATH' ) || exit;

/**
 * Runs on activation.
 *
 * Nothing runs on deactivation: transients expire on their own and the
 * options are kept, so re-enabling the plugin restores its settings.
 * Removing data belongs to uninstall.php.
 */
final class Lifecycle {

	/**
	 * Prepares the options and the demonstration page.
	 *
	 * @return void
	 */
	public static function activate(): void {
		update_option( 'books_list_version', Plugin::VERSION, false );

		// Cache invalidation counter, embedded in every transient key.
		add_option( 'books_list_cache_version', 1, '', false );

		self::create_demo_page();
	}

	/**
	 * Creates a page carrying the shortcode, unless one already exists.
	 *
	 * This is what makes the plugin usable straight after activation: no
	 * theme file is touched, the shortcode lives in ordinary post content.
	 *
	 * @return void
	 */
	private static function create_demo_page(): void {
		$existing = (int) get_option( 'books_list_page_id', 0 );

		if ( $existing > 0 && 'page' === get_post_type( $existing ) ) {
			return;
		}

		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => __( 'Books', 'books-list' ),
				'post_name'    => 'books',
				'post_content' => '[' . Shortcode::TAG . ']',
			)
		);

		if ( ! is_wp_error( $page_id ) && $page_id > 0 ) {
			update_option( 'books_list_page_id', (int) $page_id, false );
		}
	}
}
