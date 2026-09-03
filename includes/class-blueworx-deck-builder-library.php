<?php
/**
 * The content library: the one place a deck's content comes from.
 *
 * @package Blueworx\DeckBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every section and every line item the business uses lives here, once. A new
 * deck is a copy of the whole library, and from that moment the copy is the
 * deck's own — editing a library entry changes what the next deck starts with
 * and leaves every deck already made alone.
 *
 * There is no second list anywhere. A deck cannot carry a section that is not
 * in the library, which is the deliberate trade: anything bespoke goes into
 * the library first.
 */
final class Blueworx_Deck_Builder_Library {

	/**
	 * Which sort of row an entry holds.
	 */
	const SECTION   = 'section';
	const LINE_ITEM = 'line_item';

	/**
	 * Which of a deck's two estimates a line item belongs to. They keep
	 * separate totals and group by different phases, so a line item that did
	 * not say would have to land on both.
	 */
	const LIST_ESTIMATE   = 'estimate';
	const LIST_POSTLAUNCH = 'postlaunch';

	/**
	 * Every entry of one sort, in the order decks present them.
	 *
	 * Order is a stored number rather than the title, because these are slides
	 * in a presentation: alphabetical would open every deck on the call to
	 * action. Entries sharing a number fall back to their title so the list is
	 * at least stable.
	 *
	 * @param string      $type Entry type.
	 * @param string|null $which For a line item, which estimate; null for all.
	 * @return array<int,WP_Post>
	 */
	public static function entries( $type, $which = null ) {
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

		$of_type = array_values(
			array_filter(
				$posts,
				static function ( $post ) use ( $type, $which ) {
					if ( self::type_of( $post->ID ) !== $type ) {
						return false;
					}
					return null === $which || self::list_of( $post->ID ) === $which;
				}
			)
		);

		usort(
			$of_type,
			static function ( $a, $b ) {
				$order = self::order_of( $a->ID ) <=> self::order_of( $b->ID );
				return 0 !== $order ? $order : strcasecmp( $a->post_title, $b->post_title );
			}
		);

		return $of_type;
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
	 * Which estimate a line item belongs to.
	 *
	 * Anything not explicitly post-launch is project work — an entry written
	 * before the library made the distinction was a project line item, and a
	 * row that quietly moved lists on upgrade would change a deck's totals.
	 *
	 * @param int $id Entry id.
	 * @return string
	 */
	public static function list_of( $id ) {
		return self::LIST_POSTLAUNCH === self::field( $id, 'list' ) ? self::LIST_POSTLAUNCH : self::LIST_ESTIMATE;
	}

	/**
	 * Where an entry sits in its own list.
	 *
	 * @param int $id Entry id.
	 * @return int
	 */
	public static function order_of( $id ) {
		return (int) self::field( $id, 'order' );
	}

	/**
	 * How one entry is described in a list of them.
	 *
	 * @param int $id Entry id.
	 * @return string
	 */
	public static function describe( $id ) {
		if ( self::LINE_ITEM !== self::type_of( $id ) ) {
			return __( 'Section', 'blueworx-labs-deck-builder' );
		}
		return self::LIST_POSTLAUNCH === self::list_of( $id )
			? __( 'Post-launch line item', 'blueworx-labs-deck-builder' )
			: __( 'Project line item', 'blueworx-labs-deck-builder' );
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

	/* --- What a new deck starts as ----------------------------------------- */

	/**
	 * The whole library, as the three lists a new deck holds.
	 *
	 * Everything arrives ticked. A row that landed excluded would read as the
	 * copy having half-failed, and leaving work out of the package calculation
	 * by default would quietly under-state what a retainer has to cover.
	 *
	 * @return array<string,array<int,array<string,mixed>>>
	 */
	public static function starting_lists() {
		$sections = [];
		foreach ( self::entries( self::SECTION ) as $post ) {
			$sections[] = self::section_row( $post->ID );
		}

		$estimate = [];
		foreach ( self::entries( self::LINE_ITEM, self::LIST_ESTIMATE ) as $post ) {
			$estimate[] = self::line_item_row( $post->ID );
		}

		$postlaunch = [];
		foreach ( self::entries( self::LINE_ITEM, self::LIST_POSTLAUNCH ) as $post ) {
			$postlaunch[] = self::line_item_row( $post->ID );
		}

		return [
			'sections'   => $sections,
			'estimate'   => $estimate,
			'postlaunch' => $postlaunch,
		];
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
			// A service slide shows the hours that service usually takes, and
			// that is the same figure on every deck — so it comes from the
			// library rather than being retyped per client. A deck can still
			// change its own copy.
			'hours'   => (float) self::field( $id, 'hours' ),
			'strap'   => (string) self::field( $id, 'strap' ),
			'note'    => (string) self::field( $id, 'note' ),
			'visible' => true,
		];
	}

	/**
	 * An entry as a row for one of the deck's estimate lists.
	 *
	 * The internal note is deliberately not carried: it is written about one
	 * client, on one deck, and has no business starting the next one.
	 *
	 * @param int $id Entry id.
	 * @return array<string,mixed>
	 */
	public static function line_item_row( $id ) {
		$post = get_post( (int) $id );
		return [
			'title'       => null === $post ? '' : $post->post_title,
			'desc'        => (string) self::field( $id, 'desc' ),
			'phase'       => (string) self::field( $id, 'phase' ),
			'hours'       => (float) self::field( $id, 'hours' ),
			'note'        => '',
			'in_total'    => true,
			'show_client' => true,
			'in_package'  => true,
		];
	}
}
