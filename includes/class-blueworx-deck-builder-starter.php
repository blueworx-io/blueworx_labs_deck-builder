<?php
/**
 * What a fresh install begins with, and what every later install catches up to.
 *
 * @package Blueworx\DeckBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * A new deck is a copy of the content library — see
 * Blueworx_Deck_Builder_Library — so this file is where the library's own
 * content is written down: the standard BlueWorx deck, slide by slide and line
 * item by line item, so nobody retypes it for a client.
 *
 * The seed is versioned. Bump SEED_VERSION and every site catches up on its
 * next request: new entries arrive, retired ones go, and an entry still
 * carrying the words this file gave it is brought up to date. An entry
 * somebody has edited is left exactly as they left it — the stored hash is how
 * that is known, and it is the whole reason a re-seed is safe to run at all.
 *
 * Decks already made are untouched either way. A deck holds its own copy from
 * the moment it is created.
 */
final class Blueworx_Deck_Builder_Starter {

	/**
	 * Which edition of the library content this file holds.
	 */
	const SEED_VERSION = 2;

	/**
	 * Where that number is remembered.
	 */
	const VERSION_OPTION = 'blueworx_deck_builder_seed_version';

	/**
	 * Entries earlier editions of this file wrote and this one does not.
	 * Matched on the name they were given, because that is all an entry from
	 * before seed keys existed carries.
	 *
	 * @var array<int,string>
	 */
	const RETIRED = [
		// Dropped from the project estimate.
		'Competitor and sector review',
		'Content and messaging strategy',
		'Accessibility pass',
		// Dropped from the post-launch estimate.
		'Speed and Core Web Vitals',
		'Search and structured data',
		'Small improvements',
		// Renamed, or moved to the other estimate.
		'Weekly delivery management',
		'Build the site as a plugin',
		// Sections renamed so the slide's own heading reads the way the deck
		// reads: "Design", under an eyebrow that says Services.
		'Design services',
		'Development services',
		'Support services',
		'Hosting services',
		'Estimate summary',
		'Recommended support package',
		'Post-launch work',
	];

	/**
	 * Boot: catch this site up to the current edition, once.
	 *
	 * On init rather than on activation, because activation does not run on an
	 * update and an update is exactly when new content arrives. Reads one
	 * option on a normal request and writes nothing.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'init', [ __CLASS__, 'maybe_seed' ], 20 );
	}

	/**
	 * Seed when this site is behind, and never otherwise.
	 *
	 * @return void
	 */
	public static function maybe_seed() {
		if ( (int) get_option( self::VERSION_OPTION, 0 ) === self::SEED_VERSION ) {
			return;
		}
		self::seed();
	}

	/**
	 * Load a new deck's starting content.
	 *
	 * The timeline is not in here. It used to be authored alongside everything
	 * else; it is now worked out from the two estimates — see
	 * Blueworx_Deck_Builder_Deck::timeline() — so there is nothing to copy.
	 *
	 * @param int $deck_id Deck post id.
	 * @return void
	 */
	public static function load_into( $deck_id ) {
		foreach ( Blueworx_Deck_Builder_Library::starting_lists() as $list => $rows ) {
			update_post_meta( $deck_id, 'bw_deck_' . $list, $rows );
		}
	}

	/**
	 * Bring this site's packages, case studies and library up to date.
	 *
	 * Packages and case studies are seeded once and never again: those are a
	 * site's own commercial terms and its own past work, and a plugin update
	 * has no business rewriting either. The library is different — it is this
	 * plugin's content, and keeping it current is the point.
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
		self::seed_library();
		update_option( self::VERSION_OPTION, self::SEED_VERSION );
	}

	/**
	 * Four packages, so the recommendation rule has something to work with on
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
			[ '01', 'Hiraste', 'Travel and accommodation', 'Design, development, hosting, ongoing support', 'A platform built to simplify the search for large group accommodation, with curated listings and the search to find the right one quickly.' ],
			[ '02', 'PadlX', 'Sports and community', 'Design, development, hosting, ongoing support', 'The digital home of a social padel club in Australia: online booking, alongside the community and the brand around it.' ],
			[ '03', 'CAN SAKHARA', 'Luxury villa', 'Design, development, hosting, ongoing support', 'A luxury digital experience for an exclusive Ibiza villa, where immersive photography and editorial layouts do the selling.' ],
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

	/* --- The library ------------------------------------------------------- */

	/**
	 * Every slide of the standard BlueWorx deck.
	 *
	 * Order is written here rather than left to the title, because these are
	 * slides in a presentation: alphabetical would open every deck on the call
	 * to action.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private static function sections() {
		return [
			[
				'key'     => 'cover',
				'title'   => 'Cover',
				'kind'    => 'cover',
				'eyebrow' => 'Integrated',
				'note'    => 'The opening slide: the client, the title and the date this was prepared.',
			],
			[
				'key'     => 'what-we-do',
				'title'   => 'What we do',
				'kind'    => 'what',
				'eyebrow' => 'Our work',
				'note'    => 'The four things BlueWorx does for a client.',
				'body'    => 'BlueWorx builds and supports modern digital platforms designed to grow alongside your business. We bring strategy, design, development, hosting and ongoing support together into one partnership.',
				'points'  => "Website design and development\nSecure hosting and digital infrastructure\nLong-term digital partnerships\nContinuous optimisation",
			],
			[
				'key'     => 'design',
				'title'   => 'Design',
				'kind'    => 'service',
				'eyebrow' => 'Services',
				'note'    => 'Design-first. Every time.',
				'body'    => 'We create website designs that let you see the whole platform before development begins, with every decision tailored to your business, your audience and your goals.',
				'points'  => "Wireframes and full mock-ups before a single line of code is written\nRegular feedback checkpoints throughout the design phase\nFull visual alignment with your brand and audience\nInteractive prototypes, so you experience the design before launch",
				'strap'   => "Design-first.\nEvery time.",
				'hours'   => 30,
			],
			[
				'key'     => 'development',
				'title'   => 'Development',
				'kind'    => 'service',
				'eyebrow' => 'Services',
				'note'    => 'Built to scale. Built to last.',
				'body'    => 'We turn approved designs into fast, polished, fully responsive websites. Development happens on a live staging environment, so you can see it as it is built.',
				'points'  => "Clean code and modern tools, for a site that stays quick\nA seamless experience on every device and screen size\nInteraction and motion that bring the site to life\nReal-time progress you can look at whenever you like",
				'strap'   => "Built to scale.\nBuilt to last.",
				'hours'   => 80,
			],
			[
				'key'     => 'support',
				'title'   => 'Support',
				'kind'    => 'service',
				'eyebrow' => 'Services',
				'note'    => 'Always on. Always with you.',
				'body'    => 'Ongoing support that makes running and growing your website simple. It is covered by your retainer, so there are no surprises.',
				'points'  => "Content updates, turned around quickly\nProactive monitoring of speed, uptime and security\nEnhancements aligned to goals as they change\nRegular reviews and roadmap planning with your team",
				'strap'   => "Always on.\nAlways with you.",
			],
			[
				'key'     => 'hosting',
				'title'   => 'Hosting',
				'kind'    => 'service',
				'eyebrow' => 'Services',
				'note'    => 'Fast, secure, always available.',
				'body'    => 'Managed hosting is the secure, high-performance foundation that keeps your website fast, stable and ready to grow.',
				'points'  => "Enterprise-grade security, with firewalls, SSL and DDoS protection\nOptimised servers and caching, for fast loading anywhere\n24/7 monitoring, so problems are caught before you see them\nAutomated backups and straightforward recovery\nCapacity that grows with your platform and its traffic",
				'strap'   => "Fast, secure.\nAlways available.",
			],
			[
				'key'     => 'estimate',
				'title'   => 'Project estimate',
				'kind'    => 'estimate',
				'eyebrow' => 'Investment',
				'note'    => 'Built from the project estimate. Nothing here is typed twice.',
			],
			[
				'key'     => 'timeline',
				'title'   => 'Project timeline',
				'kind'    => 'timeline',
				'eyebrow' => 'Schedule',
				'note'    => 'Worked out from the estimated hours on both estimates.',
			],
			[
				'key'     => 'postlaunch',
				'title'   => 'After launch',
				'kind'    => 'postlaunch',
				'eyebrow' => 'Ongoing',
				'note'    => 'Built from the post-launch estimate.',
				'body'    => 'The work that carries on once the site is live, and what it is expected to take.',
			],
			[
				'key'     => 'hosting-management',
				'title'   => 'Hosting and management',
				'kind'    => 'hosting',
				'eyebrow' => 'Infrastructure',
				'note'    => 'The monthly hosting fee, and the work behind it.',
				'body'    => 'Your site runs on infrastructure we manage end to end. The monthly fee covers the platform itself and the work of keeping every part of it current, secure and backed up.',
				'points'  => "Servers, certificates and DNS, managed and monitored\nWordPress core, theme and plugin updates, tested before they ship\nDatabase upkeep, with automated backups and tested recovery\nMail and transactional sending, kept deliverable\nUptime, security and performance watched around the clock",
				'strap'   => 'Managed end to end.',
			],
			[
				'key'     => 'package',
				'title'   => 'Support packages',
				'kind'    => 'package',
				'eyebrow' => 'Support',
				'note'    => 'The recommended package, worked out from the hours planned.',
			],
			[
				'key'     => 'process',
				'title'   => 'Our process',
				'kind'    => 'process',
				'eyebrow' => 'How we work',
				'note'    => 'Discovery to support, in five steps.',
				'body'    => 'A clear, structured approach at every stage — from the first conversation to long-term growth.',
				'points'  => "Discovery\nDesign\nDevelopment\nLaunch\nSupport",
			],
			[
				'key'     => 'projects',
				'title'   => 'Past projects',
				'kind'    => 'projects',
				'eyebrow' => 'Selected work',
				'note'    => 'The lead-in to the case studies.',
				'body'    => 'Working closely with the businesses we support, we have delivered tailored digital work across a range of industries and organisation sizes.',
			],
			[
				'key'     => 'casestudy',
				'title'   => 'Case studies',
				'kind'    => 'casestudy',
				'eyebrow' => 'Our work',
				'note'    => 'One slide per case study chosen on the Overview tab.',
			],
			[
				'key'     => 'cta',
				'title'   => "Let's build something great.",
				'kind'    => 'cta',
				'eyebrow' => 'Next step',
				'note'    => 'The closing slide, and how to get in touch.',
				'body'    => 'We would love to learn more about your business and how we can support its digital growth.',
			],
		];
	}

	/**
	 * Every line item the two estimates start with.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private static function line_items() {
		return [
			/* --- Before launch --------------------------------------------- */
			[ 'key' => 'discovery-workshop', 'title' => 'Discovery workshop', 'desc' => 'A working session to agree scope, audience and what success looks like.', 'phase' => 'Discovery', 'hours' => 12 ],
			[ 'key' => 'wireframes', 'title' => 'Wireframes for every template', 'desc' => 'Every page type, agreed before any design.', 'phase' => 'UX and wireframes', 'hours' => 12 ],
			[ 'key' => 'design-system', 'title' => 'Design system and key pages', 'desc' => 'The look, built as reusable parts rather than as pictures.', 'phase' => 'UI design', 'hours' => 30 ],
			[ 'key' => 'prototype', 'title' => 'Interactive prototype', 'desc' => 'Click through the design before a line of code is written.', 'phase' => 'Prototyping', 'hours' => 8 ],
			[ 'key' => 'build-site', 'title' => 'Build the site', 'desc' => 'Templates, components and the editing experience behind them.', 'phase' => 'Development', 'hours' => 80 ],
			[ 'key' => 'search-basics', 'title' => 'Search engine basics', 'desc' => 'Titles, descriptions, sitemap and structured data.', 'phase' => 'Development', 'hours' => 6 ],
			[ 'key' => 'forms-mail', 'title' => 'Forms, analytics and mail', 'desc' => 'The third-party pieces, wired up and tested.', 'phase' => 'Integrations', 'hours' => 14 ],
			[ 'key' => 'analytics-consent', 'title' => 'Analytics and consent', 'desc' => 'Analytics, the consent banner and goal tracking.', 'phase' => 'Integrations', 'hours' => 8 ],
			[ 'key' => 'migrate-content', 'title' => 'Move existing content', 'desc' => 'Bringing across what is worth keeping, with a redirect for every old address.', 'phase' => 'Migration', 'hours' => 12 ],
			[ 'key' => 'client-review', 'title' => 'Client review rounds', 'desc' => 'You walk the finished work and tell us what needs to change.', 'phase' => 'Reviews and reverts', 'hours' => 12 ],
			[ 'key' => 'revisions', 'title' => 'Changes and reverts', 'desc' => 'Acting on that feedback, including putting something back the way it was.', 'phase' => 'Reviews and reverts', 'hours' => 8 ],
			[ 'key' => 'browser-testing', 'title' => 'Cross-browser and device testing', 'desc' => 'Every template, on every size that matters.', 'phase' => 'QA and testing', 'hours' => 18 ],
			[ 'key' => 'performance-pass', 'title' => 'Performance tuning', 'desc' => 'Image handling, caching and a Core Web Vitals pass.', 'phase' => 'QA and testing', 'hours' => 10 ],
			[ 'key' => 'go-live', 'title' => 'Go live', 'desc' => 'DNS, certificates, redirects and the switch itself.', 'phase' => 'Launch and deployment', 'hours' => 8 ],
			[ 'key' => 'training', 'title' => 'Training session', 'desc' => 'Two hours with the team, recorded, plus a short written guide.', 'phase' => 'Training and handover', 'hours' => 4 ],

			/* --- After launch ---------------------------------------------- */
			[ 'key' => 'launch-monitoring', 'title' => 'First month monitoring', 'desc' => 'Watching the site closely while real traffic arrives.', 'phase' => 'Launch monitoring', 'hours' => 10, 'list' => Blueworx_Deck_Builder_Library::LIST_POSTLAUNCH ],
			[ 'key' => 'content-changes', 'title' => 'Monthly content changes', 'desc' => 'The ordinary run of copy, image and page updates.', 'phase' => 'Content updates', 'hours' => 12, 'list' => Blueworx_Deck_Builder_Library::LIST_POSTLAUNCH ],
			[ 'key' => 'postlaunch-updates', 'title' => 'Post-launch updates', 'desc' => 'The work that arrives straight after go-live, once the site is real and in use.', 'phase' => 'Post-launch updates', 'hours' => 14, 'list' => Blueworx_Deck_Builder_Library::LIST_POSTLAUNCH ],
			[ 'key' => 'ongoing-improvements', 'title' => 'Ongoing improvements', 'desc' => 'Enhancements aligned to your goals as they change.', 'phase' => 'Ongoing development', 'hours' => 12, 'list' => Blueworx_Deck_Builder_Library::LIST_POSTLAUNCH ],
			[ 'key' => 'hosting-fee', 'title' => 'Managed hosting', 'desc' => 'The platform itself: servers, certificates, backups and the monitoring behind them.', 'phase' => 'Hosting and management', 'hours' => 8, 'list' => Blueworx_Deck_Builder_Library::LIST_POSTLAUNCH ],
			[ 'key' => 'platform-maintenance', 'title' => 'Platform maintenance', 'desc' => 'Core, plugin and database upkeep, and the mail systems alongside them.', 'phase' => 'Hosting and management', 'hours' => 12, 'list' => Blueworx_Deck_Builder_Library::LIST_POSTLAUNCH ],
			[ 'key' => 'updates-backups', 'title' => 'Updates and backups', 'desc' => 'Keeping everything current and recoverable.', 'phase' => 'Support and maintenance', 'hours' => 10, 'list' => Blueworx_Deck_Builder_Library::LIST_POSTLAUNCH ],
			[ 'key' => 'delivery-management', 'title' => 'Delivery management', 'desc' => 'Running the work, month to month.', 'phase' => 'Project management', 'hours' => 16, 'list' => Blueworx_Deck_Builder_Library::LIST_POSTLAUNCH ],
			[ 'key' => 'reporting', 'title' => 'Monthly reporting and review', 'desc' => 'What changed, what it did, and what is worth doing next.', 'phase' => 'Reporting and reviews', 'hours' => 6, 'list' => Blueworx_Deck_Builder_Library::LIST_POSTLAUNCH ],
		];
	}

	/**
	 * Write the library out, without treading on anybody's own words.
	 *
	 * @return void
	 */
	private static function seed_library() {
		$existing = self::existing_entries();
		$order    = 0;

		foreach ( self::sections() as $entry ) {
			$order += 10;
			self::write_entry( $entry, Blueworx_Deck_Builder_Library::SECTION, $order, $existing );
		}

		$order = 0;
		foreach ( self::line_items() as $entry ) {
			$order += 10;
			self::write_entry( $entry, Blueworx_Deck_Builder_Library::LINE_ITEM, $order, $existing );
		}

		self::retire( $existing );
	}

	/**
	 * Every library entry on the site, indexed by seed key and by name.
	 *
	 * A name is how an entry written before seed keys existed is recognised —
	 * that is all it carries. Once matched, it is given its key and never
	 * looked up by name again.
	 *
	 * @return array<string,int> Key or lowercased name, mapped to entry id.
	 */
	private static function existing_entries() {
		$posts = get_posts(
			[
				'post_type'   => Blueworx_Deck_Builder_Types::LIBRARY,
				'post_status' => 'any',
				// phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts -- the library is a hand-curated list; an agency has tens of entries.
				'numberposts' => 200,
			]
		);

		$out = [];
		foreach ( $posts as $post ) {
			$out[ 'name:' . strtolower( $post->post_title ) ] = (int) $post->ID;
			$key = (string) get_post_meta( $post->ID, 'bw_library_item_seed_key', true );
			if ( '' !== $key ) {
				$out[ 'key:' . $key ] = (int) $post->ID;
			}
		}
		return $out;
	}

	/**
	 * One canonical entry, created or brought up to date.
	 *
	 * @param array<string,mixed> $entry    Canonical entry.
	 * @param string              $type     Section or line item.
	 * @param int                 $order    Where it sits in its own list.
	 * @param array<string,int>   $existing Index from existing_entries().
	 * @return void
	 */
	private static function write_entry( array $entry, $type, $order, array $existing ) {
		$fields = self::fields_for( $entry, $type, $order );
		$id     = $existing[ 'key:' . $entry['key'] ] ?? $existing[ 'name:' . strtolower( $entry['title'] ) ] ?? 0;

		if ( $id > 0 && ! self::untouched( $id ) ) {
			// Somebody has made this entry their own. Their words win, and the
			// only thing still worth writing is the key, so the next edition
			// can find this entry again without guessing from its name.
			update_post_meta( $id, 'bw_library_item_seed_key', $entry['key'] );
			return;
		}

		if ( 0 === $id ) {
			$made = wp_insert_post(
				[
					'post_type'   => Blueworx_Deck_Builder_Types::LIBRARY,
					'post_status' => 'publish',
					'post_title'  => $entry['title'],
				]
			);
			if ( is_wp_error( $made ) || ! $made ) {
				return;
			}
			$id = (int) $made;
		} elseif ( get_post_field( 'post_title', $id ) !== $entry['title'] ) {
			wp_update_post( [ 'ID' => $id, 'post_title' => $entry['title'] ] );
		}

		foreach ( $fields as $field => $value ) {
			update_post_meta( $id, 'bw_library_item_' . $field, $value );
		}
		update_post_meta( $id, 'bw_library_item_seed_key', $entry['key'] );
		update_post_meta( $id, 'bw_library_item_seed_hash', self::hash( $entry['title'], $fields ) );
	}

	/**
	 * What one canonical entry stores, with everything it leaves blank spelled
	 * out. A field that is simply skipped would keep whatever an earlier
	 * edition put there, which is how a section ends up with a strapline from
	 * a slide it no longer is.
	 *
	 * @param array<string,mixed> $entry Canonical entry.
	 * @param string              $type  Section or line item.
	 * @param int                 $order Where it sits in its own list.
	 * @return array<string,mixed>
	 */
	private static function fields_for( array $entry, $type, $order ) {
		$common = [
			'entry_type' => $type,
			'order'      => $order,
		];

		if ( Blueworx_Deck_Builder_Library::SECTION === $type ) {
			return array_merge(
				$common,
				[
					'kind'    => (string) $entry['kind'],
					'eyebrow' => (string) ( $entry['eyebrow'] ?? '' ),
					'note'    => (string) ( $entry['note'] ?? '' ),
					'body'    => (string) ( $entry['body'] ?? '' ),
					'points'  => (string) ( $entry['points'] ?? '' ),
					'strap'   => (string) ( $entry['strap'] ?? '' ),
					'hours'   => (float) ( $entry['hours'] ?? 0 ),
				]
			);
		}

		return array_merge(
			$common,
			[
				'list'  => (string) ( $entry['list'] ?? Blueworx_Deck_Builder_Library::LIST_ESTIMATE ),
				'desc'  => (string) ( $entry['desc'] ?? '' ),
				'phase' => (string) ( $entry['phase'] ?? '' ),
				'hours' => (float) ( $entry['hours'] ?? 0 ),
			]
		);
	}

	/**
	 * Whether an entry still says exactly what this file last gave it.
	 *
	 * An entry with no hash at all was written before the library was
	 * versioned. It counts as untouched: the whole point of this edition is to
	 * replace that content, and it was never anything but seed content.
	 *
	 * @param int $id Entry id.
	 * @return bool
	 */
	private static function untouched( $id ) {
		$stored = (string) get_post_meta( $id, 'bw_library_item_seed_hash', true );
		if ( '' === $stored ) {
			return true;
		}
		return hash_equals( $stored, self::hash_of( $id ) );
	}

	/**
	 * What an entry on the site hashes to right now.
	 *
	 * @param int $id Entry id.
	 * @return string
	 */
	private static function hash_of( $id ) {
		$fields = [];
		foreach ( [ 'entry_type', 'order', 'kind', 'eyebrow', 'note', 'body', 'points', 'strap', 'list', 'desc', 'phase', 'hours' ] as $field ) {
			$value = get_post_meta( $id, 'bw_library_item_' . $field, true );
			if ( '' === $value || null === $value ) {
				continue;
			}
			// Post meta reads back as text whatever went in, so a number has to
			// be put back into the type fields_for() gave it or the two sides
			// hash a string against an integer and every entry reads as edited.
			if ( 'hours' === $field ) {
				$fields[ $field ] = (float) $value;
			} elseif ( 'order' === $field ) {
				$fields[ $field ] = (int) $value;
			} else {
				$fields[ $field ] = (string) $value;
			}
		}
		return self::hash( (string) get_post_field( 'post_title', $id ), $fields );
	}

	/**
	 * One entry, as a string that changes when anything about it does.
	 *
	 * @param string              $title  Entry name.
	 * @param array<string,mixed> $fields Stored fields.
	 * @return string
	 */
	private static function hash( $title, array $fields ) {
		$parts = array_filter(
			$fields,
			static function ( $value ) {
				return '' !== $value && 0 !== $value && 0.0 !== $value;
			}
		);
		ksort( $parts );
		return md5( $title . '|' . wp_json_encode( $parts ) );
	}

	/**
	 * Take out what earlier editions wrote and this one does not.
	 *
	 * Only ever an entry named in RETIRED — a hand-written entry is nobody's
	 * to delete, and one that simply is not canonical was probably somebody's
	 * own.
	 *
	 * @param array<string,int> $existing Index from existing_entries().
	 * @return void
	 */
	private static function retire( array $existing ) {
		foreach ( self::RETIRED as $title ) {
			$id = $existing[ 'name:' . strtolower( $title ) ] ?? 0;
			if ( $id > 0 && self::untouched( $id ) ) {
				wp_delete_post( $id, true );
			}
		}
	}
}
