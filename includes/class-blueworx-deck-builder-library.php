<?php
/**
 * The content library, and the two ways a deck exchanges rows with it.
 *
 * @package Blueworx\DeckBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * A library entry is a row somebody wants again: a section, or a line item.
 *
 * Both directions are worked as part of saving the deck rather than as their
 * own button, because the page editor's control list has no button in it and
 * a plugin does not add one on its own. So a tick is the instruction and the
 * save carries it out, after which the tick clears itself — the same shape as
 * every other change on the screen, and it cannot half-happen.
 */
final class Blueworx_Deck_Builder_Library {

	/**
	 * Which sort of row an entry holds.
	 */
	const SECTION   = 'section';
	const LINE_ITEM = 'line_item';

	/**
	 * The field on the deck that says which entries to bring in, one per list
	 * it can bring them into.
	 *
	 * @var array<string,string>
	 */
	const PICKERS = [
		'sections'   => 'library_sections',
		'estimate'   => 'library_estimate',
		'postlaunch' => 'library_postlaunch',
	];

	/**
	 * The cell on an estimate row that sends it the other way.
	 */
	const SAVE_CELL = 'to_library';

	/**
	 * Boot.
	 *
	 * @return void
	 */
	public static function register() {
		// After the editor's own permission check has passed and its callback
		// has run, so this only ever acts on a save that was allowed and that
		// actually succeeded. There is no hook inside the shared library, and
		// adding one there for a single plugin's convenience would be the
		// wrong place for it.
		add_filter( 'rest_request_after_callbacks', [ __CLASS__, 'after_save' ], 10, 3 );
	}

	/**
	 * Entries of one sort, as checkbox options.
	 *
	 * @param string $type Section or line item.
	 * @return array<int,array<string,string>>
	 */
	public static function options( $type ) {
		$out = [];
		foreach ( self::entries( $type ) as $post ) {
			$out[] = [ 'value' => (string) $post->ID, 'label' => $post->post_title ];
		}
		return $out;
	}

	/**
	 * Every entry of one sort.
	 *
	 * @param string $type Section or line item.
	 * @return array<int,WP_Post>
	 */
	private static function entries( $type ) {
		$posts = get_posts(
			[
				'post_type'   => Blueworx_Deck_Builder_Types::LIBRARY,
				'post_status' => [ 'draft', 'publish', 'private', 'pending' ],
				// phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_numberposts -- the library is a hand-curated list; an agency has tens of entries.
				'numberposts' => 200,
				'orderby'     => 'title',
				'order'       => 'ASC',
			]
		);
		return array_values(
			array_filter(
				$posts,
				static function ( $post ) use ( $type ) {
					return self::type_of( $post->ID ) === $type;
				}
			)
		);
	}

	/**
	 * What sort of row an entry holds.
	 *
	 * Entries written before the library held line items have no type stored
	 * at all, and every one of those was a section — so an unset type reads as
	 * a section rather than as nothing, and an old library keeps working.
	 *
	 * @param int $id Entry id.
	 * @return string
	 */
	public static function type_of( $id ) {
		$stored = get_post_meta( (int) $id, Blueworx_Deck_Builder_Types::LIBRARY . '_entry_type', true );
		return self::LINE_ITEM === $stored ? self::LINE_ITEM : self::SECTION;
	}

	/**
	 * One entry's own field.
	 *
	 * @param int    $id    Entry id.
	 * @param string $field Field id.
	 * @return mixed
	 */
	private static function field( $id, $field ) {
		return get_post_meta( (int) $id, Blueworx_Deck_Builder_Types::LIBRARY . '_' . $field, true );
	}

	/**
	 * An entry as a row for the deck's sections list.
	 *
	 * @param int $id Entry id.
	 * @return array<string,mixed>
	 */
	public static function section_row( $id ) {
		$post = get_post( (int) $id );
		return [
			'title'   => null === $post ? '' : $post->post_title,
			'kind'    => (string) self::field( $id, 'kind' ),
			'eyebrow' => (string) self::field( $id, 'eyebrow' ),
			'body'    => (string) self::field( $id, 'body' ),
			'points'  => (string) self::field( $id, 'points' ),
			'hours'   => '',
			'strap'   => (string) self::field( $id, 'strap' ),
			'note'    => (string) self::field( $id, 'note' ),
			'visible' => true,
		];
	}

	/**
	 * An entry as a row for one of the deck's estimate lists.
	 *
	 * The three switches all arrive on, because somebody adding a line item
	 * meant to add work — a row that lands excluded from every total reads as
	 * the insert having failed.
	 *
	 * @param int $id Entry id.
	 * @return array<string,mixed>
	 */
	public static function line_item_row( $id ) {
		$post = get_post( (int) $id );
		return [
			'title'         => null === $post ? '' : $post->post_title,
			'desc'          => (string) self::field( $id, 'desc' ),
			'phase'         => (string) self::field( $id, 'phase' ),
			'hours'         => (float) self::field( $id, 'hours' ),
			'note'          => '',
			'in_total'      => true,
			'show_client'   => true,
			'in_package'    => true,
			self::SAVE_CELL => false,
		];
	}

	/**
	 * Keep an estimate row for next time.
	 *
	 * The internal note is deliberately left behind: it was written about one
	 * client, and carrying it into the library would put it in front of the
	 * next one.
	 *
	 * @param array<string,mixed> $row One estimate row.
	 * @return int The new entry id, or 0 when there was nothing worth keeping.
	 */
	public static function keep_line_item( array $row ) {
		$title = sanitize_text_field( (string) ( $row['title'] ?? '' ) );
		if ( '' === trim( $title ) ) {
			return 0;
		}

		$id = wp_insert_post(
			[
				'post_type'   => Blueworx_Deck_Builder_Types::LIBRARY,
				'post_status' => 'publish',
				'post_title'  => $title,
			],
			true
		);
		if ( is_wp_error( $id ) || ! $id ) {
			return 0;
		}

		$meta = [
			'entry_type' => self::LINE_ITEM,
			'desc'       => sanitize_text_field( (string) ( $row['desc'] ?? '' ) ),
			'phase'      => sanitize_text_field( (string) ( $row['phase'] ?? '' ) ),
			'hours'      => (float) ( $row['hours'] ?? 0 ),
		];
		foreach ( $meta as $field => $value ) {
			update_post_meta( (int) $id, Blueworx_Deck_Builder_Types::LIBRARY . '_' . $field, $value );
		}

		return (int) $id;
	}

	/**
	 * Act on a deck save that has already happened.
	 *
	 * @param WP_REST_Response|WP_HTTP_Response|WP_Error|mixed $response Whatever the route answered.
	 * @param array<string,mixed>                              $handler  The route's own handler.
	 * @param WP_REST_Request                                  $request  The request.
	 * @return mixed The response, with its values brought up to date.
	 */
	public static function after_save( $response, $handler, $request ) {
		$route = '/' . \Blueworx\PageEditor\v1\Rest::NS . '/' . Blueworx_Deck_Builder_Editor::DECK_SCREEN;
		if ( 'POST' !== $request->get_method() || $route !== $request->get_route() ) {
			return $response;
		}
		if ( ! $response instanceof WP_REST_Response ) {
			return $response;
		}

		$body = $response->get_data();
		if ( ! is_array( $body ) || empty( $body['ok'] ) || ! isset( $body['values'] ) ) {
			return $response;
		}

		$deck = Blueworx_Deck_Builder_Deck::find( (int) $request->get_param( 'id' ) );
		if ( null === $deck ) {
			return $response;
		}

		$values  = (array) $body['values'];
		$changed = self::keep_ticked_rows( $deck, $values );
		$changed = self::insert_picked_entries( $deck, $values ) || $changed;

		if ( $changed ) {
			$body['values'] = $values;
			$response->set_data( $body );
		}

		return $response;
	}

	/**
	 * Copy every ticked estimate row into the library, then clear the ticks.
	 *
	 * @param Blueworx_Deck_Builder_Deck $deck   The deck.
	 * @param array<string,mixed>        $values Its values, changed in place.
	 * @return bool Whether anything moved.
	 */
	private static function keep_ticked_rows( Blueworx_Deck_Builder_Deck $deck, array &$values ) {
		$changed = false;

		foreach ( [ 'estimate', 'postlaunch' ] as $list ) {
			$rows  = isset( $values[ $list ] ) && is_array( $values[ $list ] ) ? $values[ $list ] : [];
			$moved = false;
			foreach ( $rows as $i => $row ) {
				if ( ! is_array( $row ) || empty( $row[ self::SAVE_CELL ] ) ) {
					continue;
				}
				self::keep_line_item( $row );
				$rows[ $i ][ self::SAVE_CELL ] = false;
				$moved                         = true;
			}
			if ( $moved ) {
				$values[ $list ] = array_values( $rows );
				self::write( $deck, $list, $values[ $list ] );
				$changed = true;
			}
		}

		return $changed;
	}

	/**
	 * Append every picked entry to the list it belongs in, then clear the pick.
	 *
	 * @param Blueworx_Deck_Builder_Deck $deck   The deck.
	 * @param array<string,mixed>        $values Its values, changed in place.
	 * @return bool Whether anything moved.
	 */
	private static function insert_picked_entries( Blueworx_Deck_Builder_Deck $deck, array &$values ) {
		$changed = false;

		foreach ( self::PICKERS as $list => $picker ) {
			$picked = isset( $values[ $picker ] ) && is_array( $values[ $picker ] ) ? $values[ $picker ] : [];
			if ( ! $picked ) {
				continue;
			}

			$rows = isset( $values[ $list ] ) && is_array( $values[ $list ] ) ? array_values( $values[ $list ] ) : [];
			foreach ( $picked as $entry_id ) {
				$rows[] = 'sections' === $list
					? self::section_row( (int) $entry_id )
					: self::line_item_row( (int) $entry_id );
			}

			$values[ $list ]   = $rows;
			$values[ $picker ] = [];
			self::write( $deck, $list, $rows );
			self::write( $deck, $picker, [] );
			$changed = true;
		}

		return $changed;
	}

	/**
	 * Put one field back, using the same meta key the editor library writes.
	 *
	 * @param Blueworx_Deck_Builder_Deck $deck  The deck.
	 * @param string                     $field Field id.
	 * @param mixed                      $value New value.
	 * @return void
	 */
	private static function write( Blueworx_Deck_Builder_Deck $deck, $field, $value ) {
		update_post_meta( $deck->id(), Blueworx_Deck_Builder_Types::DECK . '_' . $field, $value );
	}
}
