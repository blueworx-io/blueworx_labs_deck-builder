<?php
/**
 * What a fresh install begins with, and the one list a deck does not copy.
 *
 * @package Blueworx\DeckBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * A new deck is a copy of the content library — see
 * Blueworx_Deck_Builder_Library. The only thing this class still authors is
 * the timeline, which has no library behind it because a schedule is weeks and
 * milestones rather than reusable content.
 *
 * The rest of the file is the seed: the packages, case studies and library
 * entries a fresh install needs before anything here is usable at all.
 */
final class Blueworx_Deck_Builder_Starter {

	/**
	 * Load a new deck's starting content.
	 *
	 * @param int $deck_id Deck post id.
	 * @return void
	 */
	public static function load_into( $deck_id ) {
		foreach ( Blueworx_Deck_Builder_Library::starting_lists() as $list => $rows ) {
			update_post_meta( $deck_id, 'bw_deck_' . $list, $rows );
		}
		update_post_meta( $deck_id, 'bw_deck_timeline', self::timeline() );
	}

	/**
	 * The default timeline. Weeks are authored, never derived from estimated
	 * hours — a schedule is what the team can actually do.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function timeline() {
		$rows = [
			[ 'Discovery and research', 1, 2, '', 'pre', 'Understanding the business and its audience.' ],
			[ 'UX and content', 2, 4, '', 'pre', 'Structure and words, before any design.' ],
			[ 'UI design', 4, 7, 'Design sign-off', 'pre', 'The look of every template, agreed.' ],
			[ 'Development', 7, 13, '', 'pre', 'Building the site.' ],
			[ 'QA and UAT', 13, 15, '', 'pre', 'Testing, and your team signing it off.' ],
			[ 'Launch', 15, 15, 'Launch', 'launch', 'The site goes live.' ],
			[ 'Launch monitoring', 16, 18, '', 'post', 'Watching closely while real traffic arrives.' ],
			[ 'Optimisation and growth', 18, 26, '', 'post', 'Improving what the data shows is worth improving.' ],
		];

		$out = [];
		foreach ( $rows as $row ) {
			$out[] = [
				'title'     => $row[0],
				'start'     => $row[1],
				'end'       => $row[2],
				'milestone' => $row[3],
				'kind'      => $row[4],
				'desc'      => $row[5],
				'visible'   => true,
			];
		}
		return $out;
	}

	/**
	 * Seed the packages, case studies and library entries a fresh install
	 * needs to be usable at all. Runs once, on activation, and never
	 * overwrites anything already there — a site that has been set up is not
	 * a site that wants its packages reset.
	 *
	 * @return void
	 */
	public static function seed() {
		if ( ! get_posts( [ 'post_type' => Blueworx_Deck_Builder_Types::PACKAGE, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ] ) ) {
			self::seed_packages();
		}
		if ( ! get_posts( [ 'post_type' => Blueworx_Deck_Builder_Types::CASE_STUDY, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ] ) ) {
			self::seed_case_studies();
		}
		if ( ! get_posts( [ 'post_type' => Blueworx_Deck_Builder_Types::LIBRARY, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ] ) ) {
			self::seed_library();
		}
	}

	/**
	 * Five packages, so the recommendation rule has something to work with on
	 * day one.
	 *
	 * @return void
	 */
	private static function seed_packages() {
		$packages = [
			[ 'Care', 120, 1, true, false, [ 14000, 750, 600, 700 ], "Monthly updates and backups\nSecurity monitoring\nEmail support" ],
			[ 'Core', 240, 2, true, false, [ 26000, 1400, 1100, 1300 ], "Everything in Care\nContent changes each month\nPerformance reporting" ],
			[ 'Core Plus', 360, 3, true, true, [ 38000, 2050, 1600, 1900 ], "Everything in Core\nOngoing improvements\nQuarterly strategy review" ],
			[ 'Partner', 480, 4, true, false, [ 49000, 2650, 2100, 2450 ], "Everything in Core Plus\nA named lead\nPriority response" ],
		];

		foreach ( $packages as $package ) {
			$id = wp_insert_post(
				[
					'post_type'   => Blueworx_Deck_Builder_Types::PACKAGE,
					'post_status' => 'publish',
					'post_title'  => $package[0],
				]
			);
			if ( is_wp_error( $id ) || ! $id ) {
				continue;
			}
			update_post_meta( $id, 'bw_deck_package_hours', $package[1] );
			update_post_meta( $id, 'bw_deck_package_order', $package[2] );
			update_post_meta( $id, 'bw_deck_package_eligible', $package[3] );
			update_post_meta( $id, 'bw_deck_package_popular', $package[4] );
			update_post_meta( $id, 'bw_deck_package_period', 'per month' );
			update_post_meta( $id, 'bw_deck_package_commitment', '6 month minimum' );
			update_post_meta( $id, 'bw_deck_package_benefits', $package[6] );
			$codes = array_keys( Blueworx_Deck_Builder_Types::currencies() );
			foreach ( $codes as $index => $code ) {
				update_post_meta( $id, 'bw_deck_package_price_' . strtolower( $code ), $package[5][ $index ] );
			}
		}
	}

	/**
	 * Three case studies.
	 *
	 * @return void
	 */
	private static function seed_case_studies() {
		$studies = [
			[ '01', 'Hiraste', 'Travel and accommodation', 'Design, development, hosting', 'A booking-led site for a group of properties, built so the team can add a new property without us.' ],
			[ '02', 'PadlX', 'Sports and community', 'Design, development, support', 'A club platform with membership, court booking and a public league table.' ],
			[ '03', 'CAN SAKHARA', 'Luxury villa', 'Design, development', 'A single-property site where the photography does the selling.' ],
		];

		foreach ( $studies as $study ) {
			$id = wp_insert_post(
				[
					'post_type'   => Blueworx_Deck_Builder_Types::CASE_STUDY,
					'post_status' => 'publish',
					'post_title'  => $study[1],
				]
			);
			if ( is_wp_error( $id ) || ! $id ) {
				continue;
			}
			update_post_meta( $id, 'bw_case_study_number', $study[0] );
			update_post_meta( $id, 'bw_case_study_sector', $study[2] );
			update_post_meta( $id, 'bw_case_study_services', $study[3] );
			update_post_meta( $id, 'bw_case_study_summary', $study[4] );
		}
	}

	/**
	 * Everything a deck is made of, written down once.
	 *
	 * This is the standard retainer deck: it used to be a second, hardcoded
	 * list that only a "retainer" deck ever saw, which meant the library and
	 * the deck disagreed about what the business actually offers. There is one
	 * list now, and it is this one.
	 *
	 * @return void
	 */
	private static function seed_library() {
		// Order is the order of the presentation, so it is written here rather
		// than left to the title — alphabetical would open every deck on the
		// call to action.
		$sections = [
			[ 'Cover', 'cover', 'Integrated', 'The opening slide: client, title and the date this was prepared.' ],
			[ 'What we do', 'what', 'Our work', 'Four pillars of what BlueWorx does for a client.' ],
			[ 'Design services', 'service', 'Design', 'Design-first. Every time.' ],
			[ 'Development services', 'service', 'Development', 'Built to scale. Built to last.' ],
			[ 'Support services', 'service', 'Support', 'Always on. Always with you.' ],
			[ 'Hosting services', 'service', 'Hosting', 'Fast, secure, always available.' ],
			[ 'Estimate summary', 'estimate', 'Investment', 'Generated from the project estimate.' ],
			[ 'Recommended support package', 'package', 'Support', 'Generated from the package calculation.' ],
			[ 'Project timeline', 'timeline', 'Schedule', 'Generated from the timeline tab.' ],
			[ 'Post-launch work', 'postlaunch', 'After launch', 'Generated from the post-launch estimate.' ],
			[ 'Our process', 'process', 'How we work', 'Discovery, design, development, launch, support.' ],
			[ 'Past projects', 'projects', 'Selected work', 'The lead-in to the case studies.' ],
			[ 'Case studies', 'casestudy', 'Our work', 'One slide per case study chosen on the Overview tab.' ],
			[ "Let's build something great.", 'cta', 'Next step', 'The closing slide, and how to get in touch.' ],
		];

		foreach ( $sections as $index => $entry ) {
			$id = self::library_entry( $entry[0], Blueworx_Deck_Builder_Library::SECTION, ( $index + 1 ) * 10 );
			if ( ! $id ) {
				continue;
			}
			update_post_meta( $id, 'bw_library_item_kind', $entry[1] );
			update_post_meta( $id, 'bw_library_item_eyebrow', $entry[2] );
			update_post_meta( $id, 'bw_library_item_note', $entry[3] );
		}

		$project = [
			[ 'Discovery workshop', 'A working session to agree scope, audience and success.', 'Discovery', 12 ],
			[ 'Competitor and sector review', 'What the sector does well, and where there is room.', 'Research', 8 ],
			[ 'Content and messaging strategy', 'What the site says, and in what order.', 'Strategy', 10 ],
			[ 'Wireframes for every template', 'Every page type, agreed before any design.', 'UX and wireframes', 24 ],
			[ 'Design system and key pages', 'The look, built as reusable parts rather than pictures.', 'UI design', 30 ],
			[ 'Build the site as a plugin', 'Templates, components and the editing experience.', 'Development', 80 ],
			[ 'Forms, analytics and mail', 'The third-party pieces, wired up and tested.', 'Integrations', 14 ],
			[ 'Analytics and consent', 'Analytics, consent banner and goal tracking.', 'Integrations', 8 ],
			[ 'Search engine basics', 'Titles, descriptions, sitemap and structured data.', 'Development', 6 ],
			[ 'Move existing content', 'Bringing across what is worth keeping, with redirects for every old address.', 'Migration', 12 ],
			[ 'Cross-browser and device testing', 'Every template, on every size that matters.', 'QA and testing', 18 ],
			[ 'Accessibility pass', 'Keyboard, contrast and screen reader checks across every template.', 'QA and testing', 12 ],
			[ 'Performance tuning', 'Image handling, caching and a Core Web Vitals pass.', 'QA and testing', 10 ],
			[ 'Weekly delivery management', 'Running the project, start to finish.', 'Project management', 16 ],
			[ 'Go live', 'DNS, certificates, redirects and the switch itself.', 'Launch and deployment', 8 ],
			[ 'Training session', 'Two hours with the team, recorded, plus a short written guide.', 'Training and handover', 4 ],
		];

		self::seed_line_items( $project, Blueworx_Deck_Builder_Library::LIST_ESTIMATE );

		$after_launch = [
			[ 'First month monitoring', 'Watching the site closely while real traffic arrives.', 'Launch monitoring', 10 ],
			[ 'Monthly content changes', 'The ordinary run of copy and image updates.', 'Content updates', 12 ],
			[ 'Speed and Core Web Vitals', 'Keeping the site quick as it grows.', 'Performance optimisation', 10 ],
			[ 'Search and structured data', 'Making sure the work is findable.', 'Search optimisation', 8 ],
			[ 'Small improvements', 'The things that only become obvious once it is live.', 'Feature improvements', 14 ],
			[ 'Updates and backups', 'Keeping everything current and recoverable.', 'Support and maintenance', 10 ],
		];

		self::seed_line_items( $after_launch, Blueworx_Deck_Builder_Library::LIST_POSTLAUNCH );
	}

	/**
	 * One list of line items, written into the library.
	 *
	 * @param array<int,array<int,mixed>> $rows Title, description, phase, hours.
	 * @param string                      $which Which estimate these belong to.
	 * @return void
	 */
	private static function seed_line_items( array $rows, $which ) {
		foreach ( $rows as $index => $entry ) {
			$id = self::library_entry( $entry[0], Blueworx_Deck_Builder_Library::LINE_ITEM, ( $index + 1 ) * 10 );
			if ( ! $id ) {
				continue;
			}
			update_post_meta( $id, 'bw_library_item_list', $which );
			update_post_meta( $id, 'bw_library_item_desc', $entry[1] );
			update_post_meta( $id, 'bw_library_item_phase', $entry[2] );
			update_post_meta( $id, 'bw_library_item_hours', $entry[3] );
		}
	}

	/**
	 * One library entry, made, typed and placed.
	 *
	 * @param string $title Entry name.
	 * @param string $type  Entry type.
	 * @param int    $order Where it sits in its own list.
	 * @return int The new entry id, or 0 when the insert failed.
	 */
	private static function library_entry( $title, $type, $order ) {
		$id = wp_insert_post(
			[
				'post_type'   => Blueworx_Deck_Builder_Types::LIBRARY,
				'post_status' => 'publish',
				'post_title'  => $title,
			]
		);
		if ( is_wp_error( $id ) || ! $id ) {
			return 0;
		}
		update_post_meta( (int) $id, 'bw_library_item_entry_type', $type );
		update_post_meta( (int) $id, 'bw_library_item_order', $order );
		return (int) $id;
	}
}
