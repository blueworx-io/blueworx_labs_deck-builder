<?php
/**
 * The records this plugin keeps, and the lists that describe them.
 *
 * @package Blueworx\DeckBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every record is a WordPress post type, so each one gets revisions,
 * capabilities and REST without this plugin reinventing any of them. None of
 * them is public: a deck is reached through its own client link, never through
 * WordPress's own permalinks, and the other three are configuration.
 */
final class Blueworx_Deck_Builder_Types {

	const DECK       = 'bw_deck';
	const PACKAGE    = 'bw_deck_package';
	const CASE_STUDY = 'bw_case_study';
	const LIBRARY    = 'bw_library_item';

	/**
	 * How much of a day actually goes on one client's work, and how many days
	 * of that there are in a week. The timeline is worked out from estimated
	 * hours and nothing else, so these two numbers are what turns an estimate
	 * into a schedule. Four rather than eight because a working day is not
	 * eight hours of one project.
	 */
	const HOURS_PER_DAY = 4;
	const DAYS_PER_WEEK = 5;

	/**
	 * Boot.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'init', [ __CLASS__, 'types' ] );
	}

	/**
	 * Register the four post types.
	 *
	 * `show_ui` is false on all of them: this plugin builds its own lists, so
	 * WordPress's would be a second way in to the same records, styled
	 * differently and with an "Add New" that skips everything a new deck needs.
	 *
	 * @return void
	 */
	public static function types() {
		$shared = [
			'public'              => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'show_in_rest'        => false,
			'supports'            => [ 'title', 'revisions', 'author' ],
			// Behind this plugin's own capability rather than the built-in
			// post ones. Left as they came, an editor's `edit_others_posts`
			// would be enough to change any deck on the site through any route
			// that asks the post type instead of asking these screens — and
			// the page editor library asks `edit_post` on every save.
			'capability_type'     => 'blueworx_deck_record',
			'map_meta_cap'        => true,
			'capabilities'        => Blueworx_Deck_Builder_Roles::post_type_caps(),
		];

		register_post_type( self::DECK, array_merge( $shared, [ 'label' => __( 'Decks', 'blueworx-labs-deck-builder' ) ] ) );
		register_post_type( self::PACKAGE, array_merge( $shared, [ 'label' => __( 'Support packages', 'blueworx-labs-deck-builder' ) ] ) );
		register_post_type( self::CASE_STUDY, array_merge( $shared, [ 'label' => __( 'Case studies', 'blueworx-labs-deck-builder' ) ] ) );
		register_post_type( self::LIBRARY, array_merge( $shared, [ 'label' => __( 'Content library', 'blueworx-labs-deck-builder' ) ] ) );
	}

	/**
	 * The phase categories a project estimate offers.
	 *
	 * @return array<int,array<string,string>>
	 */
	public static function project_phases() {
		return self::options(
			[
				'Discovery',
				'UX and wireframes',
				'UI design',
				'Prototyping',
				'Development',
				'Integrations',
				'Migration',
				// Before testing, not after it. The client sees the finished
				// work and asks for changes here; QA then tests what they
				// actually agreed to, rather than being run twice.
				'Reviews and reverts',
				'QA and testing',
				'Launch and deployment',
				'Training and handover',
			]
		);
	}

	/**
	 * The phase categories post-launch work offers.
	 *
	 * @return array<int,array<string,string>>
	 */
	public static function postlaunch_phases() {
		return self::options(
			[
				'Launch monitoring',
				'Content updates',
				// What lands in the first weeks after go-live: the things
				// nobody could see until the site was real.
				'Post-launch updates',
				'Ongoing development',
				'Hosting and management',
				'Support and maintenance',
				// Running the work is running the relationship, and the
				// relationship carries on after launch. It sits here rather
				// than on the project estimate for that reason.
				'Project management',
				'Reporting and reviews',
				'Training',
			]
		);
	}

	/**
	 * Both phase lists at once, for the one place that cannot know which
	 * estimate a row is destined for: a content library line item, which
	 * carries its phase before it has landed anywhere.
	 *
	 * @return array<int,array<string,string>>
	 */
	public static function every_phase() {
		return array_merge( self::project_phases(), self::postlaunch_phases() );
	}

	/**
	 * One phase list as plain names, in the order the work runs. This order is
	 * the timeline: a phase's place on the schedule is where it sits here, not
	 * anything anybody sets per deck.
	 *
	 * @param string $which Either estimate or postlaunch.
	 * @return array<int,string>
	 */
	public static function phase_names( $which ) {
		$phases = Blueworx_Deck_Builder_Library::LIST_POSTLAUNCH === $which
			? self::postlaunch_phases()
			: self::project_phases();
		return array_column( $phases, 'value' );
	}

	/**
	 * The section types a deck can be built from.
	 *
	 * @return array<int,array<string,string>>
	 */
	public static function section_kinds() {
		return [
			[ 'value' => 'cover', 'label' => 'Cover' ],
			[ 'value' => 'what', 'label' => 'What we do' ],
			[ 'value' => 'service', 'label' => 'Service detail' ],
			[ 'value' => 'estimate', 'label' => 'Estimate summary' ],
			[ 'value' => 'package', 'label' => 'Recommended support package' ],
			[ 'value' => 'timeline', 'label' => 'Project timeline' ],
			[ 'value' => 'postlaunch', 'label' => 'Post-launch work' ],
			[ 'value' => 'hosting', 'label' => 'Hosting and management' ],
			[ 'value' => 'process', 'label' => 'Our process' ],
			[ 'value' => 'projects', 'label' => 'Past projects intro' ],
			[ 'value' => 'casestudy', 'label' => 'Case study' ],
			[ 'value' => 'cta', 'label' => 'Call to action' ],
		];
	}

	/**
	 * The currencies a deck can display, and how each one is written.
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function currencies() {
		return [
			'ZAR' => [ 'label' => 'Rand', 'symbol' => 'R' ],
			'USD' => [ 'label' => 'Dollar', 'symbol' => '$' ],
			'GBP' => [ 'label' => 'Pound', 'symbol' => '£' ],
			'EUR' => [ 'label' => 'Euro', 'symbol' => '€' ],
		];
	}

	/**
	 * The currencies as page editor select options.
	 *
	 * @return array<int,array<string,string>>
	 */
	public static function currency_options() {
		$out = [];
		foreach ( self::currencies() as $code => $currency ) {
			$out[] = [
				'value' => $code,
				'label' => sprintf( '%s (%s)', $currency['label'], $currency['symbol'] ),
			];
		}
		return $out;
	}

	/**
	 * Turn a list of names into value/label option pairs, keyed by the name
	 * itself — a phase is stored as what it is called, so a deck stays
	 * readable if this list is ever reordered.
	 *
	 * @param array<int,string> $names Option names.
	 * @return array<int,array<string,string>>
	 */
	private static function options( array $names ) {
		$out = [];
		foreach ( $names as $name ) {
			$out[] = [ 'value' => $name, 'label' => $name ];
		}
		return $out;
	}
}
