<?php
/**
 * One deck: what it holds, what follows from it, and what the client may see.
 *
 * @package Blueworx\DeckBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * A deck record, read once and asked questions.
 *
 * Every derived figure lives here rather than in a screen, because three
 * different surfaces ask for the same numbers — the editor's summary strip,
 * the decks dashboard and the client deck itself — and a total that disagreed
 * between them would be worse than no total at all.
 */
final class Blueworx_Deck_Builder_Deck {

	/**
	 * The deck post.
	 *
	 * @var WP_Post
	 */
	private $post;

	/**
	 * Field values, read from post meta once.
	 *
	 * @var array<string,mixed>
	 */
	private $values = [];

	/**
	 * Construct.
	 *
	 * @param WP_Post $post Deck post.
	 */
	private function __construct( WP_Post $post ) {
		$this->post = $post;
	}

	/**
	 * Load a deck by id, or null when that id is not a deck.
	 *
	 * @param int $id Post id.
	 * @return self|null
	 */
	public static function find( $id ) {
		$post = get_post( (int) $id );
		if ( null === $post || Blueworx_Deck_Builder_Types::DECK !== $post->post_type ) {
			return null;
		}
		return new self( $post );
	}

	/**
	 * Load a deck by its client link slug. Deliberately does not care about
	 * post status — the caller decides whether this deck may be shown.
	 *
	 * @param string $slug Twelve-character client slug.
	 * @return self|null
	 */
	public static function find_by_slug( $slug ) {
		$slug = preg_replace( '/[^a-z0-9]/', '', strtolower( (string) $slug ) );
		if ( 12 !== strlen( (string) $slug ) ) {
			return null;
		}
		$found = get_posts(
			[
				'post_type'   => Blueworx_Deck_Builder_Types::DECK,
				'post_status' => 'any',
				'numberposts' => 1,
				'meta_key'    => 'bw_deck_link_slug', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- the client link is the only way in; there is nothing else to query by.
				'meta_value'  => $slug, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- as above.
			]
		);
		return $found ? new self( $found[0] ) : null;
	}

	/**
	 * Every deck, most recently changed first.
	 *
	 * @return array<int,self>
	 */
	public static function all() {
		$posts = get_posts(
			[
				'post_type'   => Blueworx_Deck_Builder_Types::DECK,
				'post_status' => [ 'draft', 'publish', 'private', 'pending' ],
				// phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts -- the decks screen lists every deck; an agency has tens, not thousands.
				'numberposts' => 200,
				'orderby'     => 'modified',
				'order'       => 'DESC',
			]
		);
		return array_map(
			static function ( $post ) {
				return new self( $post );
			},
			$posts
		);
	}

	/**
	 * Mint a new deck and return its id.
	 *
	 * A deck is created here rather than by the editor library, which edits
	 * records and never makes them. Everything a deck cannot work without —
	 * its client link, its currency, its content — is set now, so the editor
	 * never opens onto a half-made record.
	 *
	 * There is no starting point to choose any more: every deck starts as a
	 * copy of the whole content library, and taking a section out is a tick on
	 * the deck rather than a decision made before it exists.
	 *
	 * @param array<string,mixed> $args Client, title, currency and the rest. All optional.
	 * @return int The new deck id, or 0 when the insert failed.
	 */
	public static function create( array $args ) {
		$title = isset( $args['title'] ) ? sanitize_text_field( (string) $args['title'] ) : '';
		$id    = wp_insert_post(
			[
				'post_type'   => Blueworx_Deck_Builder_Types::DECK,
				'post_status' => 'draft',
				'post_title'  => '' !== $title ? $title : __( 'Untitled deck', 'blueworx-labs-deck-builder' ),
			],
			true
		);
		if ( is_wp_error( $id ) || ! $id ) {
			return 0;
		}

		update_post_meta( $id, 'bw_deck_client', sanitize_text_field( (string) ( $args['client'] ?? '' ) ) );
		update_post_meta( $id, 'bw_deck_subtitle', sanitize_textarea_field( (string) ( $args['subtitle'] ?? '' ) ) );
		update_post_meta( $id, 'bw_deck_prepared_for', sanitize_text_field( (string) ( $args['prepared_for'] ?? '' ) ) );
		update_post_meta( $id, 'bw_deck_prepared_date', sanitize_text_field( (string) ( $args['prepared_date'] ?? gmdate( 'Y-m-d' ) ) ) );
		update_post_meta( $id, 'bw_deck_currency', self::clean_currency( $args['currency'] ?? 'GBP' ) );
		update_post_meta( $id, 'bw_deck_link_slug', self::mint_slug() );
		update_post_meta( $id, 'bw_deck_link_enabled', true );

		// The hosting fee is the same for most clients, so it is set once in
		// settings and copied here. From this moment it is this deck's own
		// number, the way everything else copied on creation is — changing the
		// standing fee cannot change a price a client has already been quoted.
		update_post_meta( $id, 'bw_deck_hosting_period', (string) Blueworx_Deck_Builder_Editor::setting( 'hosting_period', __( 'per month', 'blueworx-labs-deck-builder' ) ) );
		foreach ( array_keys( Blueworx_Deck_Builder_Types::currencies() ) as $code ) {
			$field = 'hosting_price_' . strtolower( $code );
			update_post_meta( $id, 'bw_deck_' . $field, (float) Blueworx_Deck_Builder_Editor::setting( $field, 0 ) );
		}

		Blueworx_Deck_Builder_Starter::load_into( (int) $id );

		return (int) $id;
	}

	/**
	 * Twelve unguessable characters. Not a hash of the deck id, not the id in
	 * any form — a client link must say nothing about how many decks exist or
	 * which one this is. The alphabet drops the characters people misread
	 * aloud, because these links get read out over the phone.
	 *
	 * @return string
	 */
	public static function mint_slug() {
		$alphabet = 'abcdefghijkmnpqrstuvwxyz23456789';
		$slug     = '';
		for ( $i = 0; $i < 12; $i++ ) {
			$slug .= $alphabet[ random_int( 0, strlen( $alphabet ) - 1 ) ];
		}
		return $slug;
	}

	/**
	 * A currency code this plugin actually knows.
	 *
	 * @param mixed $code Candidate code.
	 * @return string
	 */
	public static function clean_currency( $code ) {
		$code = strtoupper( sanitize_text_field( (string) $code ) );
		return isset( Blueworx_Deck_Builder_Types::currencies()[ $code ] ) ? $code : 'GBP';
	}

	/* --- Reading ---------------------------------------------------------- */

	/**
	 * The deck id.
	 *
	 * @return int
	 */
	public function id() {
		return (int) $this->post->ID;
	}

	/**
	 * The deck title.
	 *
	 * @return string
	 */
	public function title() {
		return (string) $this->post->post_title;
	}

	/**
	 * When the deck last changed.
	 *
	 * @return string
	 */
	public function updated() {
		return (string) $this->post->post_modified;
	}

	/**
	 * One stored field.
	 *
	 * @param string $field    Field id, without the post type prefix.
	 * @param mixed  $fallback Value when nothing is stored.
	 * @return mixed
	 */
	public function get( $field, $fallback = '' ) {
		if ( ! array_key_exists( $field, $this->values ) ) {
			$key = Blueworx_Deck_Builder_Types::DECK . '_' . $field;

			// metadata_exists() is the whole check, and the fallback applies
			// only when the field has genuinely never been written. A switch
			// turned off stores an empty string, so treating empty as "not
			// set" and handing back the default would read every "off" as
			// "on" — which is how a deck whose link has been disabled stays
			// on the web.
			$this->values[ $field ] = metadata_exists( 'post', $this->id(), $key )
				? get_post_meta( $this->id(), $key, true )
				: $fallback;
		}
		return $this->values[ $field ];
	}

	/**
	 * The client's name, or a placeholder for a deck nobody has named yet. A
	 * blank line in a list reads as a broken row rather than as work in
	 * progress.
	 *
	 * @return string
	 */
	public function client_name() {
		$client = trim( (string) $this->get( 'client' ) );
		return '' === $client ? __( 'No client yet', 'blueworx-labs-deck-builder' ) : $client;
	}

	/**
	 * One stored list.
	 *
	 * @param string $field Field id.
	 * @return array<int,array<string,mixed>>
	 */
	public function rows( $field ) {
		$value = $this->get( $field, [] );
		return is_array( $value ) ? array_values( $value ) : [];
	}

	/**
	 * Deck status as this plugin means it, which is not quite post status:
	 * archiving is state WordPress does not model, and it outranks everything.
	 *
	 * @return string One of draft, published, archived.
	 */
	public function status() {
		if ( $this->get( 'archived' ) ) {
			return 'archived';
		}
		return 'publish' === $this->post->post_status ? 'published' : 'draft';
	}

	/**
	 * The client link, or an empty string when there is nothing to share.
	 *
	 * @return string
	 */
	public function link() {
		$slug = (string) $this->get( 'link_slug' );
		return '' === $slug ? '' : home_url( '/deck/' . $slug . '/' );
	}

	/**
	 * Whether the client link works right now.
	 *
	 * @return bool
	 */
	public function link_live() {
		return 'published' === $this->status() && (bool) $this->get( 'link_enabled', true ) && '' !== $this->link();
	}

	/* --- Derived figures -------------------------------------------------- */

	/**
	 * Hours in a list, counting only rows whose named switch is on.
	 *
	 * @param string $field  Repeater field id.
	 * @param string $toggle Cell id of the switch that decides.
	 * @return float
	 */
	public function hours( $field, $toggle = 'in_total' ) {
		$total = 0.0;
		foreach ( $this->rows( $field ) as $row ) {
			if ( '' !== $toggle && empty( $row[ $toggle ] ) ) {
				continue;
			}
			$total += (float) ( $row['hours'] ?? 0 );
		}
		return $total;
	}

	/**
	 * The project estimate total.
	 *
	 * @return float
	 */
	public function project_total() {
		return $this->hours( 'estimate' );
	}

	/**
	 * The post-launch total.
	 *
	 * @return float
	 */
	public function postlaunch_total() {
		return $this->hours( 'postlaunch' );
	}

	/**
	 * What the support package has to cover: both lists, and only the rows
	 * whose package switch is on. The two switches are independent on purpose
	 * — work can be quoted to a client without a retainer having to carry it.
	 *
	 * @return float
	 */
	public function package_total() {
		return $this->hours( 'estimate', 'in_package' ) + $this->hours( 'postlaunch', 'in_package' );
	}

	/**
	 * The schedule, worked out from the two estimates and nothing else.
	 *
	 * Nobody types a timeline any more. A phase lasts as long as its estimated
	 * hours say it lasts, at Types::HOURS_PER_DAY hours of this client's work
	 * a day, and the phases run in the order the phase lists declare — so
	 * changing an estimate moves the schedule, and the two can never disagree.
	 * There are no calendar dates: a start date is not something a proposal
	 * knows.
	 *
	 * A phase with no hours in it is not a phase of this project and gets no
	 * bar. A phase whose work is all held back from the client is still real
	 * work — it takes its days out of the schedule — but the client does not
	 * see it, because the timeline shows what the estimates show.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function timeline() {
		$rows = [];
		$days = 0.0;

		$lists = [
			Blueworx_Deck_Builder_Library::LIST_ESTIMATE   => 'pre',
			Blueworx_Deck_Builder_Library::LIST_POSTLAUNCH => 'post',
		];

		foreach ( $lists as $which => $kind ) {
			if ( 'post' === $kind && $rows ) {
				$rows[] = self::schedule_row(
					__( 'Launch', 'blueworx-labs-deck-builder' ),
					__( 'The site goes live.', 'blueworx-labs-deck-builder' ),
					__( 'Launch', 'blueworx-labs-deck-builder' ),
					'launch',
					true,
					1.0,
					$days
				);
			}

			foreach ( $this->phase_work( $which ) as $phase => $work ) {
				if ( $work['hours'] <= 0 ) {
					continue;
				}
				$rows[] = self::schedule_row(
					$phase,
					implode( ' · ', $work['items'] ),
					'',
					$kind,
					$work['shown'],
					ceil( $work['hours'] / Blueworx_Deck_Builder_Types::HOURS_PER_DAY ),
					$days
				);
			}
		}

		return $rows;
	}

	/**
	 * One phase's bar, and the days it takes out of the schedule.
	 *
	 * @param string $title     Phase name.
	 * @param string $desc      What is in it, for the client.
	 * @param string $milestone Milestone label, or an empty string.
	 * @param string $kind      One of pre, launch, post.
	 * @param bool   $shown     Whether the client sees it.
	 * @param float  $length    How many working days it takes.
	 * @param float  $days      Days used so far, advanced by this row.
	 * @return array<string,mixed>
	 */
	private static function schedule_row( $title, $desc, $milestone, $kind, $shown, $length, &$days ) {
		$per_week = Blueworx_Deck_Builder_Types::DAYS_PER_WEEK;
		$start    = (int) floor( $days / $per_week ) + 1;
		$days    += max( 1.0, (float) $length );

		return [
			'title'     => $title,
			'desc'      => $desc,
			'milestone' => $milestone,
			'start'     => $start,
			'end'       => max( $start, (int) ceil( $days / $per_week ) ),
			'kind'      => $kind,
			'visible'   => (bool) $shown,
		];
	}

	/**
	 * One estimate's work, gathered under each phase, in the order the phase
	 * list declares. A row whose phase is not on that list keeps its hours in
	 * the totals but has nowhere to sit on a schedule, so it falls in at the
	 * end rather than disappearing.
	 *
	 * @param string $which Which estimate.
	 * @return array<string,array<string,mixed>>
	 */
	private function phase_work( $which ) {
		$out = [];
		foreach ( Blueworx_Deck_Builder_Types::phase_names( $which ) as $phase ) {
			$out[ $phase ] = [ 'hours' => 0.0, 'items' => [], 'shown' => false ];
		}

		foreach ( $this->rows( $which ) as $row ) {
			$phase = trim( (string) ( $row['phase'] ?? '' ) );
			if ( '' === $phase ) {
				$phase = __( 'Other work', 'blueworx-labs-deck-builder' );
			}
			if ( ! isset( $out[ $phase ] ) ) {
				$out[ $phase ] = [ 'hours' => 0.0, 'items' => [], 'shown' => false ];
			}
			if ( ! empty( $row['in_total'] ) ) {
				$out[ $phase ]['hours'] += (float) ( $row['hours'] ?? 0 );
			}
			if ( ! empty( $row['show_client'] ) ) {
				$out[ $phase ]['shown']   = true;
				$out[ $phase ]['items'][] = (string) ( $row['title'] ?? '' );
			}
		}

		return $out;
	}

	/**
	 * Subtotals per phase for one list, in the order the phases first appear.
	 *
	 * @param string $field Repeater field id.
	 * @return array<string,float>
	 */
	public function phase_subtotals( $field ) {
		$out = [];
		foreach ( $this->rows( $field ) as $row ) {
			$phase = trim( (string) ( $row['phase'] ?? '' ) );
			if ( '' === $phase ) {
				$phase = __( 'Not assigned', 'blueworx-labs-deck-builder' );
			}
			if ( ! isset( $out[ $phase ] ) ) {
				$out[ $phase ] = 0.0;
			}
			if ( ! empty( $row['in_total'] ) ) {
				$out[ $phase ] += (float) ( $row['hours'] ?? 0 );
			}
		}
		return $out;
	}

	/**
	 * The package recommendation for this deck.
	 *
	 * @return array<string,mixed>
	 */
	public function recommendation() {
		return Blueworx_Deck_Builder_Packages::recommend(
			$this->package_total(),
			(int) $this->get( 'override', 0 ),
			$this->currency()
		);
	}

	/**
	 * The currency this deck shows.
	 *
	 * @return string
	 */
	public function currency() {
		return self::clean_currency( $this->get( 'currency', 'GBP' ) );
	}

	/**
	 * The seven readiness checks, in the order the design shows them.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function readiness() {
		$recommendation = $this->recommendation();
		$scheduled      = false;
		foreach ( $this->timeline() as $phase ) {
			if ( ! empty( $phase['visible'] ) ) {
				$scheduled = true;
			}
		}

		return [
			[
				'label' => __( 'Client name and title set', 'blueworx-labs-deck-builder' ),
				'done'  => '' !== trim( (string) $this->get( 'client' ) ) && '' !== trim( $this->title() ),
			],
			[
				'label' => __( 'At least one section', 'blueworx-labs-deck-builder' ),
				'done'  => (bool) $this->rows( 'sections' ),
			],
			[
				'label' => __( 'Estimate has line items', 'blueworx-labs-deck-builder' ),
				'done'  => (bool) $this->rows( 'estimate' ),
			],
			[
				'label' => __( 'Post-launch work planned', 'blueworx-labs-deck-builder' ),
				'done'  => (bool) $this->rows( 'postlaunch' ),
			],
			[
				'label' => __( 'Timeline has something to show', 'blueworx-labs-deck-builder' ),
				'done'  => $scheduled,
			],
			[
				'label' => __( 'A package can be recommended', 'blueworx-labs-deck-builder' ),
				'done'  => in_array( $recommendation['state'], [ 'OK', 'EXACT', 'OVERRIDE' ], true ),
			],
			[
				'label' => __( 'Case studies selected', 'blueworx-labs-deck-builder' ),
				'done'  => (bool) $this->get( 'case_studies', [] ),
			],
		];
	}

	/**
	 * How far through the readiness checks this deck is, as a percentage.
	 *
	 * @return int
	 */
	public function readiness_percent() {
		$checks = $this->readiness();
		$done   = 0;
		foreach ( $checks as $check ) {
			$done += $check['done'] ? 1 : 0;
		}
		return (int) round( ( $done / max( 1, count( $checks ) ) ) * 100 );
	}

	/* --- What the client may see ------------------------------------------ */

	/**
	 * The deck reduced to exactly what a client is allowed to see.
	 *
	 * This is a whitelist and it runs on the server. Hiding an internal note
	 * with CSS, or filtering it in the browser, would still have sent it — so
	 * nothing outside this list is ever built into the payload at all.
	 *
	 * @return array<string,mixed>
	 */
	public function client_payload() {
		$recommendation = $this->recommendation();

		return [
			'client'        => (string) $this->get( 'client' ),
			'title'         => $this->title(),
			'subtitle'      => (string) $this->get( 'subtitle' ),
			'prepared_for'  => (string) $this->get( 'prepared_for' ),
			'prepared_date' => (string) $this->get( 'prepared_date' ),
			'logo'          => (int) $this->get( 'logo', 0 ),
			'currency'      => $this->currency(),
			'sections'      => $this->visible_sections(),
			'estimate'      => $this->client_items( 'estimate' ),
			'postlaunch'    => $this->client_items( 'postlaunch' ),
			'timeline'      => $this->client_timeline(),
			'totals'        => [
				'project'    => $this->project_total(),
				'postlaunch' => $this->postlaunch_total(),
			],
			'package'       => $this->client_package( $recommendation ),
			'hosting'       => $this->client_hosting(),
			'case_studies'  => $this->client_case_studies(),
		];
	}

	/**
	 * The monthly hosting and management fee, in this deck's currency, or null
	 * when nobody has set one. A hosting slide with no price on it is a slide
	 * describing work the client has no way to buy, so the section is left out
	 * rather than shown half-finished.
	 *
	 * @return array<string,mixed>|null
	 */
	public function client_hosting() {
		$amount = (float) $this->get( 'hosting_price_' . strtolower( $this->currency() ), 0 );
		if ( $amount <= 0 ) {
			return null;
		}
		return [
			'price'  => Blueworx_Deck_Builder_Packages::money( $amount, $this->currency() ),
			'period' => (string) $this->get( 'hosting_period', __( 'per month', 'blueworx-labs-deck-builder' ) ),
			'hours'  => $this->hours_in_phase( Blueworx_Deck_Builder_Library::LIST_POSTLAUNCH, 'Hosting and management' ),
		];
	}

	/**
	 * Hours counted towards one named phase of one estimate.
	 *
	 * @param string $which Which estimate.
	 * @param string $phase Phase name.
	 * @return float
	 */
	public function hours_in_phase( $which, $phase ) {
		$total = 0.0;
		foreach ( $this->rows( $which ) as $row ) {
			if ( empty( $row['in_total'] ) || trim( (string) ( $row['phase'] ?? '' ) ) !== $phase ) {
				continue;
			}
			$total += (float) ( $row['hours'] ?? 0 );
		}
		return $total;
	}

	/**
	 * Sections the client sees, in order.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function visible_sections() {
		$out = [];
		foreach ( $this->rows( 'sections' ) as $section ) {
			if ( empty( $section['visible'] ) ) {
				continue;
			}
			$out[] = [
				'kind'    => (string) ( $section['kind'] ?? 'cover' ),
				'title'   => (string) ( $section['title'] ?? '' ),
				'eyebrow' => (string) ( $section['eyebrow'] ?? '' ),
				'body'    => (string) ( $section['body'] ?? '' ),
				'points'  => (string) ( $section['points'] ?? '' ),
				'hours'   => (float) ( $section['hours'] ?? 0 ),
				'strap'   => (string) ( $section['strap'] ?? '' ),
			];
		}
		return $out;
	}

	/**
	 * Line items the client sees. The internal note is not filtered out of the
	 * row on its way past — it is never read.
	 *
	 * @param string $field Repeater field id.
	 * @return array<int,array<string,mixed>>
	 */
	private function client_items( $field ) {
		$out = [];
		foreach ( $this->rows( $field ) as $row ) {
			if ( empty( $row['show_client'] ) ) {
				continue;
			}
			$out[] = [
				'title'    => (string) ( $row['title'] ?? '' ),
				'desc'     => (string) ( $row['desc'] ?? '' ),
				'phase'    => (string) ( $row['phase'] ?? '' ),
				'hours'    => (float) ( $row['hours'] ?? 0 ),
				'in_total' => ! empty( $row['in_total'] ),
			];
		}
		return $out;
	}

	/**
	 * Timeline phases the client sees.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function client_timeline() {
		$out = [];
		foreach ( $this->timeline() as $phase ) {
			if ( empty( $phase['visible'] ) ) {
				continue;
			}
			$out[] = [
				'title'     => (string) $phase['title'],
				'desc'      => (string) $phase['desc'],
				'milestone' => (string) $phase['milestone'],
				'start'     => max( 1, (int) $phase['start'] ),
				'end'       => max( 1, (int) $phase['end'] ),
				'kind'      => $phase['kind'],
			];
		}
		return $out;
	}

	/**
	 * The package as the client sees it: the one that was chosen, and any
	 * alternatives, with no trace of how it was arrived at.
	 *
	 * A published deck shows the snapshot taken when it was published, so
	 * editing a package afterwards cannot change a price a client has already
	 * been sent.
	 *
	 * @param array<string,mixed> $recommendation Current recommendation.
	 * @return array<string,mixed>|null
	 */
	private function client_package( array $recommendation ) {
		$snapshot = $this->get( 'snapshot', [] );
		if ( 'published' === $this->status() && is_array( $snapshot ) && ! empty( $snapshot['name'] ) ) {
			return $snapshot;
		}
		if ( null === $recommendation['package'] ) {
			return null;
		}
		return Blueworx_Deck_Builder_Packages::client_view( $recommendation['package'], $this->currency(), $this->alternatives() );
	}

	/**
	 * The packages this deck shows for comparison.
	 *
	 * @return array<int,int>
	 */
	public function alternatives() {
		$chosen = $this->get( 'alternatives', [] );
		return is_array( $chosen ) ? array_map( 'intval', $chosen ) : [];
	}

	/**
	 * The case studies this deck shows, in the order they were chosen.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function client_case_studies() {
		$out = [];
		foreach ( (array) $this->get( 'case_studies', [] ) as $id ) {
			$study = get_post( (int) $id );
			if ( null === $study || Blueworx_Deck_Builder_Types::CASE_STUDY !== $study->post_type ) {
				continue;
			}
			$out[] = [
				'number'   => (string) get_post_meta( $study->ID, 'bw_case_study_number', true ),
				'name'     => $study->post_title,
				'sector'   => (string) get_post_meta( $study->ID, 'bw_case_study_sector', true ),
				'services' => (string) get_post_meta( $study->ID, 'bw_case_study_services', true ),
				'summary'  => (string) get_post_meta( $study->ID, 'bw_case_study_summary', true ),
				'link'     => (string) get_post_meta( $study->ID, 'bw_case_study_link', true ),
				'desktop'  => (int) get_post_meta( $study->ID, 'bw_case_study_desktop', true ),
				'tablet'   => (int) get_post_meta( $study->ID, 'bw_case_study_tablet', true ),
				'mobile'   => (int) get_post_meta( $study->ID, 'bw_case_study_mobile', true ),
			];
		}
		return $out;
	}

	/**
	 * Freeze the recommended package onto the deck, so the client view cannot
	 * change underneath the administrator once the link has been sent.
	 *
	 * @return void
	 */
	public function take_snapshot() {
		$recommendation = $this->recommendation();
		if ( null === $recommendation['package'] ) {
			delete_post_meta( $this->id(), 'bw_deck_snapshot' );
			return;
		}
		update_post_meta(
			$this->id(),
			'bw_deck_snapshot',
			Blueworx_Deck_Builder_Packages::client_view( $recommendation['package'], $this->currency(), $this->alternatives() )
		);
	}
}
