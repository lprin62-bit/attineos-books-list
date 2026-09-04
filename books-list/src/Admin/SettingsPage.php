<?php
/**
 * Administration screen.
 *
 * @package BooksList
 */

declare( strict_types = 1 );

namespace BooksList\Admin;

use BooksList\BookRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Screen under Settings to inspect and control the book cache.
 *
 * Reads state and renders forms only. The state-changing work lives in
 * CacheActions.
 */
final class SettingsPage {

	public const SLUG = 'books-list';

	public const CAPABILITY = 'manage_options';

	private const GROUP = 'books_list_settings';

	/**
	 * Constructor.
	 *
	 * @param BookRepository $repository Book source.
	 */
	public function __construct( private BookRepository $repository ) {}

	/**
	 * Hooks the screen and its setting.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Declares the screen under the Settings menu.
	 *
	 * @return void
	 */
	public function add_page(): void {
		add_options_page(
			__( 'Books List', 'books-list' ),
			__( 'Books List', 'books-list' ),
			self::CAPABILITY,
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Declares the cache lifetime setting.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			self::GROUP,
			'books_list_cache_ttl',
			array(
				'type'              => 'integer',
				'default'           => HOUR_IN_SECONDS,
				'sanitize_callback' => array( $this, 'sanitize_ttl' ),
			)
		);

		add_settings_section( 'books_list_cache', '', '__return_false', self::SLUG );

		add_settings_field(
			'books_list_cache_ttl',
			__( 'Cache lifetime', 'books-list' ),
			array( $this, 'render_ttl_field' ),
			self::SLUG,
			'books_list_cache'
		);
	}

	/**
	 * Keeps the cache lifetime between one minute and one week.
	 *
	 * @param mixed $value Raw submitted value.
	 * @return int
	 */
	public function sanitize_ttl( mixed $value ): int {
		return min( WEEK_IN_SECONDS, max( MINUTE_IN_SECONDS, absint( $value ) ) );
	}

	/**
	 * Renders the cache lifetime input.
	 *
	 * @return void
	 */
	public function render_ttl_field(): void {
		?>
		<input type="number" name="books_list_cache_ttl" class="small-text"
			min="<?php echo esc_attr( (string) MINUTE_IN_SECONDS ); ?>"
			max="<?php echo esc_attr( (string) WEEK_IN_SECONDS ); ?>" step="60"
			value="<?php echo esc_attr( (string) get_option( 'books_list_cache_ttl', HOUR_IN_SECONDS ) ); ?>" />
		<p class="description"><?php esc_html_e( 'In seconds.', 'books-list' ); ?></p>
		<?php
	}

	/**
	 * Renders the screen.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$last_fetch = $this->repository->get_last_fetch();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Books List', 'books-list' ); ?></h1>

			<?php $this->render_notice(); ?>

			<p>
			<?php
			if ( null === $last_fetch ) {
				esc_html_e( 'No data has been retrieved yet.', 'books-list' );
			} else {
				printf(
					/* translators: %s: date and time of the last retrieval. */
					esc_html__( 'Last retrieved on %s.', 'books-list' ),
					esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_fetch ) )
				);
			}
			?>
			</p>

			<p>
				<?php $this->render_action( CacheActions::REFRESH, __( 'Refresh data', 'books-list' ), 'primary' ); ?>
				<?php $this->render_action( CacheActions::FLUSH, __( 'Clear cache', 'books-list' ), 'secondary' ); ?>
			</p>

			<form action="options.php" method="post">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Renders one state-changing form.
	 *
	 * A POST to admin-post.php rather than a nonced link: an operation that
	 * changes state must not be reachable through a GET request.
	 *
	 * @param string $action Action name, also used as the nonce action.
	 * @param string $label  Button label.
	 * @param string $type   Button style.
	 * @return void
	 */
	private function render_action( string $action, string $label, string $type ): void {
		?>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" style="display:inline">
			<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>" />
			<?php
			wp_nonce_field( $action );
			submit_button( $label, $type, 'submit', false );
			?>
		</form>
		<?php
	}

	/**
	 * Renders the outcome of the previous action, if any.
	 *
	 * @return void
	 */
	private function render_notice(): void {
		$messages = array(
			'refreshed'      => array( 'success', __( 'Book data has been refreshed.', 'books-list' ) ),
			'refresh-failed' => array( 'error', __( 'The book service could not be reached. The cache was cleared.', 'books-list' ) ),
			'flushed'        => array( 'success', __( 'The cache has been cleared.', 'books-list' ) ),
		);

		// Matched against the allow-list above and never used to change
		// state, so no nonce is required to display it.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$notice = isset( $_GET['books_list_notice'] ) ? sanitize_key( wp_unslash( $_GET['books_list_notice'] ) ) : '';

		if ( ! isset( $messages[ $notice ] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $messages[ $notice ][0] ),
			esc_html( $messages[ $notice ][1] )
		);
	}
}
