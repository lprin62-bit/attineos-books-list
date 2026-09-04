<?php
/**
 * Plugin composition root.
 *
 * @package BooksList
 */

declare( strict_types = 1 );

namespace BooksList;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the services and attaches them to WordPress.
 *
 * Carries no business logic: anything resembling a rule belongs elsewhere.
 */
final class Plugin {

	public const VERSION = '1.4.1';

	/**
	 * Constructor.
	 *
	 * @param string $file Absolute path to the plugin main file.
	 */
	public function __construct( private string $file ) {}

	/**
	 * Registers the plugin hooks.
	 *
	 * Runs while the main file loads, a requirement for
	 * register_activation_hook() to be taken into account.
	 *
	 * @return void
	 */
	public function boot(): void {
		register_activation_hook( $this->file, array( Lifecycle::class, 'activate' ) );

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_style' ) );

		$repository = new BookRepository( new GutendexClient() );

		( new Shortcode( new Renderer( $repository ) ) )->register();

		if ( is_admin() ) {
			( new Admin\SettingsPage( $repository ) )->register();
			( new Admin\CacheActions( $repository ) )->register();

			add_filter( 'plugin_action_links_' . plugin_basename( $this->file ), array( $this, 'action_links' ) );
		}
	}

	/**
	 * Adds a settings shortcut to the plugin row on the Plugins screen.
	 *
	 * The screen lives under Settings, which is not where an administrator
	 * looks first after activating a plugin.
	 *
	 * @param string[] $links Existing action links.
	 * @return string[]
	 */
	public function action_links( array $links ): array {
		array_unshift(
			$links,
			sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( admin_url( 'options-general.php?page=' . Admin\SettingsPage::SLUG ) ),
				esc_html__( 'Settings', 'books-list' )
			)
		);

		return $links;
	}

	/**
	 * Loads the translations.
	 *
	 * Hooked on `init` rather than `plugins_loaded`: since WordPress 6.7,
	 * loading a text domain earlier triggers a _doing_it_wrong() notice.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain( 'books-list', false, dirname( plugin_basename( $this->file ) ) . '/languages' );
	}

	/**
	 * Loads the stylesheet.
	 *
	 * Under a kilobyte, so it is enqueued on every front-end page:
	 * detecting the shortcode would cost more code than the bytes it saves.
	 *
	 * @return void
	 */
	public function enqueue_style(): void {
		wp_enqueue_style( 'books-list', plugin_dir_url( $this->file ) . 'assets/css/books-list.css', array(), self::VERSION );
	}
}
