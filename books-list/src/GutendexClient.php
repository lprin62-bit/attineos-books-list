<?php
/**
 * Gutendex API client.
 *
 * @package BooksList
 */

declare(strict_types=1);

namespace BooksList;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Talks to the public Gutendex API and returns Book objects.
 *
 * Knows nothing about caching or rendering: one HTTP call, validated, and
 * a typed result.
 */
final class GutendexClient {


	private const ENDPOINT = 'https://gutendex.com/books/';
	private const TIMEOUT  = 10;

	/**
	 * Books returned per page by Gutendex. Fixed by the API, not by us.
	 *
	 * @var int
	 */
	public const PAGE_SIZE = 32;

	/**
	 * Fetches a list of books.
	 *
	 * @param array<string,scalar> $args Query parameters accepted by Gutendex.
	 * @return array{books:Book[],total:int}|WP_Error Result on success, WP_Error on any failure.
	 */
	public function fetch( array $args = array() ): array|WP_Error {
		/*
		 * add_query_arg() does not encode the values it appends — build_query()
		 * calls _http_build_query() with $urlencode set to false — so a search
		 * for two words would put a raw space in the outgoing URL. The values
		 * are encoded here; removing this is what breaks multi-word search.
		 */
		$query = array_map( 'rawurlencode', array_map( 'strval', $args ) );

		// Safe variant: refuses private and loopback addresses, closing the
		// door on server-side request forgery.
		$response = wp_safe_remote_get(
			add_query_arg( $query, self::ENDPOINT ),
			array(
				'timeout'    => self::TIMEOUT,
				'user-agent' => 'BooksList/' . Plugin::VERSION . '; ' . home_url( '/' ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'books_list_request_failed', __( 'The book service could not be reached.', 'books-list' ) );
		}

		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error( 'books_list_unexpected_status', __( 'The book service returned an unexpected response.', 'books-list' ) );
		}

		$payload = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $payload['results'] ?? null ) ) {
			return new WP_Error( 'books_list_invalid_payload', __( 'The book service returned unreadable data.', 'books-list' ) );
		}

		return array(
			'books' => array_values(
				array_filter(
					array_map(
						static fn( $entry ) => is_array( $entry ) ? Book::from_array( $entry ) : null,
						$payload['results']
					)
				)
			),
			'total' => max( 0, (int) ( $payload['count'] ?? 0 ) ),
		);
	}
}
