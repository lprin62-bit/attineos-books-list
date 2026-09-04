<?php
/**
 * Tests for the Book value object.
 *
 * @package BooksList
 */

declare( strict_types = 1 );

namespace BooksList\Tests\Unit;

use BooksList\Book;
use PHPUnit\Framework\TestCase;

/**
 * Covers the tolerance of Book::from_array() to incomplete payloads.
 */
final class BookTest extends TestCase {

	/**
	 * A complete entry maps every field.
	 *
	 * @return void
	 */
	public function test_maps_a_complete_entry(): void {
		$book = Book::from_array( self::payload() );

		$this->assertInstanceOf( Book::class, $book );
		$this->assertSame( 84, $book->id );
		$this->assertSame( 'Frankenstein', $book->title );
		$this->assertSame( array( 'Shelley, Mary Wollstonecraft' ), $book->authors );
		$this->assertSame( array( 'en' ), $book->languages );
		$this->assertSame( 12345, $book->download_count );
		$this->assertSame( 'https://example.org/cover.jpg', $book->cover_url );
		$this->assertSame( 'https://example.org/book.html', $book->source_url );
	}

	/**
	 * An entry without a usable title is rejected rather than rendered blank.
	 *
	 * @return void
	 */
	public function test_rejects_an_entry_without_title(): void {
		$this->assertNull( Book::from_array( self::payload( array( 'title' => '   ' ) ) ) );
		$this->assertNull( Book::from_array( self::payload( array( 'title' => null ) ) ) );
		$this->assertNull( Book::from_array( array() ) );
	}

	/**
	 * Missing collections degrade to empty arrays, never to null.
	 *
	 * @return void
	 */
	public function test_missing_collections_become_empty_arrays(): void {
		$book = Book::from_array(
			self::payload(
				array(
					'authors'   => null,
					'languages' => 'en',
				)
			)
		);

		$this->assertSame( array(), $book->authors );
		$this->assertSame( array(), $book->languages );
	}

	/**
	 * Malformed author entries are skipped, valid ones are kept.
	 *
	 * @return void
	 */
	public function test_skips_malformed_authors(): void {
		$book = Book::from_array(
			self::payload(
				array(
					'authors' => array(
						array( 'birth_year' => 1797 ),
						'Shelley',
						array( 'name' => '  ' ),
						array( 'name' => 'Verne, Jules' ),
					),
				)
			)
		);

		$this->assertSame( array( 'Verne, Jules' ), $book->authors );
	}

	/**
	 * Only absolute http(s) URLs are accepted from the third-party payload.
	 *
	 * @return void
	 */
	public function test_rejects_non_http_urls(): void {
		$book = Book::from_array(
			self::payload(
				array(
					'formats' => array(
						'image/jpeg' => 'javascript:alert(1)',
						'text/html'  => 'data:text/html;base64,PHN2Zz4=',
					),
				)
			)
		);

		$this->assertNull( $book->cover_url );
		$this->assertNull( $book->source_url );
	}

	/**
	 * A missing or negative download count falls back to zero.
	 *
	 * @return void
	 */
	public function test_normalises_download_count(): void {
		$this->assertSame( 0, Book::from_array( self::payload( array( 'download_count' => -5 ) ) )->download_count );
		$this->assertSame( 0, Book::from_array( self::payload( array( 'download_count' => null ) ) )->download_count );
	}

	/**
	 * Builds a valid payload, optionally overridden.
	 *
	 * @param array<string,mixed> $overrides Keys to replace.
	 * @return array<string,mixed>
	 */
	private static function payload( array $overrides = array() ): array {
		return array_merge(
			array(
				'id'             => 84,
				'title'          => 'Frankenstein',
				'authors'        => array( array( 'name' => 'Shelley, Mary Wollstonecraft' ) ),
				'languages'      => array( 'en' ),
				'download_count' => 12345,
				'formats'        => array(
					'image/jpeg' => 'https://example.org/cover.jpg',
					'text/html'  => 'https://example.org/book.html',
				),
			),
			$overrides
		);
	}
}
