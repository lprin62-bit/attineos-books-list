<?php
/**
 * Front-end rendering.
 *
 * @package BooksList
 */

declare(strict_types=1);

namespace BooksList;

defined( 'ABSPATH' ) || exit;

/**
 * Turns a query into HTML.
 *
 * Every entry point goes through this class, so the markup lives in one
 * place: adding a block or a REST route means calling render(), not
 * duplicating templates.
 */
final class Renderer {


	/**
	 * Query parameter holding the current page.
	 *
	 * @var string
	 */
	private const PAGE_PARAM = 'books_page';

	/**
	 * Query parameter holding the title search.
	 *
	 * @var string
	 */
	public const SEARCH_PARAM = 'books_search';

	/**
	 * Query parameter holding the language filter.
	 *
	 * @var string
	 */
	public const LANGUAGE_PARAM = 'books_language';

	/**
	 * Highest page number accepted from the URL.
	 *
	 * Without a ceiling, a large value overflows the offset arithmetic into
	 * a float and crashes the page. The cap sits far above any page the
	 * catalogue can actually reach.
	 *
	 * @var int
	 */
	private const MAX_PAGE = 10000;

	/**
	 * Books shown per page.
	 *
	 * Deliberately smaller than the API page size: halving the number of
	 * covers halves the weight of a page view, and one API call then serves
	 * two page views. Must divide GutendexClient::PAGE_SIZE.
	 *
	 * @var int
	 */
	private const PER_PAGE = 16;

	/**
	 * Constructor.
	 *
	 * @param BookRepository $repository Book source.
	 */
	public function __construct( private BookRepository $repository ) {}

	/**
	 * Renders a page of books.
	 *
	 * @param array{search:string,language:string,heading:string} $args Display options.
	 * @return string
	 */
	public function render( array $args ): string {
		$page   = $this->current_page();
		$offset = ( $page - 1 ) * self::PER_PAGE;

		/*
		 * The URL wins over the shortcode attributes: the visitor's choice
		 * overrides the page author's default. An author who wants a fixed
		 * list simply does not display the form.
		 */
		$languages = $this->languages();
		$search    = $this->requested( self::SEARCH_PARAM, $args['search'] );
		$language  = $this->requested( self::LANGUAGE_PARAM, $args['language'] );
		$language  = isset( $languages[ $language ] ) ? $language : '';

		$api_page = intdiv( $offset, GutendexClient::PAGE_SIZE ) + 1;

		/*
		 * `page` is omitted on the first page rather than sent as 1. The
		 * cache key is built from these arguments, so an empty set is what
		 * a manual refresh from the admin screen primes — sending 1 here
		 * would write the cache under a key the front never reads.
		 */
		$result = $this->repository->get_books(
			array_filter(
				array(
					'search'    => $search,
					'languages' => $language,
					'page'      => $api_page > 1 ? $api_page : 0,
				)
			)
		);

		$form = array(
			'hidden'    => $this->form_hidden_fields(),
			'search'    => $search,
			'language'  => $language,
			'languages' => $languages,
		);

		if ( is_wp_error( $result ) ) {
			return $this->template(
				array(
					'heading' => $args['heading'],
					'message' => __( 'Book data is temporarily unavailable. Please try again later.', 'books-list' ),
					'form'    => $form,
				)
			);
		}

		$books = array_slice( $result['books'], $offset % GutendexClient::PAGE_SIZE, self::PER_PAGE );

		if ( array() === $books ) {
			return $this->template(
				array(
					'heading' => $args['heading'],
					'message' => __( 'No book matches this selection.', 'books-list' ),
					'form'    => $form,
				)
			);
		}

		return $this->template(
			array(
				'heading'    => $args['heading'],
				'books'      => $books,
				'form'       => $form,
				'pagination' => $this->pagination( $page, $result['total'] ),
			)
		);
	}

	/**
	 * Page requested in the URL, one by default.
	 *
	 * @return int
	 */
	private function current_page(): int {
		// Read-only display parameter, sanitised to a bounded positive
		// integer and never used to change state, so no nonce applies.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = absint( wp_unslash( $_GET[ self::PAGE_PARAM ] ?? 1 ) );

		return min( self::MAX_PAGE, max( 1, $page ) );
	}

	/**
	 * Reads a display parameter from the URL, with a fallback.
	 *
	 * @param string $param    Query parameter name.
	 * @param string $fallback Value used when the parameter is absent.
	 * @return string
	 */
	private function requested( string $param, string $fallback ): string {
		// Read-only display parameters, never used to change state, so no
		// nonce applies. An array parameter — books_search[]=x — is ignored
		// rather than cast, which would raise a conversion warning.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET[ $param ] ) || ! is_string( $_GET[ $param ] ) ) {
			return $fallback;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return sanitize_text_field( wp_unslash( $_GET[ $param ] ) );
	}

	/**
	 * Languages offered by the filter.
	 *
	 * Gutendex exposes no endpoint listing its languages, so the list is
	 * curated and filterable rather than discovered.
	 *
	 * @return array<string,string> Language code to label.
	 */
	private function languages(): array {
		/**
		 * Filters the languages offered by the filter.
		 *
		 * @param array<string,string> $languages Language code to label.
		 */
		return (array) apply_filters(
			'books_list_languages',
			array(
				'en' => __( 'English', 'books-list' ),
				'fr' => __( 'French', 'books-list' ),
				'de' => __( 'German', 'books-list' ),
				'es' => __( 'Spanish', 'books-list' ),
				'it' => __( 'Italian', 'books-list' ),
				'pt' => __( 'Portuguese', 'books-list' ),
				'nl' => __( 'Dutch', 'books-list' ),
				'ru' => __( 'Russian', 'books-list' ),
			)
		);
	}

	/**
	 * Query variables the form must carry over as hidden fields.
	 *
	 * The form has no `action`, so it submits to the current URL — correct
	 * in every context, including a subdirectory install or a page shown
	 * outside the loop. A GET form replaces the query string, though, so
	 * any other variable has to be restored explicitly.
	 *
	 * @return array<string,string>
	 */
	private function form_hidden_fields(): array {
		// Read-only: these values are echoed back through esc_attr() and
		// never used to change state, so no nonce applies.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$fields = wp_unslash( $_GET );

		// The form owns these three. Carrying them over would duplicate the
		// inputs, and would pin the visitor to a stale page number.
		unset( $fields[ self::PAGE_PARAM ], $fields[ self::SEARCH_PARAM ], $fields[ self::LANGUAGE_PARAM ] );

		return array_map( 'strval', array_filter( (array) $fields, 'is_scalar' ) );
	}

	/**
	 * Builds the pagination links, empty when a single page is enough.
	 *
	 * The paginate_links() helper is used rather than hand-rolled markup: it produces
	 * the accessible structure WordPress themes already style, handles the
	 * ellipsis on long ranges, and keeps the other query parameters.
	 *
	 * @param int $page  Current page.
	 * @param int $total Total number of books reported by the API.
	 * @return string
	 */
	private function pagination( int $page, int $total ): string {
		$pages = min( self::MAX_PAGE, (int) ceil( $total / self::PER_PAGE ) );

		if ( $pages < 2 ) {
			return '';
		}

		return (string) paginate_links(
			array(
				'base'      => add_query_arg( self::PAGE_PARAM, '%#%' ),
				'format'    => '',
				'current'   => min( $page, $pages ),
				'total'     => $pages,
				'prev_text' => __( 'Previous', 'books-list' ),
				'next_text' => __( 'Next', 'books-list' ),
			)
		);
	}

	/**
	 * Renders the section template and returns its output.
	 *
	 * @param array<string,mixed> $context Variables made available to the template.
	 * @return string
	 */
	private function template( array $context ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- read by the template.
		ob_start();
		include dirname( __DIR__ ) . '/templates/books-list.php';

		return (string) ob_get_clean();
	}
}
