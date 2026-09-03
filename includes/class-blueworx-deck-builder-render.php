<?php
/**
 * The client deck: its own document, start to finish.
 *
 * @package Blueworx\DeckBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * The public presentation.
 *
 * This prints a whole HTML document itself rather than going through the theme.
 * That is the point: the deck is what a client sees of BlueWorx, and it must
 * not pick up the site theme's type, another plugin's stylesheet, or a cookie
 * banner. Nothing here is enqueued through wp_head() for the same reason —
 * every other plugin on the site hooks that.
 */
final class Blueworx_Deck_Builder_Render {

	/**
	 * Render a deck.
	 *
	 * @param Blueworx_Deck_Builder_Deck $deck Deck.
	 * @return void
	 */
	public static function deck( Blueworx_Deck_Builder_Deck $deck ) {
		$payload  = $deck->client_payload();
		$sections = self::expand( $payload );

		self::head( $payload['client'] . ' · ' . $payload['title'] );
		?>
		<div class="bwd" data-bwd-deck>
			<div class="bwd-stagewrap" data-bwd-stagewrap>
				<?php foreach ( $sections as $index => $section ) : ?>
					<?php self::section( $section, $payload, $index + 1, count( $sections ) ); ?>
				<?php endforeach; ?>
			</div>

			<nav class="bwd-nav" data-bwd-nav aria-label="<?php esc_attr_e( 'Deck sections', 'blueworx-labs-deck-builder' ); ?>">
				<button class="bwd-nav__arrow" type="button" data-bwd-prev aria-label="<?php esc_attr_e( 'Previous section', 'blueworx-labs-deck-builder' ); ?>">&#8592;</button>
				<span class="bwd-nav__dots">
					<?php foreach ( $sections as $index => $section ) : ?>
						<button class="bwd-nav__dot" type="button" data-bwd-go="<?php echo esc_attr( (string) $index ); ?>" aria-label="<?php echo esc_attr( self::section_name( $section, $index + 1 ) ); ?>"></button>
					<?php endforeach; ?>
				</span>
				<button class="bwd-nav__arrow" type="button" data-bwd-next aria-label="<?php esc_attr_e( 'Next section', 'blueworx-labs-deck-builder' ); ?>">&#8594;</button>
			</nav>
		</div>
		<?php
		self::foot();
	}

	/**
	 * The password gate.
	 *
	 * @param Blueworx_Deck_Builder_Deck $deck   Deck.
	 * @param bool                       $failed Whether a wrong password was just given.
	 * @return void
	 */
	public static function password_page( Blueworx_Deck_Builder_Deck $deck, $failed ) {
		self::head( __( 'This deck is protected', 'blueworx-labs-deck-builder' ) );
		?>
		<div class="bwd bwd--gate">
			<form class="bwd-gate" method="post">
				<p class="bwd-gate__eyebrow"><?php esc_html_e( 'Protected', 'blueworx-labs-deck-builder' ); ?></p>
				<h1 class="bwd-gate__title"><?php echo esc_html( $deck->title() ); ?></h1>
				<p class="bwd-gate__lede"><?php esc_html_e( 'This deck needs a password. It was sent separately from the link.', 'blueworx-labs-deck-builder' ); ?></p>
				<label class="bwd-gate__label" for="bwd-password"><?php esc_html_e( 'Password', 'blueworx-labs-deck-builder' ); ?></label>
				<input class="bwd-gate__input" id="bwd-password" name="bw_deck_password" type="password" autocomplete="current-password" required />
				<?php if ( $failed ) : ?>
					<p class="bwd-gate__error"><?php esc_html_e( 'That password is not right. Check the message it came in, and try again.', 'blueworx-labs-deck-builder' ); ?></p>
				<?php endif; ?>
				<button class="bwd-gate__btn" type="submit"><?php esc_html_e( 'Open the deck', 'blueworx-labs-deck-builder' ); ?></button>
			</form>
		</div>
		<?php
		self::foot( false );
	}

	/* --- Document ----------------------------------------------------------- */

	/**
	 * Everything above the deck.
	 *
	 * @param string $title Document title.
	 * @return void
	 */
	private static function head( $title ) {
		$base = BLUEWORX_DECK_BUILDER_URL;
		?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( str_replace( '_', '-', get_locale() ) ); ?>">
<head>
<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="robots" content="noindex, nofollow, noarchive" />
<title><?php echo esc_html( wp_strip_all_tags( $title ) ); ?></title>
<?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- the client deck is its own document: no wp_head(), so no queue to enqueue into. This is the only stylesheet on the page, by design. ?>
<link rel="stylesheet" href="<?php echo esc_url( $base . 'assets/deck.css?ver=' . BLUEWORX_DECK_BUILDER_VERSION ); ?>" />
</head>
<body class="bwd-body">
		<?php
	}

	/**
	 * Everything below it.
	 *
	 * @param bool $with_script Whether the deck's navigation is needed.
	 * @return void
	 */
	private static function foot( $with_script = true ) {
		if ( $with_script ) {
			printf(
				// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- see the stylesheet above; there is no wp_footer() on this document either.
				'<script src="%s"></script>',
				esc_url( BLUEWORX_DECK_BUILDER_URL . 'assets/deck.js?ver=' . BLUEWORX_DECK_BUILDER_VERSION )
			);
		}
		echo '</body></html>';
	}

	/* --- Sections ------------------------------------------------------------ */

	/**
	 * Turn the deck's section list into the slides that actually get shown: a
	 * case study row becomes one slide per case study the deck has chosen, and
	 * a generated section that has nothing to show is dropped rather than
	 * rendered empty.
	 *
	 * @param array<string,mixed> $payload Client payload.
	 * @return array<int,array<string,mixed>>
	 */
	private static function expand( array $payload ) {
		$out = [];
		foreach ( $payload['sections'] as $section ) {
			if ( 'casestudy' === $section['kind'] ) {
				foreach ( $payload['case_studies'] as $study ) {
					$out[] = array_merge( $section, [ 'study' => $study ] );
				}
				continue;
			}
			if ( ! self::has_content( $section, $payload ) ) {
				continue;
			}
			$out[] = $section;
		}
		return $out;
	}

	/**
	 * Whether a generated section has anything to generate from. An empty
	 * timeline is better left out than shown as an empty grid.
	 *
	 * @param array<string,mixed> $section Section.
	 * @param array<string,mixed> $payload Client payload.
	 * @return bool
	 */
	private static function has_content( array $section, array $payload ) {
		switch ( $section['kind'] ) {
			case 'estimate':
				return (bool) $payload['estimate'];
			case 'postlaunch':
				return (bool) $payload['postlaunch'];
			case 'timeline':
				return (bool) $payload['timeline'];
			case 'package':
				return null !== $payload['package'];
			default:
				return true;
		}
	}

	/**
	 * The line every commercial slide carries. Hours and prices on a proposal
	 * are estimates, and saying so once at the bottom of one slide is not
	 * saying it — so it appears wherever a number does.
	 *
	 * @return void
	 */
	private static function estimate_note() {
		?>
		<p class="bwd-note bwd-note--estimate"><?php esc_html_e( 'Hours and pricing shown are estimates and are subject to change as the work is scoped in detail.', 'blueworx-labs-deck-builder' ); ?></p>
		<?php
	}

	/**
	 * What a section is called in the navigation.
	 *
	 * @param array<string,mixed> $section Section.
	 * @param int                 $number  Its position.
	 * @return string
	 */
	private static function section_name( array $section, $number ) {
		if ( isset( $section['study'] ) ) {
			return $section['study']['name'];
		}
		return '' !== $section['title'] ? $section['title'] : sprintf(
			/* translators: %d: section number. */
			__( 'Section %d', 'blueworx-labs-deck-builder' ),
			$number
		);
	}

	/**
	 * One slide.
	 *
	 * @param array<string,mixed> $section Section.
	 * @param array<string,mixed> $payload Client payload.
	 * @param int                 $number  Its position.
	 * @param int                 $total   How many slides there are.
	 * @return void
	 */
	private static function section( array $section, array $payload, $number, $total ) {
		$dark  = in_array( $section['kind'], [ 'cover', 'service', 'package', 'hosting', 'process', 'casestudy', 'cta' ], true );
		$class = 'bwd-slide bwd-slide--' . ( $dark ? 'dark' : 'light' ) . ' bwd-slide--' . $section['kind'];
		?>
		<section class="<?php echo esc_attr( $class ); ?>" data-bwd-slide aria-label="<?php echo esc_attr( self::section_name( $section, $number ) ); ?>">
			<div class="bwd-stage">
				<?php
				switch ( $section['kind'] ) {
					case 'cover':
						self::cover( $section, $payload );
						break;
					case 'what':
						self::pillars( $section, $payload );
						break;
					case 'service':
						self::service( $section, $payload );
						break;
					case 'estimate':
						self::estimate( $section, $payload );
						break;
					case 'package':
						self::package( $section, $payload );
						break;
					case 'timeline':
						self::timeline( $section, $payload );
						break;
					case 'postlaunch':
						self::postlaunch( $section, $payload );
						break;
					case 'hosting':
						self::hosting( $section, $payload );
						break;
					case 'process':
						self::process( $section, $payload );
						break;
					case 'projects':
						self::projects( $section, $payload );
						break;
					case 'casestudy':
						self::case_study( $section );
						break;
					default:
						self::cta( $section, $payload );
						break;
				}
				?>
				<?php if ( 'cover' !== $section['kind'] ) : ?>
					<p class="bwd-foot bwd-foot--left"><?php echo esc_html( $payload['client'] ); ?></p>
					<p class="bwd-foot bwd-foot--right"><?php echo esc_html( sprintf( '%02d / %02d', $number, $total ) ); ?></p>
				<?php endif; ?>
			</div>
		</section>
		<?php
	}

	/**
	 * The cover.
	 *
	 * @param array<string,mixed> $section Section.
	 * @param array<string,mixed> $payload Client payload.
	 * @return void
	 */
	private static function cover( array $section, array $payload ) {
		?>
		<span class="bwd-blob bwd-blob--one"></span>
		<span class="bwd-blob bwd-blob--two"></span>
		<div class="bwd-cover">
			<div class="bwd-cover__top">
				<?php self::logo( $payload ); ?>
				<p class="bwd-cover__kind"><?php esc_html_e( 'Support proposal', 'blueworx-labs-deck-builder' ); ?></p>
			</div>
			<div class="bwd-cover__mid">
				<?php if ( '' !== $section['eyebrow'] ) : ?>
					<p class="bwd-eyebrow"><?php echo esc_html( $section['eyebrow'] ); ?></p>
				<?php endif; ?>
				<h1 class="bwd-display"><?php echo esc_html( $payload['title'] ); ?></h1>
				<?php if ( '' !== $payload['subtitle'] ) : ?>
					<p class="bwd-lede"><?php echo esc_html( $payload['subtitle'] ); ?></p>
				<?php endif; ?>
			</div>
			<div class="bwd-cover__meta">
				<?php if ( '' !== $payload['prepared_for'] ) : ?>
					<div class="bwd-pair">
						<p class="bwd-pair__label"><?php esc_html_e( 'Prepared for', 'blueworx-labs-deck-builder' ); ?></p>
						<p class="bwd-pair__value"><?php echo esc_html( $payload['prepared_for'] ); ?></p>
					</div>
				<?php endif; ?>
				<?php if ( '' !== $payload['prepared_date'] ) : ?>
					<div class="bwd-pair">
						<p class="bwd-pair__label"><?php esc_html_e( 'Date', 'blueworx-labs-deck-builder' ); ?></p>
						<p class="bwd-pair__value"><?php echo esc_html( mysql2date( 'j F Y', $payload['prepared_date'] ) ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * What we do: four pillars.
	 *
	 * @param array<string,mixed> $section Section.
	 * @param array<string,mixed> $payload Client payload.
	 * @return void
	 */
	private static function pillars( array $section, array $payload ) {
		$points = self::lines( $section['points'] );
		if ( ! $points ) {
			$points = [
				__( 'Website design and development', 'blueworx-labs-deck-builder' ),
				__( 'Secure hosting and infrastructure', 'blueworx-labs-deck-builder' ),
				__( 'Long-term digital partnership', 'blueworx-labs-deck-builder' ),
				__( 'Continuous optimisation', 'blueworx-labs-deck-builder' ),
			];
		}
		?>
		<div class="bwd-head">
			<div>
				<?php self::eyebrow( $section ); ?>
				<h2 class="bwd-h2"><?php echo esc_html( '' !== $section['title'] ? $section['title'] : __( 'What we do', 'blueworx-labs-deck-builder' ) ); ?></h2>
				<?php if ( '' !== $section['body'] ) : ?>
					<p class="bwd-intro"><?php echo esc_html( $section['body'] ); ?></p>
				<?php endif; ?>
			</div>
			<?php self::logo( $payload ); ?>
		</div>
		<div class="bwd-pillars">
			<?php foreach ( $points as $index => $point ) : ?>
				<div class="bwd-pillar">
					<p class="bwd-pillar__n"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></p>
					<p class="bwd-pillar__title"><?php echo esc_html( $point ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * One service, its hours and its points.
	 *
	 * @param array<string,mixed> $section Section.
	 * @param array<string,mixed> $payload Client payload.
	 * @return void
	 */
	private static function service( array $section, array $payload ) {
		$points = self::lines( $section['points'] );
		?>
		<div class="bwd-two">
			<div class="bwd-two__left">
				<?php self::eyebrow( $section ); ?>
				<h2 class="bwd-h1"><?php echo esc_html( $section['title'] ); ?></h2>
				<?php if ( '' !== $section['body'] ) : ?>
					<p class="bwd-lede"><?php echo esc_html( $section['body'] ); ?></p>
				<?php endif; ?>
				<?php if ( $section['hours'] > 0 ) : ?>
					<div class="bwd-hours">
						<p class="bwd-hours__n"><?php echo esc_html( Blueworx_Deck_Builder_Packages::hours( $section['hours'] ) ); ?></p>
						<p class="bwd-hours__label"><?php esc_html_e( 'hours estimated', 'blueworx-labs-deck-builder' ); ?></p>
					</div>
				<?php endif; ?>
				<?php if ( '' !== $section['strap'] ) : ?>
					<p class="bwd-strap"><?php echo esc_html( $section['strap'] ); ?></p>
				<?php endif; ?>
			</div>
			<div class="bwd-two__right">
				<?php foreach ( $points as $index => $point ) : ?>
					<div class="bwd-point">
						<p class="bwd-point__n"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></p>
						<p class="bwd-point__text"><?php echo esc_html( $point ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		unset( $payload );
	}

	/**
	 * The estimate summary, phase by phase.
	 *
	 * @param array<string,mixed> $section Section.
	 * @param array<string,mixed> $payload Client payload.
	 * @return void
	 */
	private static function estimate( array $section, array $payload ) {
		$phases = [];
		foreach ( $payload['estimate'] as $item ) {
			$phase = '' !== $item['phase'] ? $item['phase'] : __( 'Other work', 'blueworx-labs-deck-builder' );
			if ( ! isset( $phases[ $phase ] ) ) {
				$phases[ $phase ] = [ 'hours' => 0.0, 'items' => [] ];
			}
			$phases[ $phase ]['items'][] = $item['title'];
			if ( $item['in_total'] ) {
				$phases[ $phase ]['hours'] += $item['hours'];
			}
		}
		?>
		<div class="bwd-head">
			<div>
				<?php self::eyebrow( $section ); ?>
				<h2 class="bwd-h2"><?php echo esc_html( '' !== $section['title'] ? $section['title'] : __( 'Estimate summary', 'blueworx-labs-deck-builder' ) ); ?></h2>
			</div>
			<div class="bwd-total">
				<p class="bwd-total__label"><?php esc_html_e( 'Total project estimate', 'blueworx-labs-deck-builder' ); ?></p>
				<p class="bwd-total__n"><?php echo esc_html( Blueworx_Deck_Builder_Packages::hours( $payload['totals']['project'] ) ); ?></p>
			</div>
		</div>
		<div class="bwd-phases">
			<?php foreach ( $phases as $name => $phase ) : ?>
				<div class="bwd-phase">
					<div class="bwd-phase__row">
						<p class="bwd-phase__name"><?php echo esc_html( $name ); ?></p>
						<p class="bwd-phase__n"><?php echo esc_html( Blueworx_Deck_Builder_Packages::hours( $phase['hours'] ) ); ?></p>
					</div>
					<p class="bwd-phase__items"><?php echo esc_html( implode( ' · ', $phase['items'] ) ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="bwd-note"><?php esc_html_e( 'Estimates cover the work described above. Two rounds of revisions are included at each stage.', 'blueworx-labs-deck-builder' ); ?></p>
		<?php
		self::estimate_note();
	}

	/**
	 * Hosting and management: the monthly fee, and what it covers.
	 *
	 * @param array<string,mixed> $section Section.
	 * @param array<string,mixed> $payload Client payload.
	 * @return void
	 */
	private static function hosting( array $section, array $payload ) {
		$hosting = $payload['hosting'];
		$points  = self::lines( $section['points'] );
		?>
		<div class="bwd-two">
			<div class="bwd-two__left">
				<?php self::eyebrow( $section, __( 'Infrastructure', 'blueworx-labs-deck-builder' ) ); ?>
				<h2 class="bwd-h1"><?php echo esc_html( '' !== $section['title'] ? $section['title'] : __( 'Hosting and management', 'blueworx-labs-deck-builder' ) ); ?></h2>
				<?php if ( '' !== $section['body'] ) : ?>
					<p class="bwd-lede"><?php echo esc_html( $section['body'] ); ?></p>
				<?php endif; ?>
				<?php // The page stands on its own: it describes the platform whether or not a fee has been quoted for this client yet. ?>
				<?php if ( null !== $hosting ) : ?>
					<div class="bwd-fee">
						<p class="bwd-fee__n"><?php echo esc_html( $hosting['price'] ); ?></p>
						<p class="bwd-fee__label"><?php echo esc_html( $hosting['period'] ); ?></p>
					</div>
					<?php if ( $hosting['hours'] > 0 ) : ?>
						<p class="bwd-fee__hours">
							<?php
							printf(
								/* translators: %s: hours. */
								esc_html__( 'Around %s hours of managed upkeep a month.', 'blueworx-labs-deck-builder' ),
								esc_html( Blueworx_Deck_Builder_Packages::hours( $hosting['hours'] ) )
							);
							?>
						</p>
					<?php endif; ?>
				<?php endif; ?>
				<?php if ( '' !== $section['strap'] ) : ?>
					<p class="bwd-strap"><?php echo esc_html( $section['strap'] ); ?></p>
				<?php endif; ?>
			</div>
			<div class="bwd-two__right">
				<?php foreach ( $points as $index => $point ) : ?>
					<div class="bwd-point">
						<p class="bwd-point__n"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></p>
						<p class="bwd-point__text"><?php echo esc_html( $point ); ?></p>
					</div>
				<?php endforeach; ?>
				<?php self::estimate_note(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * The recommended package, with any alternatives beside it.
	 *
	 * @param array<string,mixed> $section Section.
	 * @param array<string,mixed> $payload Client payload.
	 * @return void
	 */
	private static function package( array $section, array $payload ) {
		$package = $payload['package'];
		$others  = array_slice( $package['alternatives'], 0, 2 );
		$planned = $payload['totals']['project'] + $payload['totals']['postlaunch'];
		?>
		<?php self::eyebrow( $section ); ?>
		<h2 class="bwd-h1"><?php echo esc_html( $package['name'] ); ?></h2>
		<p class="bwd-lede">
			<?php
			printf(
				/* translators: %s: hours of work planned for the client. */
				esc_html__( 'Built around the %s hours of work planned for this site.', 'blueworx-labs-deck-builder' ),
				esc_html( Blueworx_Deck_Builder_Packages::hours( $planned ) )
			);
			?>
		</p>
		<div class="bwd-packages bwd-packages--<?php echo esc_attr( (string) ( count( $others ) + 1 ) ); ?>">
			<div class="bwd-pkg bwd-pkg--main">
				<p class="bwd-pkg__eyebrow"><?php esc_html_e( 'Recommended', 'blueworx-labs-deck-builder' ); ?></p>
				<p class="bwd-pkg__name"><?php echo esc_html( $package['name'] ); ?></p>
				<?php if ( null !== $package['price'] ) : ?>
					<p class="bwd-pkg__price"><?php echo esc_html( $package['price'] ); ?> <span class="bwd-pkg__period"><?php echo esc_html( $package['period'] ); ?></span></p>
				<?php endif; ?>
				<p class="bwd-pkg__hours">
					<?php
					printf(
						/* translators: %s: hours included. */
						esc_html__( '%s hours included', 'blueworx-labs-deck-builder' ),
						esc_html( Blueworx_Deck_Builder_Packages::hours( $package['hours'] ) )
					);
					?>
				</p>
				<ul class="bwd-pkg__list">
					<?php foreach ( $package['benefits'] as $benefit ) : ?>
						<li class="bwd-pkg__item"><?php echo esc_html( $benefit ); ?></li>
					<?php endforeach; ?>
				</ul>
				<?php if ( '' !== $package['commitment'] ) : ?>
					<p class="bwd-pkg__commit"><?php echo esc_html( $package['commitment'] ); ?></p>
				<?php endif; ?>
			</div>
			<?php foreach ( $others as $alt ) : ?>
				<div class="bwd-pkg bwd-pkg--alt">
					<p class="bwd-pkg__name"><?php echo esc_html( $alt['name'] ); ?></p>
					<?php if ( null !== $alt['price'] ) : ?>
						<p class="bwd-pkg__price"><?php echo esc_html( $alt['price'] ); ?> <span class="bwd-pkg__period"><?php echo esc_html( $alt['period'] ); ?></span></p>
					<?php endif; ?>
					<p class="bwd-pkg__hours">
						<?php
						printf(
							/* translators: %s: hours included. */
							esc_html__( '%s hours included', 'blueworx-labs-deck-builder' ),
							esc_html( Blueworx_Deck_Builder_Packages::hours( $alt['hours'] ) )
						);
						?>
					</p>
					<ul class="bwd-pkg__list">
						<?php foreach ( array_slice( $alt['benefits'], 0, 3 ) as $benefit ) : ?>
							<li class="bwd-pkg__item"><?php echo esc_html( $benefit ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		self::estimate_note();
	}

	/**
	 * The timeline.
	 *
	 * @param array<string,mixed> $section Section.
	 * @param array<string,mixed> $payload Client payload.
	 * @return void
	 */
	private static function timeline( array $section, array $payload ) {
		// A scale per stretch, not one for the slide. Each counts its own weeks
		// from week one, so a single scale would draw the shorter of the two as
		// a stub of a plan it has nothing to do with.
		$scale = [ 'pre' => 1, 'post' => 1 ];
		foreach ( $payload['timeline'] as $phase ) {
			$in           = 'post' === $phase['kind'] ? 'post' : 'pre';
			$scale[ $in ] = max( $scale[ $in ], $phase['end'] );
		}
		?>
		<?php self::eyebrow( $section ); ?>
		<h2 class="bwd-h2"><?php echo esc_html( '' !== $section['title'] ? $section['title'] : __( 'Project timeline', 'blueworx-labs-deck-builder' ) ); ?></h2>
		<div class="bwd-tl">
			<?php $band = ''; ?>
			<?php foreach ( $payload['timeline'] as $phase ) : ?>
				<?php
				// The two stretches get a heading between them. Everything up
				// to and including launch is a piece of work with an end;
				// everything after runs for as long as the client keeps us,
				// and one unbroken chart reads as the same commitment.
				$in = 'post' === $phase['kind'] ? 'post' : 'pre';
				if ( $in !== $band ) {
					$band = $in;
					printf(
						'<p class="bwd-tl__band">%s</p>',
						esc_html(
							'post' === $in
								? __( 'Post-launch', 'blueworx-labs-deck-builder' )
								: __( 'Development phase', 'blueworx-labs-deck-builder' )
						)
					);
				}

				$max   = $scale[ $in ];
				$left  = ( ( $phase['start'] - 1 ) / $max ) * 100;
				$width = max( 3.5, ( ( $phase['end'] - $phase['start'] + 1 ) / $max ) * 100 );
				$text  = '' !== $phase['milestone'] ? $phase['milestone'] : $phase['desc'];

				// A one-week phase is a sliver, and a sliver with words in it
				// reads as a mistake rather than as a label. Below a fifth of
				// the width the description moves out to the label column,
				// where there is room to read it.
				$inside = $width >= 20 ? $text : '';
				$beside = $width >= 20 ? '' : $text;
				?>
				<div class="bwd-tl__row">
					<div class="bwd-tl__label">
						<p class="bwd-tl__title"><?php echo esc_html( $phase['title'] ); ?></p>
						<p class="bwd-tl__range">
							<?php
							echo esc_html(
								$phase['start'] === $phase['end']
									? sprintf(
										/* translators: %d: week number. */
										__( 'Week %d', 'blueworx-labs-deck-builder' ),
										$phase['start']
									)
									: sprintf(
										/* translators: 1: first week, 2: last week. */
										__( 'Weeks %1$d–%2$d', 'blueworx-labs-deck-builder' ),
										$phase['start'],
										$phase['end']
									)
							);
							?>
						</p>
						<?php if ( '' !== $beside ) : ?>
							<p class="bwd-tl__aside"><?php echo esc_html( $beside ); ?></p>
						<?php endif; ?>
					</div>
					<div class="bwd-tl__track">
						<div class="bwd-tl__bar bwd-tl__bar--<?php echo esc_attr( $phase['kind'] ); ?>" style="left:<?php echo esc_attr( number_format( $left, 3, '.', '' ) ); ?>%;width:<?php echo esc_attr( number_format( $width, 3, '.', '' ) ); ?>%">
							<?php if ( '' !== $inside ) : ?>
								<span class="bwd-tl__text"><?php echo esc_html( $inside ); ?></span>
							<?php endif; ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="bwd-legend">
			<span class="bwd-key bwd-key--pre"></span><?php esc_html_e( 'Before launch', 'blueworx-labs-deck-builder' ); ?>
			<span class="bwd-key bwd-key--launch"></span><?php esc_html_e( 'Launch', 'blueworx-labs-deck-builder' ); ?>
			<span class="bwd-key bwd-key--post"></span><?php esc_html_e( 'After launch', 'blueworx-labs-deck-builder' ); ?>
		</div>
		<p class="bwd-note">
			<?php
			printf(
				/* translators: %d: hours of work assumed per working day. */
				esc_html__( 'Worked out from the estimated hours, at %d hours of work a day. Weeks are indicative and confirmed at kick-off.', 'blueworx-labs-deck-builder' ),
				(int) Blueworx_Deck_Builder_Types::HOURS_PER_DAY
			);
			?>
		</p>
		<?php
		self::estimate_note();
	}

	/**
	 * Post-launch work.
	 *
	 * @param array<string,mixed> $section Section.
	 * @param array<string,mixed> $payload Client payload.
	 * @return void
	 */
	private static function postlaunch( array $section, array $payload ) {
		?>
		<div class="bwd-head">
			<div>
				<?php self::eyebrow( $section ); ?>
				<h2 class="bwd-h2"><?php echo esc_html( '' !== $section['title'] ? $section['title'] : __( 'After launch', 'blueworx-labs-deck-builder' ) ); ?></h2>
				<?php if ( '' !== $section['body'] ) : ?>
					<p class="bwd-intro"><?php echo esc_html( $section['body'] ); ?></p>
				<?php endif; ?>
			</div>
			<div class="bwd-total">
				<p class="bwd-total__label"><?php esc_html_e( 'Ongoing estimate', 'blueworx-labs-deck-builder' ); ?></p>
				<p class="bwd-total__n"><?php echo esc_html( Blueworx_Deck_Builder_Packages::hours( $payload['totals']['postlaunch'] ) ); ?></p>
			</div>
		</div>
		<div class="bwd-cards">
			<?php foreach ( array_slice( $payload['postlaunch'], 0, 6 ) as $item ) : ?>
				<div class="bwd-card">
					<p class="bwd-card__label"><?php echo esc_html( $item['phase'] ); ?></p>
					<p class="bwd-card__title"><?php echo esc_html( $item['title'] ); ?></p>
					<p class="bwd-card__text"><?php echo esc_html( $item['desc'] ); ?></p>
					<p class="bwd-card__n">
						<?php
						printf(
							/* translators: %s: hours. */
							esc_html__( '%s hrs', 'blueworx-labs-deck-builder' ),
							esc_html( Blueworx_Deck_Builder_Packages::hours( $item['hours'] ) )
						);
						?>
					</p>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		self::estimate_note();
	}

	/**
	 * Our process.
	 *
	 * @param array<string,mixed> $section Section.
	 * @param array<string,mixed> $payload Client payload.
	 * @return void
	 */
	private static function process( array $section, array $payload ) {
		$steps = self::lines( $section['points'] );
		if ( ! $steps ) {
			$steps = [
				__( 'Discovery', 'blueworx-labs-deck-builder' ),
				__( 'Design', 'blueworx-labs-deck-builder' ),
				__( 'Development', 'blueworx-labs-deck-builder' ),
				__( 'Launch', 'blueworx-labs-deck-builder' ),
				__( 'Support', 'blueworx-labs-deck-builder' ),
			];
		}
		?>
		<?php self::eyebrow( $section ); ?>
		<h2 class="bwd-h2"><?php echo esc_html( '' !== $section['title'] ? $section['title'] : __( 'Our process', 'blueworx-labs-deck-builder' ) ); ?></h2>
		<?php if ( '' !== $section['body'] ) : ?>
			<p class="bwd-intro"><?php echo esc_html( $section['body'] ); ?></p>
		<?php endif; ?>
		<div class="bwd-steps">
			<?php foreach ( $steps as $index => $step ) : ?>
				<div class="bwd-step">
					<p class="bwd-step__n"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></p>
					<p class="bwd-step__title"><?php echo esc_html( $step ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="bwd-note"><?php esc_html_e( 'Every project is different — we adapt this process to fit your timeline, team and goals.', 'blueworx-labs-deck-builder' ); ?></p>
		<?php
		unset( $payload );
	}

	/**
	 * The lead-in to the case studies.
	 *
	 * @param array<string,mixed> $section Section.
	 * @param array<string,mixed> $payload Client payload.
	 * @return void
	 */
	private static function projects( array $section, array $payload ) {
		?>
		<div class="bwd-mid">
			<?php self::eyebrow( $section, __( 'Selected work', 'blueworx-labs-deck-builder' ) ); ?>
			<h2 class="bwd-display"><?php echo esc_html( '' !== $section['title'] ? $section['title'] : __( 'Past projects', 'blueworx-labs-deck-builder' ) ); ?></h2>
			<?php if ( '' !== $section['body'] ) : ?>
				<p class="bwd-lede"><?php echo esc_html( $section['body'] ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		unset( $payload );
	}

	/**
	 * One case study.
	 *
	 * @param array<string,mixed> $section Section, carrying its study.
	 * @return void
	 */
	private static function case_study( array $section ) {
		$study = $section['study'];
		// With no screenshots there is nothing to put in the right-hand
		// column, and a half-empty slide reads as a slide that failed to load.
		$shots = $study['desktop'] || $study['tablet'] || $study['mobile'];
		?>
		<div class="bwd-two <?php echo $shots ? 'bwd-two--study' : 'bwd-two--solo'; ?>">
			<div class="bwd-two__left">
				<?php if ( '' !== $study['number'] ) : ?>
					<p class="bwd-studyn"><?php echo esc_html( $study['number'] ); ?></p>
				<?php endif; ?>
				<h2 class="bwd-h1"><?php echo esc_html( $study['name'] ); ?></h2>
				<?php if ( '' !== $study['sector'] ) : ?>
					<p class="bwd-sector"><?php echo esc_html( $study['sector'] ); ?></p>
				<?php endif; ?>
				<?php if ( '' !== $study['services'] ) : ?>
					<p class="bwd-chips">
						<?php foreach ( array_map( 'trim', explode( ',', $study['services'] ) ) as $service ) : ?>
							<span class="bwd-chip"><?php echo esc_html( $service ); ?></span>
						<?php endforeach; ?>
					</p>
				<?php endif; ?>
				<?php if ( '' !== $study['summary'] ) : ?>
					<p class="bwd-lede"><?php echo esc_html( $study['summary'] ); ?></p>
				<?php endif; ?>
				<?php if ( '' !== $study['link'] ) : ?>
					<p class="bwd-link"><a class="bwd-link__a" href="<?php echo esc_url( $study['link'] ); ?>" target="_blank" rel="noopener">&#8599; <?php echo esc_html( wp_parse_url( $study['link'], PHP_URL_HOST ) ); ?></a></p>
				<?php endif; ?>
			</div>
			<div class="bwd-two__right bwd-shots">
				<?php self::image( $study['desktop'], 'bwd-shot bwd-shot--desktop' ); ?>
				<div class="bwd-shots__small">
					<?php self::image( $study['tablet'], 'bwd-shot bwd-shot--tablet' ); ?>
					<?php self::image( $study['mobile'], 'bwd-shot bwd-shot--mobile' ); ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * The last slide.
	 *
	 * @param array<string,mixed> $section Section.
	 * @param array<string,mixed> $payload Client payload.
	 * @return void
	 */
	private static function cta( array $section, array $payload ) {
		$email = (string) Blueworx_Deck_Builder_Editor::setting( 'contact_email', get_option( 'admin_email' ) );
		?>
		<span class="bwd-blob bwd-blob--big"></span>
		<div class="bwd-cta">
			<?php self::logo( $payload ); ?>
			<h2 class="bwd-display"><?php echo esc_html( '' !== $section['title'] ? $section['title'] : __( 'Let us build something great.', 'blueworx-labs-deck-builder' ) ); ?></h2>
			<p class="bwd-lede">
				<?php
				printf(
					/* translators: %s: client name. */
					esc_html__( 'Whenever you are ready, %s — we will take it from here.', 'blueworx-labs-deck-builder' ),
					esc_html( $payload['client'] )
				);
				?>
			</p>
			<?php if ( '' !== $email ) : ?>
				<p class="bwd-cta__actions">
					<a class="bwd-btn" href="<?php echo esc_url( 'mailto:' . $email ); ?>"><?php esc_html_e( 'Get in touch', 'blueworx-labs-deck-builder' ); ?></a>
				</p>
				<p class="bwd-cta__email"><?php echo esc_html( $email ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/* --- Small pieces -------------------------------------------------------- */

	/**
	 * The client's logo, on the white plate it needs to sit on a dark section.
	 *
	 * @param array<string,mixed> $payload Client payload.
	 * @return void
	 */
	private static function logo( array $payload ) {
		if ( ! $payload['logo'] ) {
			return;
		}
		$src = wp_get_attachment_image_url( $payload['logo'], 'medium' );
		if ( ! $src ) {
			return;
		}
		?>
		<span class="bwd-logo"><img class="bwd-logo__img" src="<?php echo esc_url( $src ); ?>" alt="<?php echo esc_attr( $payload['client'] ); ?>" /></span>
		<?php
	}

	/**
	 * One image from the media library, or nothing at all.
	 *
	 * @param int    $id    Attachment id.
	 * @param string $css   Class to put on it.
	 * @return void
	 */
	private static function image( $id, $css ) {
		if ( ! $id ) {
			return;
		}
		$src = wp_get_attachment_image_url( (int) $id, 'large' );
		if ( ! $src ) {
			return;
		}
		printf( '<img class="%s" src="%s" alt="" />', esc_attr( $css ), esc_url( $src ) );
	}

	/**
	 * A section's eyebrow, when it has one.
	 *
	 * @param array<string,mixed> $section  Section.
	 * @param string              $fallback Used when the section has none.
	 * @return void
	 */
	private static function eyebrow( array $section, $fallback = '' ) {
		$text = '' !== $section['eyebrow'] ? $section['eyebrow'] : $fallback;
		if ( '' === $text ) {
			return;
		}
		printf( '<p class="bwd-eyebrow">%s</p>', esc_html( $text ) );
	}

	/**
	 * A textarea split into the lines somebody actually typed.
	 *
	 * @param string $text Raw text.
	 * @return array<int,string>
	 */
	private static function lines( $text ) {
		$lines = preg_split( '/\r\n|\r|\n/', (string) $text );
		return array_values( array_filter( array_map( 'trim', (array) $lines ) ) );
	}
}
