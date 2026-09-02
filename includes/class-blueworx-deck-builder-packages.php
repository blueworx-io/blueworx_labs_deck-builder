<?php
/**
 * Support packages, and the one rule that picks between them.
 *
 * @package Blueworx\DeckBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Packages are set up once and reused by every deck, so they live here rather
 * than on a deck. The recommendation rule lives here too, because it is the
 * only thing in the plugin that is genuinely a decision — and a decision made
 * in two places is a decision made wrong in one of them.
 */
final class Blueworx_Deck_Builder_Packages {

	/**
	 * Every package, in display order.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function all() {
		$posts = get_posts(
			[
				'post_type'   => Blueworx_Deck_Builder_Types::PACKAGE,
				'post_status' => [ 'draft', 'publish', 'private', 'pending' ],
				'numberposts' => 100,
			]
		);

		$packages = array_map( [ __CLASS__, 'read' ], $posts );

		usort(
			$packages,
			static function ( $a, $b ) {
				if ( $a['order'] === $b['order'] ) {
					return strcmp( $a['name'], $b['name'] );
				}
				return $a['order'] < $b['order'] ? -1 : 1;
			}
		);

		return $packages;
	}

	/**
	 * One package by id.
	 *
	 * @param int $id Post id.
	 * @return array<string,mixed>|null
	 */
	public static function find( $id ) {
		$post = get_post( (int) $id );
		if ( null === $post || Blueworx_Deck_Builder_Types::PACKAGE !== $post->post_type ) {
			return null;
		}
		return self::read( $post );
	}

	/**
	 * Read a package post into the shape the rest of the plugin uses.
	 *
	 * Hours are nullable on purpose: a package with no hours set is not a
	 * package with zero hours, it is a package nobody has finished setting up,
	 * and the difference decides whether it can be recommended at all.
	 *
	 * @param WP_Post $post Package post.
	 * @return array<string,mixed>
	 */
	private static function read( WP_Post $post ) {
		$hours_raw = get_post_meta( $post->ID, 'bw_deck_package_hours', true );
		$prices    = [];
		foreach ( array_keys( Blueworx_Deck_Builder_Types::currencies() ) as $code ) {
			$raw             = get_post_meta( $post->ID, 'bw_deck_package_price_' . strtolower( $code ), true );
			$prices[ $code ] = ( '' === $raw || null === $raw ) ? null : (float) $raw;
		}

		return [
			'id'         => (int) $post->ID,
			'name'       => (string) $post->post_title,
			'hours'      => ( '' === $hours_raw || null === $hours_raw ) ? null : (float) $hours_raw,
			'period'     => (string) get_post_meta( $post->ID, 'bw_deck_package_period', true ),
			'commitment' => (string) get_post_meta( $post->ID, 'bw_deck_package_commitment', true ),
			'order'      => (int) get_post_meta( $post->ID, 'bw_deck_package_order', true ),
			'eligible'   => (bool) get_post_meta( $post->ID, 'bw_deck_package_eligible', true ),
			'popular'    => (bool) get_post_meta( $post->ID, 'bw_deck_package_popular', true ),
			'benefits'   => (string) get_post_meta( $post->ID, 'bw_deck_package_benefits', true ),
			'prices'     => $prices,
		];
	}

	/**
	 * Which package to recommend for a given number of hours.
	 *
	 * The rule, in full, and in this order:
	 *
	 * - An override is an override: it wins, and what the automatic answer
	 *   would have been is worked out anyway so the editor can say so.
	 * - Nothing eligible with hours set: NONE. This is a configuration
	 *   problem, not an estimate problem, and it blocks publishing.
	 * - Otherwise, the smallest eligible package that covers the work. Never a
	 *   package with fewer hours than the total — under-allocating hours is
	 *   the one answer this rule may never give.
	 * - Nothing large enough: CUSTOM. Recommend nothing, flag for review.
	 *
	 * @param float  $package_total Hours the package has to cover.
	 * @param int    $override      Manually chosen package id, or 0.
	 * @param string $currency      Currency the deck displays.
	 * @return array<string,mixed>
	 */
	public static function recommend( $package_total, $override = 0, $currency = 'GBP' ) {
		$package_total = (float) $package_total;
		$eligible      = [];
		foreach ( self::all() as $package ) {
			if ( $package['eligible'] && null !== $package['hours'] && $package['hours'] > 0 ) {
				$eligible[] = $package;
			}
		}
		usort(
			$eligible,
			static function ( $a, $b ) {
				return $a['hours'] < $b['hours'] ? -1 : 1;
			}
		);

		$automatic = self::automatic( $eligible, $package_total );

		if ( $override > 0 ) {
			$chosen = self::find( $override );
			if ( null !== $chosen ) {
				return self::result( 'OVERRIDE', $chosen, $package_total, $currency, $automatic, $eligible );
			}
		}

		return self::result( $automatic['state'], $automatic['package'], $package_total, $currency, null, $eligible );
	}

	/**
	 * The answer the rule gives on its own, before any override.
	 *
	 * @param array<int,array<string,mixed>> $eligible      Eligible packages, smallest first.
	 * @param float                          $package_total Hours to cover.
	 * @return array<string,mixed>
	 */
	private static function automatic( array $eligible, $package_total ) {
		if ( ! $eligible ) {
			return [ 'state' => 'NONE', 'package' => null ];
		}
		foreach ( $eligible as $package ) {
			if ( $package['hours'] >= $package_total ) {
				return [
					'state'   => ( (float) $package['hours'] === (float) $package_total ) ? 'EXACT' : 'OK',
					'package' => $package,
				];
			}
		}
		return [ 'state' => 'CUSTOM', 'package' => null ];
	}

	/**
	 * Assemble everything a screen needs to explain the recommendation.
	 *
	 * @param string                         $state         Rule state.
	 * @param array<string,mixed>|null       $package       Chosen package.
	 * @param float                          $package_total Hours to cover.
	 * @param string                         $currency      Deck currency.
	 * @param array<string,mixed>|null       $automatic     What the rule said, when overridden.
	 * @param array<int,array<string,mixed>> $eligible      Eligible packages.
	 * @return array<string,mixed>
	 */
	private static function result( $state, $package, $package_total, $currency, $automatic, array $eligible ) {
		$largest = $eligible ? end( $eligible ) : null;

		return [
			'state'     => $state,
			'package'   => $package,
			'total'     => $package_total,
			'currency'  => $currency,
			'remaining' => ( null === $package || null === $package['hours'] ) ? null : ( (float) $package['hours'] - $package_total ),
			'automatic' => $automatic,
			'eligible'  => $eligible,
			'largest'   => $largest,
			'reason'    => self::reason( $state, $package_total, $largest ),
		];
	}

	/**
	 * The sentence under the recommendation panel.
	 *
	 * @param string                   $state         Rule state.
	 * @param float                    $package_total Hours to cover.
	 * @param array<string,mixed>|null $largest       Largest eligible package.
	 * @return string
	 */
	private static function reason( $state, $package_total, $largest ) {
		switch ( $state ) {
			case 'NONE':
				return __( 'No package has included hours set, so nothing can be recommended.', 'blueworx-labs-deck-builder' );

			case 'CUSTOM':
				return sprintf(
					/* translators: 1: calculated hours, 2: largest package name, 3: that package's hours. */
					__( 'The calculated work is %1$s hours, above %2$s at %3$s hours. This deck needs a custom package.', 'blueworx-labs-deck-builder' ),
					self::hours( $package_total ),
					null === $largest ? '—' : $largest['name'],
					null === $largest ? '—' : self::hours( $largest['hours'] )
				);

			case 'OVERRIDE':
				return __( 'Chosen by hand. The client sees this as the selected recommendation.', 'blueworx-labs-deck-builder' );

			default:
				return sprintf(
					/* translators: %s: calculated hours. */
					__( 'Smallest eligible package with at least %s hours.', 'blueworx-labs-deck-builder' ),
					self::hours( $package_total )
				);
		}
	}

	/**
	 * The package as the client sees it: a name, a price and what is included.
	 * No hours arithmetic, no eligibility, no override messaging — none of
	 * that is the client's business.
	 *
	 * @param array<string,mixed> $package      The recommended package.
	 * @param string              $currency     Deck currency.
	 * @param array<int,int>      $alternatives Package ids shown alongside.
	 * @return array<string,mixed>
	 */
	public static function client_view( array $package, $currency, array $alternatives = [] ) {
		$others = [];
		foreach ( $alternatives as $id ) {
			if ( (int) $id === $package['id'] ) {
				continue;
			}
			$other = self::find( (int) $id );
			if ( null !== $other ) {
				$others[] = self::card( $other, $currency );
			}
		}

		$view                 = self::card( $package, $currency );
		$view['currency']     = $currency;
		$view['alternatives'] = $others;
		return $view;
	}

	/**
	 * One package as a card of plain values.
	 *
	 * @param array<string,mixed> $package  Package.
	 * @param string              $currency Deck currency.
	 * @return array<string,mixed>
	 */
	private static function card( array $package, $currency ) {
		return [
			'name'       => $package['name'],
			'hours'      => $package['hours'],
			'period'     => $package['period'],
			'commitment' => $package['commitment'],
			'popular'    => $package['popular'],
			'benefits'   => array_values(
				array_filter(
					array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $package['benefits'] ) )
				)
			),
			'price'      => self::price( $package, $currency ),
		];
	}

	/**
	 * A package's price in one currency, already written the way it is shown.
	 * Null when this package has no price in this currency — a deck may not
	 * display a currency a package has no price in, and saying "0" would be a
	 * lie rather than a gap.
	 *
	 * @param array<string,mixed> $package  Package.
	 * @param string              $currency Currency code.
	 * @return string|null
	 */
	public static function price( array $package, $currency ) {
		$currency = Blueworx_Deck_Builder_Deck::clean_currency( $currency );
		$amount   = $package['prices'][ $currency ] ?? null;
		if ( null === $amount ) {
			return null;
		}
		$symbol = Blueworx_Deck_Builder_Types::currencies()[ $currency ]['symbol'];
		return $symbol . number_format_i18n( (float) $amount, 0 );
	}

	/**
	 * Hours, written without a trailing ".0" on a whole number.
	 *
	 * @param float|null $hours Hours.
	 * @return string
	 */
	public static function hours( $hours ) {
		if ( null === $hours ) {
			return '—';
		}
		$hours = (float) $hours;
		return number_format_i18n( $hours, ( floor( $hours ) === $hours ) ? 0 : 1 );
	}

	/**
	 * Packages as page editor select options.
	 *
	 * @param string $empty_label Label for the "no choice" option.
	 * @return array<int,array<string,string>>
	 */
	public static function options( $empty_label = '' ) {
		$out = [];
		if ( '' !== $empty_label ) {
			$out[] = [ 'value' => '0', 'label' => $empty_label ];
		}
		foreach ( self::all() as $package ) {
			$out[] = [
				'value' => (string) $package['id'],
				'label' => $package['name'] . ( null === $package['hours'] ? '' : ' · ' . self::hours( $package['hours'] ) . ' hrs' ),
			];
		}
		return $out;
	}

	/**
	 * The first eligible package with no hours set, if there is one. The
	 * dashboard and the editor both warn about it, because it is a package
	 * that looks configured and silently never gets recommended.
	 *
	 * @return array<string,mixed>|null
	 */
	public static function missing_hours() {
		foreach ( self::all() as $package ) {
			if ( $package['eligible'] && ( null === $package['hours'] || $package['hours'] <= 0 ) ) {
				return $package;
			}
		}
		return null;
	}
}
