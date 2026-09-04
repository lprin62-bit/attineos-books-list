<?php
/**
 * State-changing administration actions.
 *
 * @package BooksList
 */

declare(strict_types=1);

namespace BooksList\Admin;

use BooksList\BookRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the refresh and clear-cache buttons.
 *
 * Both are POST endpoints on admin-post.php: capability first, then nonce,
 * then the work, then a redirect — so reloading never replays the action.
 */
final class CacheActions {


	public const REFRESH = 'books_list_refresh';

	public const FLUSH = 'books_list_flush';

	/**
	 * Constructor.
	 *
	 * @param BookRepository $repository Book source.
	 */
	public function __construct( private BookRepository $repository ) {}

	/**
	 * Hooks both endpoints.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::REFRESH, array( $this, 'refresh' ) );
		add_action( 'admin_post_' . self::FLUSH, array( $this, 'flush' ) );
	}

	/**
	 * Clears the cache, then primes it with a fresh set of books.
	 *
	 * Clearing first matters: cache keys embed the query parameters, so
	 * refreshing one key would leave every other variation stale.
	 *
	 * @return void
	 */
	public function refresh(): void {
		$this->authorize( self::REFRESH );

		$this->repository->flush();

		/*
		 * get_books() and not a direct fetch: it goes through the same code
		 * path as the front end, so the cache is primed under the key the
		 * front actually reads.
		 */
		$this->redirect( is_wp_error( $this->repository->get_books() ) ? 'refresh-failed' : 'refreshed' );
	}

	/**
	 * Clears the cache without fetching anything.
	 *
	 * @return void
	 */
	public function flush(): void {
		$this->authorize( self::FLUSH );

		$this->repository->flush();

		$this->redirect( 'flushed' );
	}

	/**
	 * Stops the request unless the user is allowed and the nonce is valid.
	 *
	 * @param string $action Action name, also used as the nonce action.
	 * @return void
	 */
	private function authorize( string $action ): void {
		if ( ! current_user_can( SettingsPage::CAPABILITY ) ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', 'books-list' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( $action );
	}

	/**
	 * Sends the user back to the settings screen with a status flag.
	 *
	 * @param string $notice Outcome key read back by the settings screen.
	 * @return void
	 */
	private function redirect( string $notice ): void {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'              => SettingsPage::SLUG,
					'books_list_notice' => $notice,
				),
				admin_url( 'options-general.php' )
			)
		);

		exit;
	}
}
