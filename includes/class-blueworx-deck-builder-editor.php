<?php
/**
 * Every editor screen this plugin has, declared as a schema.
 *
 * @package Blueworx\DeckBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Four record editors, all built by the shared page editor library: the deck
 * itself, a support package, a case study and a content library entry. None of
 * this file draws any markup — it says what each record holds, and the library
 * owns the shape.
 */
final class Blueworx_Deck_Builder_Editor {

	const DECK_SCREEN     = 'blueworx-deck-editor';
	const PACKAGE_SCREEN  = 'blueworx-deck-package-editor';
	const STUDY_SCREEN    = 'blueworx-deck-case-study-editor';
	const LIBRARY_SCREEN  = 'blueworx-deck-library-editor';
	const SETTINGS_SCREEN = 'blueworx-labs-deck-builder-settings';

	/**
	 * Which library panel a field belongs to. A panel cannot carry a
	 * condition — only a field can — so both panels are always on screen
	 * and every field in them says which sort of entry it is for.
	 */
	const SHOWN_FOR_SECTION   = [ 'field' => 'entry_type', 'value' => Blueworx_Deck_Builder_Library::SECTION ];
	const SHOWN_FOR_LINE_ITEM = [ 'field' => 'entry_type', 'value' => Blueworx_Deck_Builder_Library::LINE_ITEM ];

	/**
	 * Where the plugin's own settings are kept.
	 *
	 * @var string
	 */
	const OPTION = 'blueworx_deck_builder_settings';

	/**
	 * What every record here leaves off the library's Publish and settings tab.
	 *
	 * None of these records is a page of the site. A deck, a package, a case
	 * study and a library entry have no excerpt to summarise them, no comments
	 * to allow, no categories to be found by and nothing to sit underneath. The
	 * address matters — a deck's is the client link — but it is something to
	 * copy, never something to retype: changing it breaks a link already sent,
	 * and the deck's own link is minted when the deck is made.
	 *
	 * Status, publish date and author stay. Those are what publishing a record
	 * actually means, and nothing here would be better for hiding them.
	 */
	const PUBLISHING = [
		'slug'       => 'readonly',
		'excerpt'    => false,
		'comments'   => false,
		'taxonomies' => false,
		'parent'     => false,
	];

	/**
	 * Boot.
	 *
	 * @return void
	 */
	public static function register() {
		// On init, not plugins_loaded. Every label below is translated, and
		// WordPress refuses to load a text domain before init — asking anyway
		// works but logs a notice on every request and hands back the
		// untranslated string. init still runs well before admin_menu and
		// rest_api_init, which are the two hooks the library needs these
		// screens to exist by.
		add_action( 'init', [ __CLASS__, 'screens' ], 5 );
	}

	/**
	 * Register every screen.
	 *
	 * @return void
	 */
	public static function screens() {
		if ( ! class_exists( '\Blueworx\PageEditor\v1\Editor' ) ) {
			return;
		}
		\Blueworx\PageEditor\v1\Editor::register( self::deck() );
		\Blueworx\PageEditor\v1\Editor::register( self::package() );
		\Blueworx\PageEditor\v1\Editor::register( self::case_study() );
		\Blueworx\PageEditor\v1\Editor::register( self::library_item() );
		\Blueworx\PageEditor\v1\Editor::register( self::settings() );
	}

	/**
	 * One plugin setting.
	 *
	 * @param string $key      Setting key.
	 * @param mixed  $fallback Value when it has never been set.
	 * @return mixed
	 */
	public static function setting( $key, $fallback = '' ) {
		$settings = get_option( self::OPTION, [] );
		return ( is_array( $settings ) && isset( $settings[ $key ] ) && '' !== $settings[ $key ] ) ? $settings[ $key ] : $fallback;
	}

	/**
	 * The plugin's own settings. An option screen, because these are genuinely
	 * site configuration rather than a record — the one thing that is allowed
	 * to store to options.
	 *
	 * @return array<string,mixed>
	 */
	private static function settings() {
		return [
			'slug'        => self::SETTINGS_SCREEN,
			'title'       => __( 'Deck Builder settings', 'blueworx-labs-deck-builder' ),
			'menu_title'  => __( 'Settings', 'blueworx-labs-deck-builder' ),
			'parent'      => Blueworx_Deck_Builder_Admin::PAGE_SLUG,
			'eyebrow'     => 'Deck Builder · Settings',
			'lede'        => __( 'How new decks start, and what a client link does.', 'blueworx-labs-deck-builder' ),
			'store'       => 'option',
			'option_name' => self::OPTION,
			'capability'  => Blueworx_Deck_Builder_Admin::CAPABILITY,
			'tabs'        => [
				[
					'id'     => 'settings',
					'label'  => __( 'Settings', 'blueworx-labs-deck-builder' ),
					'panels' => [
						[
							'id'      => 'defaults',
							'eyebrow' => 'Decks · Defaults',
							'title'   => __( 'New decks', 'blueworx-labs-deck-builder' ),
							'note'    => __( 'A new deck copies the whole content library. This is what it cannot get from there.', 'blueworx-labs-deck-builder' ),
							'fields'  => [
								[ 'id' => 'default_currency', 'kind' => 'select', 'label' => __( 'Default currency', 'blueworx-labs-deck-builder' ), 'options' => Blueworx_Deck_Builder_Types::currency_options() ],
								[ 'id' => 'contact_email', 'kind' => 'text', 'label' => __( 'Contact address', 'blueworx-labs-deck-builder' ), 'format' => 'email', 'help' => __( 'Shown on the last slide of every deck.', 'blueworx-labs-deck-builder' ) ],
							],
						],
						[
							'id'      => 'links',
							'eyebrow' => 'Decks · Client links',
							'title'   => __( 'Client links', 'blueworx-labs-deck-builder' ),
							'note'    => __( 'These apply to every deck. A single deck can still turn its own link off.', 'blueworx-labs-deck-builder' ),
							'fields'  => [
								[ 'id' => 'noindex', 'kind' => 'toggle', 'label' => __( 'Keep decks out of search engines', 'blueworx-labs-deck-builder' ), 'help' => __( 'Adds a noindex header and leaves decks out of the sitemap. Leave this on unless you have a reason not to.', 'blueworx-labs-deck-builder' ), 'default' => true ],
							],
						],
					],
				],
			],
		];
	}

	/**
	 * Which deck is open, read from the address the editor was reached by.
	 *
	 * The recommendation panel and the share tab both describe a particular
	 * deck, and a schema is built per request, so this is how they know which
	 * one. Nothing here decides anything: the library re-checks the id, the
	 * post type and the current user's permission on it before a single value
	 * is read or written.
	 *
	 * @return Blueworx_Deck_Builder_Deck|null
	 */
	private static function open_deck() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reading which record is on screen, not changing anything.
		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		return $id > 0 ? Blueworx_Deck_Builder_Deck::find( $id ) : null;
	}

	/* --- The deck ---------------------------------------------------------- */

	/**
	 * The deck editor: seven tabs, one record, one save bar.
	 *
	 * @return array<string,mixed>
	 */
	private static function deck() {
		$deck = self::open_deck();

		return [
			'slug'       => self::DECK_SCREEN,
			// The open deck's own title is the page title, the way the design
			// asks for it. It falls back to a generic label for the menu item,
			// which is built once and cannot know which deck is open.
			'title'      => null === $deck || '' === $deck->title() ? __( 'Edit deck', 'blueworx-labs-deck-builder' ) : $deck->title(),
			'menu_title' => __( 'Edit deck', 'blueworx-labs-deck-builder' ),
			'parent'     => Blueworx_Deck_Builder_Admin::PAGE_SLUG,
			'eyebrow'    => null === $deck ? __( 'Deck Builder', 'blueworx-labs-deck-builder' ) : sprintf( 'Deck Builder · %s', $deck->client_name() ),
			'lede'       => __( 'Everything this client will see. Nothing changes on the client link until you save.', 'blueworx-labs-deck-builder' ),
			'post_type'  => Blueworx_Deck_Builder_Types::DECK,
			'capability' => Blueworx_Deck_Builder_Admin::CAPABILITY,
			'publishing' => self::PUBLISHING,
			'summary'    => [
				[ 'id' => 'project', 'label' => __( 'Project estimate', 'blueworx-labs-deck-builder' ), 'sum' => 'estimate.hours', 'where' => 'estimate.in_total', 'suffix' => 'hrs', 'foot' => __( 'Work required before launch', 'blueworx-labs-deck-builder' ) ],
				[ 'id' => 'postlaunch', 'label' => __( 'Post-launch work', 'blueworx-labs-deck-builder' ), 'sum' => 'postlaunch.hours', 'where' => 'postlaunch.in_total', 'suffix' => 'hrs', 'foot' => __( 'Work planned after launch', 'blueworx-labs-deck-builder' ) ],
				// One cell, not two. The recommendation is worked out from the
				// project work and the work after launch together, so somebody
				// reading two figures side by side would be adding them up in
				// their head to check the panel below.
				[
					'id'     => 'in_package',
					'label'  => __( 'In package calculation', 'blueworx-labs-deck-builder' ),
					'sum'    => [ 'estimate.hours', 'postlaunch.hours' ],
					'where'  => [ 'estimate.in_package', 'postlaunch.in_package' ],
					'suffix' => 'hrs',
					'foot'   => __( 'What the recommendation covers', 'blueworx-labs-deck-builder' ),
				],
				[ 'id' => 'phases', 'label' => __( 'Timeline phases', 'blueworx-labs-deck-builder' ), 'count' => 'timeline', 'foot' => __( 'On the client timeline', 'blueworx-labs-deck-builder' ) ],
			],
			'tabs'       => [
				self::deck_overview_tab(),
				self::deck_sections_tab(),
				self::deck_estimate_tab(),
				self::deck_timeline_tab( $deck ),
				self::deck_postlaunch_tab(),
				self::deck_package_tab( $deck ),
				self::deck_share_tab( $deck ),
			],
		];
	}

	/**
	 * Overview: who the deck is for, and what it is called.
	 *
	 * @return array<string,mixed>
	 */
	private static function deck_overview_tab() {
		return [
			'id'     => 'overview',
			'label'  => __( 'Overview', 'blueworx-labs-deck-builder' ),
			'panels' => [
				[
					'id'      => 'details',
					'eyebrow' => 'Cover · Deck details',
					'title'   => __( 'Deck details', 'blueworx-labs-deck-builder' ),
					'note'    => __( 'Shown on the cover of the client deck.', 'blueworx-labs-deck-builder' ),
					'fields'  => [
						[ 'id' => 'post_title', 'kind' => 'title', 'label' => __( 'Deck title', 'blueworx-labs-deck-builder' ), 'required' => true, 'help' => __( 'The headline on the cover slide.', 'blueworx-labs-deck-builder' ) ],
						[ 'id' => 'client', 'kind' => 'text', 'label' => __( 'Client or organisation', 'blueworx-labs-deck-builder' ), 'required' => true ],
						[ 'id' => 'subtitle', 'kind' => 'textarea', 'label' => __( 'Supporting statement', 'blueworx-labs-deck-builder' ), 'wide' => true, 'help' => __( 'One sentence, under the title on the cover.', 'blueworx-labs-deck-builder' ) ],
						[ 'id' => 'prepared_for', 'kind' => 'text', 'label' => __( 'Prepared for', 'blueworx-labs-deck-builder' ) ],
						[ 'id' => 'prepared_date', 'kind' => 'date', 'label' => __( 'Prepared date', 'blueworx-labs-deck-builder' ), 'help' => __( 'Week 1 of the timeline counts forward from this date.', 'blueworx-labs-deck-builder' ) ],
						[ 'id' => 'currency', 'kind' => 'select', 'label' => __( 'Display currency', 'blueworx-labs-deck-builder' ), 'options' => Blueworx_Deck_Builder_Types::currency_options(), 'help' => __( 'Used for every package price this client sees.', 'blueworx-labs-deck-builder' ) ],
						[ 'id' => 'logo', 'kind' => 'media', 'label' => __( 'Client logo', 'blueworx-labs-deck-builder' ), 'help' => __( 'PNG or SVG, at least 320px wide.', 'blueworx-labs-deck-builder' ) ],
					],
				],
				[
					'id'      => 'studies',
					'eyebrow' => 'Past projects · Case studies',
					'title'   => __( 'Past projects shown to this client', 'blueworx-labs-deck-builder' ),
					'note'    => __( 'Pick the work closest to this client\'s sector. Order follows the section list.', 'blueworx-labs-deck-builder' ),
					'fields'  => [
						[ 'id' => 'case_studies', 'kind' => 'checkboxes', 'label' => __( 'Case studies', 'blueworx-labs-deck-builder' ), 'options' => self::record_options( Blueworx_Deck_Builder_Types::CASE_STUDY ), 'wide' => true ],
					],
				],
			],
		];
	}

	/**
	 * Sections: every section the library holds, for this client.
	 *
	 * @return array<string,mixed>
	 */
	private static function deck_sections_tab() {
		return [
			'id'     => 'sections',
			'label'  => __( 'Sections', 'blueworx-labs-deck-builder' ),
			'panels' => [
				[
					'id'      => 'order',
					'eyebrow' => 'Client deck · Sections',
					'title'   => __( 'Sections', 'blueworx-labs-deck-builder' ),
					'note'    => __( 'Every section in the content library, copied for this client. A deck always presents them in this order — turn one off to leave it out without losing what it says.', 'blueworx-labs-deck-builder' ),
					'fields'  => [
						[
							'id'     => 'sections',
							'kind'   => 'repeater',
							'label'  => __( 'Sections', 'blueworx-labs-deck-builder' ),
							// The list is the library's, and the order is the
							// presentation's. Both are settled before this
							// screen opens, so neither is offered here.
							'fixed'  => true,
							'fields' => [
								[ 'id' => 'title', 'kind' => 'text', 'label' => __( 'Name', 'blueworx-labs-deck-builder' ) ],
								[ 'id' => 'kind', 'kind' => 'select', 'label' => __( 'Type', 'blueworx-labs-deck-builder' ), 'options' => Blueworx_Deck_Builder_Types::section_kinds() ],
								[ 'id' => 'eyebrow', 'kind' => 'text', 'label' => __( 'Eyebrow', 'blueworx-labs-deck-builder' ) ],
								[ 'id' => 'body', 'kind' => 'textarea', 'label' => __( 'Body', 'blueworx-labs-deck-builder' ) ],
								[ 'id' => 'points', 'kind' => 'textarea', 'label' => __( 'Key points', 'blueworx-labs-deck-builder' ) ],
								[ 'id' => 'hours', 'kind' => 'number', 'label' => __( 'Hours shown', 'blueworx-labs-deck-builder' ) ],
								[ 'id' => 'strap', 'kind' => 'text', 'label' => __( 'Strapline', 'blueworx-labs-deck-builder' ) ],
								[ 'id' => 'note', 'kind' => 'text', 'label' => __( 'Builder note', 'blueworx-labs-deck-builder' ) ],
								[ 'id' => 'visible', 'kind' => 'toggle', 'label' => __( 'Shown to the client', 'blueworx-labs-deck-builder' ) ],
							],
						],
					],
				],
			],
		];
	}

	/**
	 * Project estimate.
	 *
	 * @return array<string,mixed>
	 */
	private static function deck_estimate_tab() {
		return [
			'id'     => 'estimate',
			'label'  => __( 'Project estimate', 'blueworx-labs-deck-builder' ),
			'panels' => [
				[
					'id'      => 'work',
					'eyebrow' => 'Estimate · Before launch',
					'title'   => __( 'Work required before launch', 'blueworx-labs-deck-builder' ),
					'note'    => __( 'Every project line item in the content library, copied for this client. Rows fall under their phase, and each phase carries its own subtotal. Only rows with the package switch on count towards the recommendation.', 'blueworx-labs-deck-builder' ),
					'fields'  => [ self::line_items( 'estimate', Blueworx_Deck_Builder_Types::project_phases() ) ],
				],
			],
		];
	}

	/**
	 * Post-launch estimate. Structurally identical to the project estimate and
	 * deliberately so — the same UI, separate data, separate totals.
	 *
	 * @return array<string,mixed>
	 */
	private static function deck_postlaunch_tab() {
		return [
			'id'     => 'postlaunch',
			'label'  => __( 'Post-launch', 'blueworx-labs-deck-builder' ),
			'panels' => [
				[
					'id'      => 'after',
					'eyebrow' => 'Estimate · After launch',
					'title'   => __( 'Work planned after launch', 'blueworx-labs-deck-builder' ),
					'note'    => __( 'Every post-launch line item in the content library, copied for this client. This is the ongoing work they should expect once the site is live.', 'blueworx-labs-deck-builder' ),
					'fields'  => [ self::line_items( 'postlaunch', Blueworx_Deck_Builder_Types::postlaunch_phases() ) ],
				],
			],
		];
	}

	/**
	 * One estimate list. Both tabs use this: the two lists are structurally
	 * identical, and only their data and their totals are separate.
	 *
	 * @param string                          $id     Field id.
	 * @param array<int,array<string,string>> $phases Phase options.
	 * @return array<string,mixed>
	 */
	private static function line_items( $id, array $phases ) {
		return [
			'id'                => $id,
			'kind'              => 'repeater',
			'label'             => __( 'Line items', 'blueworx-labs-deck-builder' ),
			// The library says which rows exist. Here, a row is included or it
			// is not, and its wording is this client's.
			'fixed'             => true,
			'group_by'          => 'phase',
			'subtotal_of'       => 'hours',
			'subtotal_suffix'   => 'hrs',
			'group_empty_label' => __( 'Not assigned to a phase', 'blueworx-labs-deck-builder' ),
			'fields'            => [
				[ 'id' => 'title', 'kind' => 'text', 'label' => __( 'Work item', 'blueworx-labs-deck-builder' ) ],
				[ 'id' => 'desc', 'kind' => 'text', 'label' => __( 'Description', 'blueworx-labs-deck-builder' ) ],
				[ 'id' => 'phase', 'kind' => 'select', 'label' => __( 'Phase', 'blueworx-labs-deck-builder' ), 'options' => $phases ],
				[ 'id' => 'hours', 'kind' => 'number', 'label' => __( 'Hours', 'blueworx-labs-deck-builder' ), 'min' => 0 ],
				[ 'id' => 'note', 'kind' => 'text', 'label' => __( 'Internal note', 'blueworx-labs-deck-builder' ) ],
				[ 'id' => 'in_total', 'kind' => 'toggle', 'label' => __( 'In total', 'blueworx-labs-deck-builder' ) ],
				[ 'id' => 'show_client', 'kind' => 'toggle', 'label' => __( 'Shown to client', 'blueworx-labs-deck-builder' ) ],
				[ 'id' => 'in_package', 'kind' => 'toggle', 'label' => __( 'In package calculation', 'blueworx-labs-deck-builder' ) ],
			],
		];
	}

	/**
	 * Timeline.
	 *
	 * @param Blueworx_Deck_Builder_Deck|null $deck Open deck.
	 * @return array<string,mixed>
	 */
	private static function deck_timeline_tab( $deck ) {
		return [
			'id'     => 'timeline',
			'label'  => __( 'Timeline', 'blueworx-labs-deck-builder' ),
			'panels' => [
				[
					'id'      => 'plan',
					'eyebrow' => 'Client deck · Schedule',
					'title'   => __( 'Project timeline', 'blueworx-labs-deck-builder' ),
					'note'    => __( 'Dates are never worked out from estimated hours — set them to match the team\'s real availability.', 'blueworx-labs-deck-builder' ),
					'fields'  => [
						[
							'id'     => 'timeline',
							'kind'   => 'gantt',
							'label'  => __( 'Phases', 'blueworx-labs-deck-builder' ),
							// A project runs discovery to growth, in that
							// order, every time. The weeks are per client; the
							// running order is not.
							'fixed'  => true,
							'origin' => null === $deck ? '' : (string) $deck->get( 'prepared_date' ),
							'help'   => __( 'The launch milestone separates project work from post-launch work. Exactly one phase may be it.', 'blueworx-labs-deck-builder' ),
						],
					],
				],
			],
		];
	}

	/**
	 * Support package: the recommendation, and the two things that can change it.
	 *
	 * @param Blueworx_Deck_Builder_Deck|null $deck Open deck.
	 * @return array<string,mixed>
	 */
	private static function deck_package_tab( $deck ) {
		return [
			'id'     => 'package',
			'label'  => __( 'Support package', 'blueworx-labs-deck-builder' ),
			'panels' => [
				[
					'id'      => 'recommendation',
					'eyebrow' => 'Support · Recommendation',
					'title'   => null === $deck ? __( 'Recommended package', 'blueworx-labs-deck-builder' ) : self::recommendation_title( $deck ),
					'note'    => null === $deck ? '' : $deck->recommendation()['reason'],
					'fields'  => [
						[
							'id'    => 'calculation',
							'kind'  => 'facts',
							'label' => __( 'How this was worked out', 'blueworx-labs-deck-builder' ),
							'rows'  => self::recommendation_facts( $deck ),
							'help'  => __( 'Worked out when this screen opened. Save to bring it up to date after changing hours.', 'blueworx-labs-deck-builder' ),
						],
						[ 'id' => 'override', 'kind' => 'select', 'label' => __( 'Manual override', 'blueworx-labs-deck-builder' ), 'options' => Blueworx_Deck_Builder_Packages::options( __( 'Use the automatic recommendation', 'blueworx-labs-deck-builder' ) ), 'help' => __( 'An override is marked here but shows to the client as the selected recommendation.', 'blueworx-labs-deck-builder' ) ],
						[ 'id' => 'alternatives', 'kind' => 'checkboxes', 'label' => __( 'Shown for comparison', 'blueworx-labs-deck-builder' ), 'options' => Blueworx_Deck_Builder_Packages::options(), 'wide' => true, 'help' => __( 'Alternatives sit alongside the recommendation, with less emphasis.', 'blueworx-labs-deck-builder' ) ],
					],
				],
			],
		];
	}

	/**
	 * Preview and share.
	 *
	 * @param Blueworx_Deck_Builder_Deck|null $deck Open deck.
	 * @return array<string,mixed>
	 */
	private static function deck_share_tab( $deck ) {
		return [
			'id'     => 'share',
			'label'  => __( 'Preview and share', 'blueworx-labs-deck-builder' ),
			'panels' => [
				[
					'id'      => 'preview',
					'eyebrow' => 'Client · Preview',
					'title'   => __( 'What the client sees', 'blueworx-labs-deck-builder' ),
					'note'    => __( 'The deck on its own client link. Save first — the frame shows what has been saved, not what is on screen.', 'blueworx-labs-deck-builder' ),
					'fields'  => [
						[
							'id'    => 'client_preview',
							'kind'  => 'preview',
							'label' => __( 'Client deck', 'blueworx-labs-deck-builder' ),
							// A draft deck frames too. The client link already
							// shows an unpublished deck to somebody who may
							// edit it and a not-found page to everybody else,
							// so this previews the real page rather than an
							// approximation of it — and a deck can be checked
							// before it is ever published, which is the whole
							// point of a preview. Archived or disabled is the
							// one case with nothing to show: that page is gone
							// for the editor too, and framing the not-found it
							// returns would read as the deck being broken.
							'url'   => null === $deck || ! self::previewable( $deck ) ? '' : $deck->link(),
							'help'  => null === $deck ? '' : self::link_help( $deck ),
						],
					],
				],
				[
					'id'      => 'link',
					'eyebrow' => 'Client · Link',
					'title'   => __( 'The client link', 'blueworx-labs-deck-builder' ),
					'note'    => __( 'No WordPress login needed. Excluded from search engines and sitemaps, and it carries no record ids.', 'blueworx-labs-deck-builder' ),
					'fields'  => [
						// A copytext field is display-only: nothing is ever written
						// back for it, so it reads its default every time. That
						// makes the default the right place for the link — it is
						// worked out from the deck's slug and this site's own
						// address on each request, rather than stored as a second
						// copy that goes stale the day the site moves domain.
						[
							'id'      => 'client_link',
							'kind'    => 'copytext',
							'label'   => __( 'Client link', 'blueworx-labs-deck-builder' ),
							'default' => null === $deck ? '' : $deck->link(),
							'help'    => null === $deck ? '' : self::link_help( $deck ),
						],
						[ 'id' => 'link_enabled', 'kind' => 'toggle', 'label' => __( 'Link enabled', 'blueworx-labs-deck-builder' ), 'help' => __( 'Turning this off returns a not-found page.', 'blueworx-labs-deck-builder' ) ],
						[ 'id' => 'password_on', 'kind' => 'toggle', 'label' => __( 'Password protection', 'blueworx-labs-deck-builder' ), 'help' => __( 'Asks for a password before the deck loads.', 'blueworx-labs-deck-builder' ) ],
						[ 'id' => 'password', 'kind' => 'text', 'label' => __( 'Deck password', 'blueworx-labs-deck-builder' ), 'help' => __( 'Send this separately from the link.', 'blueworx-labs-deck-builder' ), 'depends_on' => [ 'field' => 'password_on', 'value' => true ] ],
						[ 'id' => 'archived', 'kind' => 'toggle', 'label' => __( 'Archived', 'blueworx-labs-deck-builder' ), 'help' => __( 'An archived deck keeps its content, but its client link stops working.', 'blueworx-labs-deck-builder' ) ],
					],
				],
				[
					'id'      => 'privacy',
					'eyebrow' => 'Client · Privacy',
					'title'   => __( 'What the client is shown', 'blueworx-labs-deck-builder' ),
					'note'    => __( 'Filtered on the server, not hidden in the page.', 'blueworx-labs-deck-builder' ),
					'fields'  => [
						[
							'id'    => 'exposure',
							'kind'  => 'facts',
							'label' => __( 'What is exposed', 'blueworx-labs-deck-builder' ),
							'rows'  => self::exposure_facts( $deck ),
						],
					],
				],
			],
		];
	}

	/**
	 * The recommendation panel's title.
	 *
	 * @param Blueworx_Deck_Builder_Deck $deck Open deck.
	 * @return string
	 */
	private static function recommendation_title( Blueworx_Deck_Builder_Deck $deck ) {
		$recommendation = $deck->recommendation();
		if ( null !== $recommendation['package'] ) {
			return $recommendation['package']['name'];
		}
		return 'CUSTOM' === $recommendation['state']
			? __( 'Custom package required', 'blueworx-labs-deck-builder' )
			: __( 'No eligible package', 'blueworx-labs-deck-builder' );
	}

	/**
	 * The recommendation, as the four figures the design shows plus the state.
	 *
	 * @param Blueworx_Deck_Builder_Deck|null $deck Open deck.
	 * @return array<int,array<string,string>>
	 */
	private static function recommendation_facts( $deck ) {
		if ( null === $deck ) {
			return [ [ 'label' => __( 'Deck', 'blueworx-labs-deck-builder' ), 'value' => __( 'Open a deck to see its recommendation.', 'blueworx-labs-deck-builder' ) ] ];
		}

		$recommendation = $deck->recommendation();
		$package        = $recommendation['package'];
		$states         = [
			'OK'       => __( 'This package covers the calculated work', 'blueworx-labs-deck-builder' ),
			'EXACT'    => __( 'This package covers the calculated work exactly', 'blueworx-labs-deck-builder' ),
			'OVERRIDE' => __( 'Chosen by hand, not by the rule', 'blueworx-labs-deck-builder' ),
			'CUSTOM'   => __( 'Above the largest package — flagged for manual review', 'blueworx-labs-deck-builder' ),
			'NONE'     => __( 'No package can be recommended until one has hours set', 'blueworx-labs-deck-builder' ),
		];

		return [
			[ 'label' => __( 'State', 'blueworx-labs-deck-builder' ), 'value' => $states[ $recommendation['state'] ] ?? $recommendation['state'] ],
			[ 'label' => __( 'In calculation', 'blueworx-labs-deck-builder' ), 'value' => Blueworx_Deck_Builder_Packages::hours( $recommendation['total'] ) . ' hrs' ],
			[ 'label' => __( 'Package hours', 'blueworx-labs-deck-builder' ), 'value' => null === $package ? '—' : Blueworx_Deck_Builder_Packages::hours( $package['hours'] ) . ' hrs' ],
			[ 'label' => __( 'Remaining capacity', 'blueworx-labs-deck-builder' ), 'value' => null === $recommendation['remaining'] ? '—' : Blueworx_Deck_Builder_Packages::hours( $recommendation['remaining'] ) . ' hrs' ],
			[ 'label' => __( 'Price', 'blueworx-labs-deck-builder' ), 'value' => self::price_line( $package, $deck->currency() ) ],
		];
	}

	/**
	 * A package's price, with its period and currency written out.
	 *
	 * @param array<string,mixed>|null $package  Package.
	 * @param string                   $currency Currency code.
	 * @return string
	 */
	private static function price_line( $package, $currency ) {
		if ( null === $package ) {
			return '—';
		}
		$price = Blueworx_Deck_Builder_Packages::price( $package, $currency );
		if ( null === $price ) {
			return sprintf(
				/* translators: %s: currency code. */
				__( 'No price set in %s', 'blueworx-labs-deck-builder' ),
				$currency
			);
		}
		return trim( $price . ' ' . $package['period'] . ' · ' . $currency );
	}

	/**
	 * What the client link does and does not carry.
	 *
	 * @param Blueworx_Deck_Builder_Deck|null $deck Open deck.
	 * @return array<int,array<string,string>>
	 */
	private static function exposure_facts( $deck ) {
		$hidden_items = 0;
		$hidden_notes = 0;
		if ( null !== $deck ) {
			foreach ( [ 'estimate', 'postlaunch' ] as $list ) {
				foreach ( $deck->rows( $list ) as $row ) {
					$hidden_items += empty( $row['show_client'] ) ? 1 : 0;
					$hidden_notes += ( '' !== trim( (string) ( $row['note'] ?? '' ) ) ) ? 1 : 0;
				}
			}
		}

		return [
			[
				'label' => __( 'Line items held back', 'blueworx-labs-deck-builder' ),
				'value' => sprintf(
					/* translators: %d: number of line items. */
					_n( '%d item is not sent to the client', '%d items are not sent to the client', $hidden_items, 'blueworx-labs-deck-builder' ),
					$hidden_items
				),
			],
			[
				'label' => __( 'Internal notes', 'blueworx-labs-deck-builder' ),
				'value' => sprintf(
					/* translators: %d: number of internal notes. */
					_n( '%d internal note, never sent', '%d internal notes, never sent', $hidden_notes, 'blueworx-labs-deck-builder' ),
					$hidden_notes
				),
			],
			[ 'label' => __( 'WordPress login', 'blueworx-labs-deck-builder' ), 'value' => __( 'Not needed to open the link', 'blueworx-labs-deck-builder' ) ],
			[ 'label' => __( 'Search engines', 'blueworx-labs-deck-builder' ), 'value' => __( 'Excluded, and left out of sitemaps', 'blueworx-labs-deck-builder' ) ],
			[ 'label' => __( 'Record ids', 'blueworx-labs-deck-builder' ), 'value' => __( 'None in the link', 'blueworx-labs-deck-builder' ) ],
		];
	}

	/**
	 * Whether there is a page to frame at all.
	 *
	 * Not the same question as whether the link is live: an unpublished deck
	 * has a page that its own editors can open, which is exactly what a
	 * preview is for. An archived or disabled one has nothing, for anybody.
	 *
	 * @param Blueworx_Deck_Builder_Deck $deck Open deck.
	 * @return bool
	 */
	private static function previewable( Blueworx_Deck_Builder_Deck $deck ) {
		return 'archived' !== $deck->status()
			&& (bool) $deck->get( 'link_enabled', true )
			&& '' !== $deck->link();
	}

	/**
	 * What the help line under the client link says, which depends entirely on
	 * whether that link works right now.
	 *
	 * @param Blueworx_Deck_Builder_Deck $deck Open deck.
	 * @return string
	 */
	private static function link_help( Blueworx_Deck_Builder_Deck $deck ) {
		if ( 'archived' === $deck->status() ) {
			return __( 'This deck is archived, so the link returns a not-found page.', 'blueworx-labs-deck-builder' );
		}
		if ( ! $deck->get( 'link_enabled', true ) ) {
			return __( 'This link is disabled and returns a not-found page.', 'blueworx-labs-deck-builder' );
		}
		if ( 'published' !== $deck->status() ) {
			return __( 'The link starts working when you publish this deck.', 'blueworx-labs-deck-builder' );
		}
		return __( 'Live. Anyone with this link can open the deck.', 'blueworx-labs-deck-builder' );
	}

	/* --- The other three records ------------------------------------------- */

	/**
	 * A support package.
	 *
	 * @return array<string,mixed>
	 */
	private static function package() {
		$prices = [];
		foreach ( Blueworx_Deck_Builder_Types::currencies() as $code => $currency ) {
			$prices[] = [
				'id'    => 'price_' . strtolower( $code ),
				'kind'  => 'number',
				'label' => sprintf( '%s · %s', $currency['label'], $code ),
				'min'   => 0,
				'help'  => sprintf(
					/* translators: %s: currency symbol. */
					__( 'Shown as %s. A deck can only display a currency that has a price here.', 'blueworx-labs-deck-builder' ),
					$currency['symbol']
				),
			];
		}

		return [
			'slug'       => self::PACKAGE_SCREEN,
			'title'      => __( 'Edit support package', 'blueworx-labs-deck-builder' ),
			'parent'     => Blueworx_Deck_Builder_Admin::PAGE_SLUG,
			'eyebrow'    => 'Deck Builder · Support packages',
			'lede'       => __( 'Set this package up once. Every deck that recommends it uses what is here.', 'blueworx-labs-deck-builder' ),
			'post_type'  => Blueworx_Deck_Builder_Types::PACKAGE,
			'capability' => Blueworx_Deck_Builder_Admin::CAPABILITY,
			'publishing' => self::PUBLISHING,
			'tabs'       => [
				[
					'id'     => 'package',
					'label'  => __( 'Package', 'blueworx-labs-deck-builder' ),
					'panels' => [
						[
							'id'      => 'basics',
							'eyebrow' => 'Package · Basics',
							'title'   => __( 'What this package is', 'blueworx-labs-deck-builder' ),
							'note'    => __( 'A package with no included hours can never be recommended.', 'blueworx-labs-deck-builder' ),
							'fields'  => [
								[ 'id' => 'post_title', 'kind' => 'title', 'label' => __( 'Package name', 'blueworx-labs-deck-builder' ), 'required' => true ],
								[ 'id' => 'hours', 'kind' => 'number', 'label' => __( 'Included hours', 'blueworx-labs-deck-builder' ), 'min' => 0, 'help' => __( 'Used by the recommendation rule.', 'blueworx-labs-deck-builder' ) ],
								[ 'id' => 'period', 'kind' => 'text', 'label' => __( 'Allowance period', 'blueworx-labs-deck-builder' ), 'help' => __( 'For example, per month.', 'blueworx-labs-deck-builder' ) ],
								[ 'id' => 'commitment', 'kind' => 'text', 'label' => __( 'Minimum commitment', 'blueworx-labs-deck-builder' ) ],
								[ 'id' => 'order', 'kind' => 'number', 'label' => __( 'Display order', 'blueworx-labs-deck-builder' ), 'min' => 0, 'help' => __( 'Lower numbers come first.', 'blueworx-labs-deck-builder' ) ],
								[ 'id' => 'benefits', 'kind' => 'textarea', 'label' => __( 'What is included', 'blueworx-labs-deck-builder' ), 'wide' => true, 'help' => __( 'One benefit per line. Shown as a ticked list on the client deck.', 'blueworx-labs-deck-builder' ) ],
								[ 'id' => 'eligible', 'kind' => 'toggle', 'label' => __( 'Eligible for automatic recommendation', 'blueworx-labs-deck-builder' ) ],
								[ 'id' => 'popular', 'kind' => 'toggle', 'label' => __( 'Most popular', 'blueworx-labs-deck-builder' ) ],
							],
						],
						[
							'id'      => 'prices',
							'eyebrow' => 'Package · Price',
							'title'   => __( 'Price per currency', 'blueworx-labs-deck-builder' ),
							'note'    => __( 'Every package carries four prices. Each deck picks which one the client sees, so a package is set up once and reused everywhere.', 'blueworx-labs-deck-builder' ),
							'fields'  => $prices,
						],
					],
				],
			],
		];
	}

	/**
	 * A case study.
	 *
	 * @return array<string,mixed>
	 */
	private static function case_study() {
		return [
			'slug'       => self::STUDY_SCREEN,
			'title'      => __( 'Edit case study', 'blueworx-labs-deck-builder' ),
			'parent'     => Blueworx_Deck_Builder_Admin::PAGE_SLUG,
			'eyebrow'    => 'Deck Builder · Case studies',
			'lede'       => __( 'Past work, ready to drop into any deck.', 'blueworx-labs-deck-builder' ),
			'post_type'  => Blueworx_Deck_Builder_Types::CASE_STUDY,
			'capability' => Blueworx_Deck_Builder_Admin::CAPABILITY,
			'publishing' => self::PUBLISHING,
			'tabs'       => [
				[
					'id'     => 'study',
					'label'  => __( 'Case study', 'blueworx-labs-deck-builder' ),
					'panels' => [
						[
							'id'      => 'about',
							'eyebrow' => 'Case study · The project',
							'title'   => __( 'The project', 'blueworx-labs-deck-builder' ),
							'note'    => __( 'What this was, and who it was for.', 'blueworx-labs-deck-builder' ),
							'fields'  => [
								[ 'id' => 'post_title', 'kind' => 'title', 'label' => __( 'Project name', 'blueworx-labs-deck-builder' ), 'required' => true ],
								[ 'id' => 'number', 'kind' => 'text', 'label' => __( 'Project number', 'blueworx-labs-deck-builder' ), 'max_length' => 4 ],
								[ 'id' => 'sector', 'kind' => 'text', 'label' => __( 'Industry', 'blueworx-labs-deck-builder' ) ],
								[ 'id' => 'services', 'kind' => 'text', 'label' => __( 'Services', 'blueworx-labs-deck-builder' ), 'help' => __( 'Separate each one with a comma.', 'blueworx-labs-deck-builder' ) ],
								[ 'id' => 'summary', 'kind' => 'textarea', 'label' => __( 'Summary', 'blueworx-labs-deck-builder' ), 'wide' => true ],
								[ 'id' => 'link', 'kind' => 'text', 'label' => __( 'Website', 'blueworx-labs-deck-builder' ), 'format' => 'url' ],
							],
						],
						[
							'id'      => 'imagery',
							'eyebrow' => 'Case study · Imagery',
							'title'   => __( 'Screens', 'blueworx-labs-deck-builder' ),
							'note'    => __( 'Three shots of the same site, shown together on the slide.', 'blueworx-labs-deck-builder' ),
							'fields'  => [
								[ 'id' => 'desktop', 'kind' => 'media', 'label' => __( 'Desktop', 'blueworx-labs-deck-builder' ) ],
								[ 'id' => 'tablet', 'kind' => 'media', 'label' => __( 'Tablet', 'blueworx-labs-deck-builder' ) ],
								[ 'id' => 'mobile', 'kind' => 'media', 'label' => __( 'Mobile', 'blueworx-labs-deck-builder' ) ],
							],
						],
					],
				],
			],
		];
	}

	/**
	 * A content library entry.
	 *
	 * @return array<string,mixed>
	 */
	private static function library_item() {
		return [
			'slug'       => self::LIBRARY_SCREEN,
			'title'      => __( 'Edit library entry', 'blueworx-labs-deck-builder' ),
			'parent'     => Blueworx_Deck_Builder_Admin::PAGE_SLUG,
			'eyebrow'    => 'Deck Builder · Content library',
			'lede'       => __( 'A section or a line item every new deck starts with. Changing it here changes what the next deck begins with; decks already made keep their own copy.', 'blueworx-labs-deck-builder' ),
			'post_type'  => Blueworx_Deck_Builder_Types::LIBRARY,
			'capability' => Blueworx_Deck_Builder_Admin::CAPABILITY,
			'publishing' => self::PUBLISHING,
			'tabs'       => [
				[
					'id'     => 'entry',
					'label'  => __( 'Entry', 'blueworx-labs-deck-builder' ),
					'panels' => [
						[
							'id'      => 'entry_kind',
							'eyebrow' => 'Library · Entry',
							'title'   => __( 'What this entry is', 'blueworx-labs-deck-builder' ),
							'note'    => __( 'A section becomes a slide. A line item becomes a row on one of the two estimates. The panels below follow whichever you pick.', 'blueworx-labs-deck-builder' ),
							'fields'  => [
								[ 'id' => 'post_title', 'kind' => 'title', 'label' => __( 'Name', 'blueworx-labs-deck-builder' ), 'required' => true ],
								[
									'id'      => 'entry_type',
									'kind'    => 'select',
									'label'   => __( 'Entry type', 'blueworx-labs-deck-builder' ),
									'options' => [
										[ 'value' => Blueworx_Deck_Builder_Library::SECTION, 'label' => __( 'Section', 'blueworx-labs-deck-builder' ) ],
										[ 'value' => Blueworx_Deck_Builder_Library::LINE_ITEM, 'label' => __( 'Line item', 'blueworx-labs-deck-builder' ) ],
									],
									'default' => Blueworx_Deck_Builder_Library::SECTION,
								],
								// Every deck presents in this order, so it is
								// set once here rather than argued about per
								// deck. Lower numbers come first.
								[ 'id' => 'order', 'kind' => 'number', 'label' => __( 'Order in a deck', 'blueworx-labs-deck-builder' ), 'min' => 0, 'help' => __( 'Lower numbers come first. Entries sharing a number fall back to their name.', 'blueworx-labs-deck-builder' ) ],
							],
						],
						[
							'id'      => 'content',
							'eyebrow' => 'Library · Section',
							'title'   => __( 'The section', 'blueworx-labs-deck-builder' ),
							'note'    => __( 'What gets inserted when somebody adds this to a deck.', 'blueworx-labs-deck-builder' ),
							'fields'  => [
								[ 'depends_on' => self::SHOWN_FOR_SECTION, 'id' => 'kind', 'kind' => 'select', 'label' => __( 'Section type', 'blueworx-labs-deck-builder' ), 'options' => Blueworx_Deck_Builder_Types::section_kinds() ],
								[ 'depends_on' => self::SHOWN_FOR_SECTION, 'id' => 'note', 'kind' => 'text', 'label' => __( 'Builder note', 'blueworx-labs-deck-builder' ), 'help' => __( 'The small line under the name in the sections list.', 'blueworx-labs-deck-builder' ) ],
								[ 'depends_on' => self::SHOWN_FOR_SECTION, 'id' => 'eyebrow', 'kind' => 'text', 'label' => __( 'Eyebrow', 'blueworx-labs-deck-builder' ) ],
								[ 'depends_on' => self::SHOWN_FOR_SECTION, 'id' => 'body', 'kind' => 'textarea', 'label' => __( 'Body', 'blueworx-labs-deck-builder' ), 'wide' => true ],
								[ 'depends_on' => self::SHOWN_FOR_SECTION, 'id' => 'points', 'kind' => 'textarea', 'label' => __( 'Key points', 'blueworx-labs-deck-builder' ), 'wide' => true, 'help' => __( 'One per line.', 'blueworx-labs-deck-builder' ) ],
								[ 'depends_on' => self::SHOWN_FOR_SECTION, 'id' => 'strap', 'kind' => 'text', 'label' => __( 'Strapline', 'blueworx-labs-deck-builder' ) ],
							],
						],
						[
							'id'      => 'work',
							'eyebrow' => 'Library · Line item',
							'title'   => __( 'The line item', 'blueworx-labs-deck-builder' ),
							'note'    => __( 'Work quoted often. Its list, phase and hours come with it into every new deck; an internal note never does.', 'blueworx-labs-deck-builder' ),
							'fields'  => [
								[ 'depends_on' => self::SHOWN_FOR_LINE_ITEM, 'id' => 'desc', 'kind' => 'text', 'label' => __( 'Description', 'blueworx-labs-deck-builder' ), 'wide' => true ],
								// A deck keeps two estimates with separate
								// totals and separate phases, so a line item
								// has to say which one it is for. Without it,
								// every line item would land on both.
								[
									'depends_on' => self::SHOWN_FOR_LINE_ITEM,
									'id'         => 'list',
									'kind'       => 'select',
									'label'      => __( 'Which estimate', 'blueworx-labs-deck-builder' ),
									'options'    => [
										[ 'value' => Blueworx_Deck_Builder_Library::LIST_ESTIMATE, 'label' => __( 'Project estimate', 'blueworx-labs-deck-builder' ) ],
										[ 'value' => Blueworx_Deck_Builder_Library::LIST_POSTLAUNCH, 'label' => __( 'Post-launch work', 'blueworx-labs-deck-builder' ) ],
									],
									'default'    => Blueworx_Deck_Builder_Library::LIST_ESTIMATE,
								],
								[ 'depends_on' => self::SHOWN_FOR_LINE_ITEM, 'id' => 'phase', 'kind' => 'select', 'label' => __( 'Phase', 'blueworx-labs-deck-builder' ), 'options' => Blueworx_Deck_Builder_Types::every_phase(), 'help' => __( 'The two estimates group by their own phases. Pick one that belongs to the list above, or the row lands under "not assigned to a phase".', 'blueworx-labs-deck-builder' ) ],
								[ 'depends_on' => self::SHOWN_FOR_LINE_ITEM, 'id' => 'hours', 'kind' => 'number', 'label' => __( 'Hours', 'blueworx-labs-deck-builder' ), 'min' => 0 ],
							],
						],
					],
				],
			],
		];
	}

	/**
	 * Records of one post type as checkbox options.
	 *
	 * @param string $post_type Post type.
	 * @return array<int,array<string,string>>
	 */
	private static function record_options( $post_type ) {
		$posts = get_posts(
			[
				'post_type'   => $post_type,
				'post_status' => [ 'draft', 'publish', 'private', 'pending' ],
				'numberposts' => 100,
				'orderby'     => 'title',
				'order'       => 'ASC',
			]
		);
		$out   = [];
		foreach ( $posts as $post ) {
			$out[] = [ 'value' => (string) $post->ID, 'label' => $post->post_title ];
		}
		return $out;
	}
}
