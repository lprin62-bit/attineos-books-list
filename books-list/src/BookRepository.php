<?php
/**
 * Cached access to books.
 *
 * @package BooksList
 */

declare(strict_types=1);

namespace BooksList;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Single source of books for the rest of the plugin.
 *
 * Wraps the API client with a transient cache, so a page view never
 * triggers an HTTP call while fresh data is available.
 *
 * Invalidation uses a version counter embedded in every cache key: bumping
 * it orphans all entries at once, where a `DELETE ... LIKE` SQL query would
 * silently do nothing on a site backed by a persistent object cache.
 */
final class BookRepository {


	private const VERSION_OPTION    = 'books_list_cache_version';
	private const LAST_FETCH_OPTION = 'books_list_last_fetch';

	/**
	 * Constructor.
	 *
	 * @param GutendexClient $client API client used on cache misses.
	 */
	public function __construct( private GutendexClient $client ) {}

	/**
	 * Returns books, from cache when available.
	 *
	 * @param array<string,scalar> $args Query parameters.
	 * @return array{books:Book[],total:int}|WP_Error
	 */
	public function get_books( array $args = array() ): array|WP_Error {
		/**
		 * Filters the query parameters sent to the API.
		 *
		 * Applied before the cache key is computed, so a filter that changes
		 * the query also changes the key — otherwise two different result
		 * sets would share one cache entry.
		 *
		 * @param array<string,scalar> $args Query parameters.
		 */
		$args = (array) apply_filters( 'books_list_request_args', $args );

		$cached = get_transient( $this->cache_key( $args ) );
		$result = $this->is_usable( $cached ) ? $cached : $this->refresh( $args );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		/**
		 * Filters the books on their way out of the repository.
		 *
		 * Applied after the cache is read, never before it is written: a
		 * filter that depends on the current user must not have its result
		 * stored and served to everyone else.
		 *
		 * @param Book[]               $books Books for this query.
		 * @param array<string,scalar> $args  Query parameters used.
		 */
		$result['books'] = (array) apply_filters( 'books_list_books', $result['books'], $args );

		return $result;
	}

	/**
	 * Tells whether a cached entry can be used as is.
	 *
	 * The shape is checked, not just the type: an entry written by an
	 * earlier version of the plugin may hold a different structure, and a
	 * mistyped value would only surface as a fatal error further down.
	 * Rejecting it makes the cache self-healing across upgrades.
	 *
	 * @param mixed $cached Raw value read from the transient.
	 * @return bool
	 */
	private function is_usable( mixed $cached ): bool {
		if ( ! is_array( $cached ) || ! isset( $cached['books'], $cached['total'] ) ) {
			return false;
		}

		if ( ! is_int( $cached['total'] ) || ! is_array( $cached['books'] ) ) {
			return false;
		}

		foreach ( $cached['books'] as $book ) {
			if ( ! $book instanceof Book ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Fetches from the API and writes the cache entry.
	 *
	 * @param array<string,scalar> $args Query parameters, already filtered.
	 * @return array{books:Book[],total:int}|WP_Error
	 */
	private function refresh( array $args ): array|WP_Error {
		$result = $this->client->fetch( $args );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		set_transient( $this->cache_key( $args ), $result, $this->ttl() );
		update_option( self::LAST_FETCH_OPTION, time(), false );

		return $result;
	}

	/**
	 * Invalidates every cached entry.
	 *
	 * @return void
	 */
	public function flush(): void {
		$version = (int) get_option( self::VERSION_OPTION, 1 ) + 1;

		update_option( self::VERSION_OPTION, $version, false );
		delete_option( self::LAST_FETCH_OPTION );

		/**
		 * Fires once the book cache has been invalidated.
		 *
		 * @param int $version New cache version.
		 */
		do_action( 'books_list_cache_flushed', $version );
	}

	/**
	 * Timestamp of the last successful fetch, null when never fetched.
	 *
	 * @return int|null
	 */
	public function get_last_fetch(): ?int {
		$timestamp = (int) get_option( self::LAST_FETCH_OPTION, 0 );

		return $timestamp > 0 ? $timestamp : null;
	}

	/**
	 * Builds the transient key for a set of query parameters.
	 *
	 * @param array<string,scalar> $args Query parameters.
	 * @return string
	 */
	private function cache_key( array $args ): string {
		ksort( $args );

		return 'books_list_' . md5( get_option( self::VERSION_OPTION, 1 ) . '|' . wp_json_encode( $args ) );
	}

	/**
	 * Cache lifetime in seconds.
	 *
	 * @return int
	 */
	private function ttl(): int {
		/**
		 * Filters the book cache lifetime.
		 *
		 * @param int $ttl Lifetime in seconds.
		 */
		return max( 1, (int) apply_filters( 'books_list_cache_ttl', (int) get_option( 'books_list_cache_ttl', HOUR_IN_SECONDS ) ) );
	}
}
