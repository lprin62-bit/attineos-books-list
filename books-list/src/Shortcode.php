<?php
/**
 * The [books_list] shortcode.
 *
 * @package BooksList
 */

declare( strict_types = 1 );

namespace BooksList;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes the book list inside post content.
 *
 * Deliberately thin: sanitise the attributes, hand over to the renderer.
 */
final class Shortcode {

	public const TAG = 'books_list';

	/**
	 * Constructor.
	 *
	 * @param Renderer $renderer Renderer used to produce the markup.
	 */
	public function __construct( private Renderer $renderer ) {}

	/**
	 * Registers the shortcode.
	 *
	 * @return void
	 */
	public function register(): void {
		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	/**
	 * Renders the shortcode.
	 *
	 * @param array<string,string>|string $atts Raw shortcode attributes.
	 * @return string
	 */
	public function render( $atts ): string {
		$atts = shortcode_atts(
			array(
				'search'   => '',
				'language' => '',
				'heading'  => __( 'A selection of books', 'books-list' ),
			),
			is_array( $atts ) ? $atts : array(),
			self::TAG
		);

		return $this->renderer->render(
			array(
				'search'   => sanitize_text_field( (string) $atts['search'] ),
				'language' => $this->sanitize_language( (string) $atts['language'] ),
				'heading'  => sanitize_text_field( (string) $atts['heading'] ),
			)
		);
	}

	/**
	 * Keeps only a plausible ISO 639-1 language code.
	 *
	 * @param string $language Raw attribute value.
	 * @return string Lowercase code, or an empty string.
	 */
	private function sanitize_language( string $language ): string {
		$language = strtolower( trim( $language ) );

		return preg_match( '/^[a-z]{2,3}$/', $language ) ? $language : '';
	}
}
