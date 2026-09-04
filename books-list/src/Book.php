<?php
/**
 * Book value object.
 *
 * @package BooksList
 */

declare( strict_types = 1 );

namespace BooksList;

defined( 'ABSPATH' ) || exit;

/**
 * A single book.
 *
 * The only class that knows the shape of a Gutendex payload, and the only
 * one with no WordPress dependency — which is what makes it unit-testable
 * without bootstrapping WordPress.
 */
final class Book {

	/**
	 * Constructor.
	 *
	 * @param int         $id             Gutendex identifier.
	 * @param string      $title          Book title, never empty.
	 * @param string[]    $authors        Author names, possibly empty.
	 * @param string[]    $languages      Language codes, possibly empty.
	 * @param int         $download_count Downloads reported by the API.
	 * @param string|null $cover_url      Cover image URL, or null.
	 * @param string|null $source_url     Readable version URL, or null.
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $title,
		public readonly array $authors,
		public readonly array $languages,
		public readonly int $download_count,
		public readonly ?string $cover_url,
		public readonly ?string $source_url
	) {}

	/**
	 * Builds a Book from one entry of a Gutendex payload.
	 *
	 * Returns null for an entry without a title: rendering a blank card
	 * would be worse than skipping it.
	 *
	 * @param array<string,mixed> $data One entry of the API `results` array.
	 * @return self|null
	 */
	public static function from_array( array $data ): ?self {
		$title = is_string( $data['title'] ?? null ) ? trim( $data['title'] ) : '';

		if ( '' === $title ) {
			return null;
		}

		$formats = is_array( $data['formats'] ?? null ) ? $data['formats'] : array();

		return new self(
			(int) ( $data['id'] ?? 0 ),
			$title,
			self::authors( $data['authors'] ?? null ),
			self::strings( $data['languages'] ?? null ),
			max( 0, (int) ( $data['download_count'] ?? 0 ) ),
			self::url( $formats, array( 'image/jpeg', 'image/png' ) ),
			self::url( $formats, array( 'text/html', 'text/html; charset=utf-8' ) )
		);
	}

	/**
	 * Extracts author names, skipping malformed entries.
	 *
	 * @param mixed $authors Raw `authors` value.
	 * @return string[]
	 */
	private static function authors( mixed $authors ): array {
		$names = array();

		foreach ( is_array( $authors ) ? $authors : array() as $author ) {
			$name = is_array( $author ) ? trim( (string) ( $author['name'] ?? '' ) ) : '';

			if ( '' !== $name ) {
				$names[] = $name;
			}
		}

		return $names;
	}

	/**
	 * Keeps only the non-empty strings of a raw list.
	 *
	 * @param mixed $values Raw list.
	 * @return string[]
	 */
	private static function strings( mixed $values ): array {
		$out = array();

		foreach ( is_array( $values ) ? $values : array() as $value ) {
			if ( is_string( $value ) && '' !== $value ) {
				$out[] = strtolower( $value );
			}
		}

		return $out;
	}

	/**
	 * Returns the first http(s) URL among the given format keys.
	 *
	 * URLs come from a third party: anything that is not an absolute http
	 * link is dropped rather than handed to an href or src attribute.
	 *
	 * @param array<string,mixed> $formats API `formats` map.
	 * @param string[]            $keys    Format keys to try, in order.
	 * @return string|null
	 */
	private static function url( array $formats, array $keys ): ?string {
		foreach ( $keys as $key ) {
			$url = trim( (string) ( $formats[ $key ] ?? '' ) );

			if ( str_starts_with( $url, 'http://' ) || str_starts_with( $url, 'https://' ) ) {
				return $url;
			}
		}

		return null;
	}
}
