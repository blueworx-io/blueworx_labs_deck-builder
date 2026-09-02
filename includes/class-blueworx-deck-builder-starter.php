<?php
/**
 * The retainer starting point, and what a fresh install begins with.
 *
 * @package Blueworx\DeckBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * A deck built from the retainer template starts with the standard set of
 * sections, a project estimate, post-launch work and a timeline. Everything
 * here is a starting point and every line of it is editable per deck — nothing
 * in this file is referenced again once a deck has been created.
 */
final class Blueworx_Deck_Builder_Starter {

	/**
	 * Load the retainer set onto a deck.
	 *
	 * @param int $deck_id Deck post id.
	 * @return void
	 */
	public static function load_into( $deck_id ) {
		update_post_meta( $deck_id, 'bw_deck_sections', self::sections() );
		update_post_meta( $deck_id, 'bw_deck_estimate', self::estimate() );
		update_post_meta( $deck_id, 'bw_deck_postlaunch', self::postlaunch() );
		update_post_meta( $deck_id, 'bw_deck_timeline', self::timeline() );
	}

	/**
	 * The retainer deck's sections, in presentation order.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function sections() {
		$rows = [
			[ 'cover', 'Cover', 'Integrated', 'The opening slide: client, title and the date this was prepared.' ],
			[ 'what', 'What we do', 'Our work', 'Four pillars of what BlueWorx does for a client.' ],
			[ 'service', 'Design services', 'Design', 'Design-first. Every time.' ],
			[ 'service', 'Development services', 'Development', 'Built to scale. Built to last.' ],
			[ 'service', 'Support services', 'Support', 'Always on. Always with you.' ],
			[ 'service', 'Hosting services', 'Hosting', 'Fast, secure, always available.' ],
			[ 'estimate', 'Estimate summary', 'Investment', 'Generated from the project estimate.' ],
			[ 'package', 'Recommended support package', 'Support', 'Generated from the package calculation.' ],
			[ 'timeline', 'Project timeline', 'Schedule', 'Generated from the timeline tab.' ],
			[ 'postlaunch', 'Post-launch work', 'After launch', 'Generated from the post-launch estimate.' ],
			[ 'process', 'Our process', 'How we work', 'Discovery, design, development, launch, support.' ],
			[ 'projects', 'Past projects', 'Selected work', 'The lead-in to the case studies.' ],
			[ 'casestudy', 'Case studies', 'Our work', 'One slide per case study chosen on the Overview tab.' ],
			[ 'cta', "Let's build something great.", 'Next step', 'The closing slide, and how to get in touch.' ],
		];

		$out = [];
		foreach ( $rows as $row ) {
			$out[] = [
				'kind'    => $row[0],
				'title'   => $row[1],
				'eyebrow' => $row[2],
				'note'    => $row[3],
				'body'    => '',
				'points'  => '',
				'hours'   => 0,
				'strap'   => '',
				'visible' => true,
			];
		}
		return $out;
	}

	/**
	 * A starting project estimate.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function estimate() {
		return self::items(
			[
				[ 'Discovery', 'Discovery workshop', 'A working session to agree scope, audience and success.', 12 ],
				[ 'Research', 'Competitor and sector review', 'What the sector does well, and where there is room.', 8 ],
				[ 'Strategy', 'Content and messaging strategy', 'What the site says, and in what order.', 10 ],
				[ 'UX and wireframes', 'Wireframes for every template', 'Every page type, agreed before any design.', 24 ],
				[ 'UI design', 'Design system and key pages', 'The look, built as reusable parts rather than pictures.', 30 ],
				[ 'Development', 'Build the site as a plugin', 'Templates, components and the editing experience.', 80 ],
				[ 'Integrations', 'Forms, analytics and mail', 'The third-party pieces, wired up and tested.', 14 ],
				[ 'Migration', 'Move existing content', 'Bringing across what is worth keeping.', 12 ],
				[ 'QA and testing', 'Cross-browser and device testing', 'Every template, on every size that matters.', 18 ],
				[ 'Project management', 'Weekly delivery management', 'Running the project, start to finish.', 16 ],
				[ 'Launch and deployment', 'Go live', 'DNS, certificates, redirects and the switch itself.', 8 ],
			]
		);
	}

	/**
	 * A starting post-launch estimate.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function postlaunch() {
		return self::items(
			[
				[ 'Launch monitoring', 'First month monitoring', 'Watching the site closely while real traffic arrives.', 10 ],
				[ 'Content updates', 'Monthly content changes', 'The ordinary run of copy and image updates.', 12 ],
				[ 'Performance optimisation', 'Speed and Core Web Vitals', 'Keeping the site quick as it grows.', 10 ],
				[ 'Search optimisation', 'Search and structured data', 'Making sure the work is findable.', 8 ],
				[ 'Feature improvements', 'Small improvements', 'The things that only become obvious once it is live.', 14 ],
				[ 'Support and maintenance', 'Updates and backups', 'Keeping everything current and recoverable.', 10 ],
			]
		);
	}

	/**
	 * Turn a compact list into line items with all three switches on.
	 *
	 * Everything starts included — in the total, shown to the client and
	 * counted towards the support package. Taking a line out is a decision
	 * somebody makes about this client; leaving work out of the package
	 * calculation by default would quietly under-state what the retainer has
	 * to cover, which is the one answer that must never happen by accident.
	 *
	 * @param array<int,array<int,mixed>> $rows Phase, title, description, hours.
	 * @return array<int,array<string,mixed>>
	 */
	private static function items( array $rows ) {
		$out = [];
		foreach ( $rows as $row ) {
			$out[] = [
				'title'       => $row[1],
				'desc'        => $row[2],
				'phase'       => $row[0],
				'hours'       => $row[3],
				'note'        => '',
				'in_total'    => true,
				'show_client' => true,
				'in_package'  => true,
			];
		}
		return $out;
	}

	/**
	 * The default timeline. Weeks are authored, never derived from the hours
	 * above — a schedule is what the team can actually do.
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
	 * The reusable sections and line items a blank deck can be built from.
	 *
	 * @return void
	 */
	private static function seed_library() {
		$sections = [
			[ 'Cover', 'cover', 'The opening slide.' ],
			[ 'What we do', 'what', 'Four pillars of the offer.' ],
			[ 'Service detail', 'service', 'One service, its hours and its points.' ],
			[ 'Estimate summary', 'estimate', 'Generated from the project estimate.' ],
			[ 'Recommended support package', 'package', 'Generated from the package calculation.' ],
			[ 'Project timeline', 'timeline', 'Generated from the timeline tab.' ],
			[ 'Post-launch work', 'postlaunch', 'Generated from the post-launch estimate.' ],
			[ 'Our process', 'process', 'Discovery to support, in five steps.' ],
			[ 'Past projects intro', 'projects', 'The lead-in to the case studies.' ],
			[ 'Call to action', 'cta', 'How to get in touch.' ],
			[ 'Standard introduction', 'what', 'The introduction we open most decks with.' ],
		];

		foreach ( $sections as $entry ) {
			$id = self::library_entry( $entry[0], Blueworx_Deck_Builder_Library::SECTION );
			if ( ! $id ) {
				continue;
			}
			update_post_meta( $id, 'bw_library_item_kind', $entry[1] );
			update_post_meta( $id, 'bw_library_item_note', $entry[2] );
		}

		// The work that turns up on nearly every quote, so the estimate's own
		// library picker has something in it on day one rather than an empty
		// panel that reads as a broken feature.
		$line_items = [
			[ 'Accessibility pass', 'Keyboard, contrast and screen reader checks across every template.', 'QA and testing', 12 ],
			[ 'Analytics and consent', 'Analytics, consent banner and goal tracking.', 'Development', 8 ],
			[ 'Content migration', 'Moving existing content across, with redirects for every old address.', 'Migration', 24 ],
			[ 'Performance tuning', 'Image handling, caching and a Core Web Vitals pass.', 'QA and testing', 10 ],
			[ 'Search engine basics', 'Titles, descriptions, sitemap and structured data.', 'Development', 6 ],
			[ 'Training session', 'Two hours with the team, recorded, plus a short written guide.', 'Training and handover', 4 ],
		];

		foreach ( $line_items as $entry ) {
			$id = self::library_entry( $entry[0], Blueworx_Deck_Builder_Library::LINE_ITEM );
			if ( ! $id ) {
				continue;
			}
			update_post_meta( $id, 'bw_library_item_desc', $entry[1] );
			update_post_meta( $id, 'bw_library_item_phase', $entry[2] );
			update_post_meta( $id, 'bw_library_item_hours', $entry[3] );
		}
	}

	/**
	 * One library entry, made and typed.
	 *
	 * @param string $title Entry name.
	 * @param string $type  Section or line item.
	 * @return int The new entry id, or 0 when the insert failed.
	 */
	private static function library_entry( $title, $type ) {
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
		return (int) $id;
	}
}
