<?php
/**
 * The plugin's wp-admin menu, chrome and shared screen furniture.
 *
 * @package Blueworx\DeckBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Deck Builder admin menu and renders the screens the page
 * editor library does not: the decks dashboard, the create screen, the three
 * lists and settings.
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
	 * The capability required to open any of these screens. Defined once, in
	 * Blueworx_Deck_Builder_Roles, and named here so every screen in this
	 * plugin keeps reading it from the same place.
	 *
	 * @var string
	 */
	const CAPABILITY = Blueworx_Deck_Builder_Roles::CAPABILITY;

	/**
	 * The single instance.
	 *
	 * @var Blueworx_Deck_Builder_Admin|null
	 */
	private static $instance = null;

	/**
	 * Hook suffixes for every screen this class renders itself.
	 *
	 * @var array<int,string>
	 */
	private $hooks = [];

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
	 * The menu goes on at priority 9 so the top-level entry exists before the
	 * page editor library adds its own screens to it at priority 10 — a
	 * submenu registered against a parent that does not exist yet is silently
	 * dropped.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', [ $this, 'add_menu' ], 9 );
		add_action( 'admin_head', [ $this, 'hide_editor_entries' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_filter( 'admin_body_class', [ $this, 'body_class' ] );
		add_action( 'admin_post_blueworx_deck_action', [ $this, 'handle_action' ] );
		add_action( 'admin_post_blueworx_deck_create', [ $this, 'handle_create' ] );
	}

	/**
	 * Add the menu, in the order the design sets out.
	 *
	 * @return void
	 */
	public function add_menu() {
		$this->hooks[] = add_menu_page(
			__( 'Deck Builder', 'blueworx-labs-deck-builder' ),
			__( 'Deck Builder', 'blueworx-labs-deck-builder' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			[ $this, 'render_decks' ],
			// The design system's own icon, not a WordPress one. It comes back
			// as a data URI because WordPress draws this menu itself, before
			// any of the system's JavaScript has run — see Icons::menu(),
			// which is also where the trade-off is written down: a background
			// image cannot inherit a colour, so this icon does not brighten on
			// hover the way a dashicon does.
			\Blueworx\PageEditor\v1\Icons::menu( 'playing-cards-fan' ),
			58
		);

		$this->hooks[] = add_submenu_page( self::PAGE_SLUG, __( 'Decks', 'blueworx-labs-deck-builder' ), __( 'Decks', 'blueworx-labs-deck-builder' ), self::CAPABILITY, self::PAGE_SLUG, [ $this, 'render_decks' ] );

		// Creating a deck is no longer a screen: there is nothing left to ask
		// before making one, because a deck starts as a copy of the whole
		// content library and everything about the client is edited on the
		// deck itself. So this menu item does the thing rather than opening a
		// form that only stood between somebody and the editor. WordPress
		// links a submenu slug containing ".php" straight through, which is
		// how a menu item can be an action at all — and the nonce is what
		// stops a link somebody clicked elsewhere from making decks.
		add_submenu_page(
			self::PAGE_SLUG,
			__( 'Create new deck', 'blueworx-labs-deck-builder' ),
			__( 'Create new deck', 'blueworx-labs-deck-builder' ),
			self::CAPABILITY,
			self::create_url()
		);

		$pages = [
			[ self::PAGE_SLUG . '-library', __( 'Content library', 'blueworx-labs-deck-builder' ), [ $this, 'render_library' ] ],
			[ self::PAGE_SLUG . '-case-studies', __( 'Case studies', 'blueworx-labs-deck-builder' ), [ $this, 'render_case_studies' ] ],
			[ self::PAGE_SLUG . '-packages', __( 'Support packages', 'blueworx-labs-deck-builder' ), [ $this, 'render_packages' ] ],
		];

		// Settings is not here: it is a page editor screen, so the library
		// adds it — last, which is where WordPress's own settings belong.

		foreach ( $pages as $page ) {
			$this->hooks[] = add_submenu_page( self::PAGE_SLUG, $page[1], $page[1], self::CAPABILITY, $page[0], $page[2] );
		}
	}

	/**
	 * The address that makes a new deck and opens it.
	 *
	 * @return string
	 */
	public static function create_url() {
		return wp_nonce_url(
			add_query_arg( 'action', 'blueworx_deck_create', admin_url( 'admin-post.php' ) ),
			'blueworx_deck_create'
		);
	}

	/**
	 * Take the record editors back out of the menu.
	 *
	 * They are reached from a list, with a record id, and an editor opened
	 * from a menu item has no record to edit — it would say so, correctly,
	 * and read as broken.
	 *
	 * This runs on admin_head rather than admin_menu, and the difference is
	 * the whole trick. WordPress works out which parent a plugin page belongs
	 * to by looking it up in the submenu, and it uses that parent to decide
	 * whether the current user may open the page at all. Remove the entry
	 * while the menu is still being built and the page loses its parent, the
	 * lookup fails, and every one of these editors answers 403 — the screen
	 * becomes unreachable rather than merely unlisted. By admin_head that
	 * check has already run and the menu has not yet been drawn, so the item
	 * disappears and the page keeps working.
	 *
	 * @return void
	 */
	public function hide_editor_entries() {
		foreach (
			[
				Blueworx_Deck_Builder_Editor::DECK_SCREEN,
				Blueworx_Deck_Builder_Editor::PACKAGE_SCREEN,
				Blueworx_Deck_Builder_Editor::STUDY_SCREEN,
				Blueworx_Deck_Builder_Editor::LIBRARY_SCREEN,
			] as $slug
		) {
			remove_submenu_page( self::PAGE_SLUG, $slug );
		}
	}

	/**
	 * Whether the screen being loaded is one this class renders.
	 *
	 * @param string $hook_suffix Screen hook.
	 * @return bool
	 */
	private function is_own_screen( $hook_suffix ) {
		return in_array( $hook_suffix, array_filter( $this->hooks ), true );
	}

	/**
	 * Load the design system on this plugin's screens, and nowhere else.
	 *
	 * @param string $hook_suffix The screen currently being loaded.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( ! $this->is_own_screen( $hook_suffix ) ) {
			return;
		}

		wp_enqueue_style(
			'blueworx-admin-design',
			BLUEWORX_DECK_BUILDER_URL . 'assets/blueworx-admin-design.css',
			[],
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
				[],
				BLUEWORX_DECK_BUILDER_VERSION
			);
		}
	}

	/**
	 * Mark this plugin's own screens, so the chrome overrides can be scoped to
	 * them without guessing at the class WordPress builds from a hook name.
	 *
	 * @param string $classes Existing body classes.
	 * @return string
	 */
	public function body_class( $classes ) {
		global $hook_suffix;
		if ( $this->is_own_screen( (string) $hook_suffix ) ) {
			$classes .= ' bw-full-bleed';
		}
		return $classes;
	}

	/**
	 * Drop WordPress's own chrome padding on this plugin's screens.
	 *
	 * @return string
	 */
	private function chrome_overrides() {
		return implode(
			"\n",
			[
				'.wrap.bw-wrap { margin: 0; }',
				'body.bw-full-bleed #wpcontent { padding-left: 0; }',
				'body.bw-full-bleed #wpbody-content { padding-bottom: 0; }',
				'body.bw-full-bleed #wpfooter { display: none; }',
			]
		);
	}

	/* --- Screens ----------------------------------------------------------- */

	/**
	 * The decks dashboard.
	 *
	 * @return void
	 */
	public function render_decks() {
		$this->guard();
		Blueworx_Deck_Builder_Decks_Screen::render();
	}

	/**
	 * Support packages.
	 *
	 * @return void
	 */
	public function render_packages() {
		$this->guard();
		Blueworx_Deck_Builder_List_Screen::packages();
	}

	/**
	 * Case studies.
	 *
	 * @return void
	 */
	public function render_case_studies() {
		$this->guard();
		Blueworx_Deck_Builder_List_Screen::case_studies();
	}

	/**
	 * Content library.
	 *
	 * @return void
	 */
	public function render_library() {
		$this->guard();
		Blueworx_Deck_Builder_List_Screen::library();
	}

	/**
	 * Refuse a screen to anyone who should not see it.
	 *
	 * @return void
	 */
	private function guard() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to open this screen.', 'blueworx-labs-deck-builder' ) );
		}
	}

	/* --- Actions ------------------------------------------------------------ */

	/**
	 * Everything a row action or a form on these screens can do.
	 *
	 * All of it goes through one nonce-checked handler rather than through a
	 * screen: a screen that acted on a GET would let a link somebody clicked
	 * elsewhere archive a deck or regenerate a live client link.
	 *
	 * @return void
	 */
	public function handle_action() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'blueworx-labs-deck-builder' ) );
		}
		check_admin_referer( 'blueworx_deck_action' );

		$action = isset( $_POST['deck_action'] ) ? sanitize_key( wp_unslash( $_POST['deck_action'] ) ) : '';
		$id     = isset( $_POST['deck_id'] ) ? (int) $_POST['deck_id'] : 0;

		$redirect = Blueworx_Deck_Builder_Decks_Screen::do_action( $action, $id, $_POST );

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Make a new deck and go straight to its editor.
	 *
	 * The one action reached by a link rather than a form, because it is a
	 * menu item. It still checks the capability and a nonce, so a link
	 * somebody was sent cannot make decks on their behalf, and it creates a
	 * draft — nothing a client can see until somebody publishes it.
	 *
	 * @return void
	 */
	public function handle_create() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'blueworx-labs-deck-builder' ) );
		}
		check_admin_referer( 'blueworx_deck_create' );

		$id = Blueworx_Deck_Builder_Deck::create( [] );

		wp_safe_redirect(
			$id > 0
				? self::editor_url( Blueworx_Deck_Builder_Editor::DECK_SCREEN, $id )
				: add_query_arg( 'done', 'notmade', self::url() )
		);
		exit;
	}

	/* --- Shared furniture --------------------------------------------------- */

	/**
	 * The address of one of this plugin's screens.
	 *
	 * @param string              $page Page slug.
	 * @param array<string,mixed> $args Extra query arguments.
	 * @return string
	 */
	public static function url( $page = self::PAGE_SLUG, array $args = [] ) {
		return add_query_arg( array_merge( [ 'page' => $page ], $args ), admin_url( 'admin.php' ) );
	}

	/**
	 * The address of a record editor.
	 *
	 * @param string $screen Editor screen slug.
	 * @param int    $id     Record id.
	 * @return string
	 */
	public static function editor_url( $screen, $id ) {
		return self::url( $screen, [ 'id' => (int) $id ] );
	}

	/**
	 * Open a screen: the wrapper, the page header and the start of the body.
	 *
	 * @param string                          $eyebrow Eyebrow above the title.
	 * @param string                          $title   Page title.
	 * @param string                          $lede    One-line description.
	 * @param array<int,array<string,string>> $actions Buttons, right-aligned.
	 * @return void
	 */
	public static function open( $eyebrow, $title, $lede, array $actions = [] ) {
		?>
		<div class="wrap bw-wrap">
			<div class="bw-admin bw-page">
				<header class="bw-pagehead">
					<div class="bw-pagehead__titles">
						<p class="bw-pagehead__eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
						<h1 class="bw-pagehead__h1"><?php echo esc_html( $title ); ?></h1>
						<p class="bw-pagehead__lede"><?php echo esc_html( $lede ); ?></p>
					</div>
					<?php if ( $actions ) : ?>
						<div class="bw-pagehead__actions">
							<?php foreach ( $actions as $action ) : ?>
								<a class="bw-btn <?php echo esc_attr( $action['class'] ); ?>" href="<?php echo esc_url( $action['href'] ); ?>">
									<?php if ( ! empty( $action['icon'] ) ) : ?>
										<i class="bw-icon" data-lucide="<?php echo esc_attr( $action['icon'] ); ?>"></i>
									<?php endif; ?>
									<?php echo esc_html( $action['label'] ); ?>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</header>
				<div class="bw-page__body">
					<div class="bw-panels">
		<?php
	}

	/**
	 * Close a screen opened by open().
	 *
	 * @return void
	 */
	public static function close() {
		?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * A notice.
	 *
	 * @param string $tone  One of info, success, warning, danger.
	 * @param string $icon  Lucide icon name.
	 * @param string $title Notice title.
	 * @param string $body  Notice body.
	 * @return void
	 */
	public static function notice( $tone, $icon, $title, $body ) {
		?>
		<div class="bw-notice bw-notice--<?php echo esc_attr( $tone ); ?>">
			<i class="bw-icon bw-notice__icon" data-lucide="<?php echo esc_attr( $icon ); ?>"></i>
			<div class="bw-notice__text">
				<p class="bw-notice__title"><?php echo esc_html( $title ); ?></p>
				<p class="bw-notice__body"><?php echo esc_html( $body ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * A form that performs one action on one record, as a button.
	 *
	 * @param string              $label  Button label.
	 * @param string              $action Action name.
	 * @param int                 $id     Record id.
	 * @param string              $css    Button classes.
	 * @param array<string,mixed> $extra  Extra hidden fields.
	 * @return void
	 */
	public static function action_button( $label, $action, $id, $css = 'bw-btn bw-btn--secondary bw-btn--sm', array $extra = [] ) {
		?>
		<form class="bw-rowactions__link" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'blueworx_deck_action' ); ?>
			<input type="hidden" name="action" value="blueworx_deck_action" />
			<input type="hidden" name="deck_action" value="<?php echo esc_attr( $action ); ?>" />
			<input type="hidden" name="deck_id" value="<?php echo esc_attr( (string) $id ); ?>" />
			<?php foreach ( $extra as $name => $value ) : ?>
				<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" />
			<?php endforeach; ?>
			<button type="submit" class="<?php echo esc_attr( $css ); ?>"><?php echo esc_html( $label ); ?></button>
		</form>
		<?php
	}
}
