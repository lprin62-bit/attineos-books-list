<?php
/**
 * PHPUnit bootstrap.
 *
 * Unit tests cover the classes that carry no WordPress dependency, so no
 * WordPress test suite is required. Loading Book here and nothing else is
 * itself a check: if the class ever starts calling a WordPress function,
 * these tests break.
 *
 * @package BooksList
 */

declare( strict_types = 1 );

define( 'ABSPATH', __DIR__ . '/' );

require_once dirname( __DIR__ ) . '/books-list/src/Book.php';
