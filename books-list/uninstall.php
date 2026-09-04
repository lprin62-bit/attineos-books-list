<?php
/**
 * Plugin uninstall routine.
 *
 * WordPress runs this file when the plugin is deleted, in an isolated
 * context: the plugin is not loaded and none of its classes are available.
 * That is why this file is preferred over register_uninstall_hook().
 *
 * @package BooksList
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$books_list_options = array(
	'books_list_version',
	'books_list_cache_version',
	'books_list_cache_ttl',
	'books_list_last_fetch',
	'books_list_page_id',
);

foreach ( $books_list_options as $books_list_option ) {
	delete_option( $books_list_option );
}

/*
 * Transients carry an expiry and vanish on their own.
 *
 * The page created on activation is deliberately kept: it is content owned
 * by the site, not by the plugin.
 */
