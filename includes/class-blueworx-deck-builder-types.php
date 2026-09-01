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

	const DECK        = 'bw_deck';
	const PACKAGE     = 'bw_deck_package';
	const CASE_STUDY  = 'bw_case_study';
	const LIBRARY     = 'bw_library_item';

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
				'Research',
				'Strategy',
				'Content',
				'UX and wireframes',
				'UI design',
				'Prototyping',
				'Development',
				'Integrations',
				'Migration',
				'QA and testing',
				'Project management',
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
				'Performance optimisation',
				'Search optimisation',
				'Feature improvements',
				'Ongoing development',
				'Support and maintenance',
				'Reporting and reviews',
				'Training',
			]
		);
	}

	/**
	 * The section types a deck can be built from.
	 *
	 * @return array<int,array<string,string>>
	 */
	public static function section_kinds() {
		return [
			[ 'value' => 'cover',      'label' => 'Cover' ],
			[ 'value' => 'what',       'label' => 'What we do' ],
			[ 'value' => 'service',    'label' => 'Service detail' ],
			[ 'value' => 'estimate',   'label' => 'Estimate summary' ],
			[ 'value' => 'package',    'label' => 'Recommended support package' ],
			[ 'value' => 'timeline',   'label' => 'Project timeline' ],
			[ 'value' => 'postlaunch', 'label' => 'Post-launch work' ],
			[ 'value' => 'process',    'label' => 'Our process' ],
			[ 'value' => 'projects',   'label' => 'Past projects intro' ],
			[ 'value' => 'casestudy',  'label' => 'Case study' ],
			[ 'value' => 'cta',        'label' => 'Call to action' ],
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
