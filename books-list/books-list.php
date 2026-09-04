<?php
/**
 * Plugin Name:       Books List
 * Description:       Displays a selection of books from the public Gutendex API.
 * Version:           1.4.1
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Lucas
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       books-list
 * Domain Path:       /languages
 *
 * @package BooksList
 */

declare( strict_types = 1 );

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/src/Autoloader.php';

BooksList\Autoloader::register();

( new BooksList\Plugin( __FILE__ ) )->boot();
