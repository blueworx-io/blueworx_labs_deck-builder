<?php
/**
 * The plugin's wp-admin screens.
 *
 * @package Blueworx\DeckBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Deck Builder admin menu and renders its screens.
 *
 * Every screen here is built from the shared blueworx-admin-design system, so a
 * Deck Builder screen and any other BlueWorx plugin screen read as one product.
 */
class Blueworx_Deck_Builder_Admin {

	/**
	 * The admin page slug, and the value of the `page` query argument.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'blueworx-labs-deck-builder';

	/**
	 * The capability required to open any of these screens.
	 *
	 * @var string
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * The single instance.
	 *
	 * @var Blueworx_Deck_Builder_Admin|null
	 */
	private static $instance = null;

	/**
	 * The hook suffix WordPress returns when the menu page is added.
	 *
	 * @var string
	 */
	private $hook_suffix = '';

	/**
	 * Get the single instance.
	 *
	 * @return Blueworx_Deck_Builder_Admin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hook the admin screens up.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add the top-level menu entry.
	 *
	 * @return void
	 */
	public function add_menu() {
		$this->hook_suffix = add_menu_page(
			__( 'Deck Builder', 'blueworx-labs-deck-builder' ),
			__( 'Deck Builder', 'blueworx-labs-deck-builder' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_overview' ),
			'dashicons-slides',
			58
		);
	}

	/**
	 * Load the design system on this plugin's screens, and nowhere else.
	 *
	 * @param string $hook_suffix The screen currently being loaded.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( '' === $this->hook_suffix || $hook_suffix !== $this->hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'blueworx-admin-design',
			BLUEWORX_DECK_BUILDER_URL . 'assets/blueworx-admin-design.css',
			array(),
			BLUEWORX_DECK_BUILDER_VERSION
		);

		// The full-bleed chrome overrides the design system documents. They are
		// the only styling this plugin keeps of its own, and they go inline so
		// there is never a second admin stylesheet to drift from the first.
		wp_add_inline_style( 'blueworx-admin-design', $this->chrome_overrides() );

		// Every [data-lucide] element on a PHP-rendered screen stays empty
		// without this. It is a module, so an older WordPress simply gets no
		// icons rather than a fatal error.
		if ( function_exists( 'wp_enqueue_script_module' ) ) {
			wp_enqueue_script_module(
				'blueworx-admin-icons',
				BLUEWORX_DECK_BUILDER_URL . 'assets/blueworx-admin-icons.js',
				array(),
				BLUEWORX_DECK_BUILDER_VERSION
			);
		}
	}

	/**
	 * Drop WordPress's own chrome padding on this plugin's screens.
	 *
	 * @return string
	 */
	private function chrome_overrides() {
		$body = 'body.toplevel_page_' . self::PAGE_SLUG;

		return implode(
			"\n",
			array(
				'.wrap.bw-wrap { margin: 0; }',
				$body . ' #wpcontent { padding-left: 0; }',
				$body . ' #wpbody-content { padding-bottom: 0; }',
				$body . ' #wpfooter { display: none; }',
			)
		);
	}

	/**
	 * Render the overview screen.
	 *
	 * @return void
	 */
	public function render_overview() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to open this screen.', 'blueworx-labs-deck-builder' ) );
		}

		?>
		<div class="wrap bw-wrap">
			<div class="bw-admin bw-page">
				<header class="bw-pagehead">
					<div class="bw-pagehead__titles">
						<p class="bw-pagehead__eyebrow"><?php esc_html_e( 'Deck Builder', 'blueworx-labs-deck-builder' ); ?></p>
						<h1 class="bw-pagehead__h1"><?php esc_html_e( 'Decks', 'blueworx-labs-deck-builder' ); ?></h1>
						<p class="bw-pagehead__lede"><?php esc_html_e( 'Build a deck once, then present it from anywhere on the site.', 'blueworx-labs-deck-builder' ); ?></p>
					</div>
				</header>
				<div class="bw-page__body">
					<div class="bw-panels">
						<section class="bw-card">
							<div class="bw-card__head">
								<div class="bw-card__titles">
									<h2 class="bw-card__title"><?php esc_html_e( 'Your decks', 'blueworx-labs-deck-builder' ); ?></h2>
								</div>
							</div>
							<div class="bw-card__body">
								<div class="bw-empty" data-bw-deck-builder-empty>
									<i class="bw-icon bw-icon--28 bw-empty__icon" data-lucide="layout-dashboard"></i>
									<h3 class="bw-empty__title"><?php esc_html_e( 'No decks yet', 'blueworx-labs-deck-builder' ); ?></h3>
									<p class="bw-empty__text"><?php esc_html_e( 'This plugin is set up but has no deck content yet. The first deck screen lands in a later release.', 'blueworx-labs-deck-builder' ); ?></p>
								</div>
							</div>
						</section>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
