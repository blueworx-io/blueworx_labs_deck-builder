<?php
/**
 * The three list screens: support packages, case studies and the content library.
 *
 * @package Blueworx\DeckBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * One list shape, three lists. Each one exists because the page editor library
 * edits records and never creates them, so something has to show what exists,
 * make a new one, and link into the editor with a record id.
 */
final class Blueworx_Deck_Builder_List_Screen {

	/**
	 * Support packages.
	 *
	 * @return void
	 */
	public static function packages() {
		$rows = [];
		foreach ( Blueworx_Deck_Builder_Packages::all() as $package ) {
			$rows[] = [
				'id'    => $package['id'],
				'name'  => $package['name'],
				'note'  => self::package_note( $package ),
				'type'  => trim( $package['period'] . ' · ' . $package['commitment'], ' ·' ),
				'used'  => self::decks_using( $package['id'] ),
				'badge' => self::package_badge( $package ),
			];
		}

		self::screen(
			[
				'eyebrow'  => __( 'Deck Builder', 'blueworx-labs-deck-builder' ),
				'title'    => __( 'Support packages', 'blueworx-labs-deck-builder' ),
				'lede'     => __( 'Set up each package once — hours, prices in every currency, and what the client sees.', 'blueworx-labs-deck-builder' ),
				'add'      => __( 'Add package', 'blueworx-labs-deck-builder' ),
				'action'   => 'new_package',
				'back'     => Blueworx_Deck_Builder_Admin::PAGE_SLUG . '-packages',
				'screen'   => Blueworx_Deck_Builder_Editor::PACKAGE_SCREEN,
				'empty'    => __( 'No packages yet', 'blueworx-labs-deck-builder' ),
				'emptyMsg' => __( 'A deck cannot recommend anything until at least one package has hours and a price.', 'blueworx-labs-deck-builder' ),
				'icon'     => 'package',
				'column'   => __( 'Allowance', 'blueworx-labs-deck-builder' ),
				'rows'     => $rows,
				'notice'   => [
					'tone'  => 'info',
					'icon'  => 'info',
					'title' => __( 'Every package carries four prices', 'blueworx-labs-deck-builder' ),
					'body'  => __( 'Rand, Dollar, Pound and Euro. Each deck picks which one the client sees, so a package is set up once and reused everywhere.', 'blueworx-labs-deck-builder' ),
				],
			]
		);
	}

	/**
	 * Case studies.
	 *
	 * @return void
	 */
	public static function case_studies() {
		$rows = [];
		foreach ( self::records( Blueworx_Deck_Builder_Types::CASE_STUDY ) as $post ) {
			$rows[] = [
				'id'    => $post->ID,
				'name'  => $post->post_title,
				'note'  => (string) get_post_meta( $post->ID, 'bw_case_study_summary', true ),
				'type'  => (string) get_post_meta( $post->ID, 'bw_case_study_sector', true ),
				'used'  => self::decks_using( $post->ID, 'bw_deck_case_studies' ),
				'badge' => null,
			];
		}

		self::screen(
			[
				'eyebrow'  => __( 'Deck Builder', 'blueworx-labs-deck-builder' ),
				'title'    => __( 'Case studies', 'blueworx-labs-deck-builder' ),
				'lede'     => __( 'Past work, ready to drop into any deck.', 'blueworx-labs-deck-builder' ),
				'add'      => __( 'Add case study', 'blueworx-labs-deck-builder' ),
				'action'   => 'new_case_study',
				'back'     => Blueworx_Deck_Builder_Admin::PAGE_SLUG . '-case-studies',
				'screen'   => Blueworx_Deck_Builder_Editor::STUDY_SCREEN,
				'empty'    => __( 'No case studies yet', 'blueworx-labs-deck-builder' ),
				'emptyMsg' => __( 'Add the work you want clients to see, then choose which of it each deck shows.', 'blueworx-labs-deck-builder' ),
				'icon'     => 'image',
				'column'   => __( 'Sector', 'blueworx-labs-deck-builder' ),
				'rows'     => $rows,
				'notice'   => null,
			]
		);
	}

	/**
	 * The content library.
	 *
	 * @return void
	 */
	public static function library() {
		$kinds = wp_list_pluck( Blueworx_Deck_Builder_Types::section_kinds(), 'label', 'value' );
		$rows  = [];
		foreach ( self::records( Blueworx_Deck_Builder_Types::LIBRARY ) as $post ) {
			$kind   = (string) get_post_meta( $post->ID, 'bw_library_item_kind', true );
			$rows[] = [
				'id'    => $post->ID,
				'name'  => $post->post_title,
				'note'  => (string) get_post_meta( $post->ID, 'bw_library_item_note', true ),
				'type'  => $kinds[ $kind ] ?? __( 'Section', 'blueworx-labs-deck-builder' ),
				'used'  => '',
				'badge' => null,
			];
		}

		self::screen(
			[
				'eyebrow'  => __( 'Deck Builder', 'blueworx-labs-deck-builder' ),
				'title'    => __( 'Content library', 'blueworx-labs-deck-builder' ),
				'lede'     => __( 'Reusable sections. Editing one inside a deck makes a copy and leaves the library entry alone.', 'blueworx-labs-deck-builder' ),
				'add'      => __( 'Add section', 'blueworx-labs-deck-builder' ),
				'action'   => 'new_library_item',
				'back'     => Blueworx_Deck_Builder_Admin::PAGE_SLUG . '-library',
				'screen'   => Blueworx_Deck_Builder_Editor::LIBRARY_SCREEN,
				'empty'    => __( 'The library is empty', 'blueworx-labs-deck-builder' ),
				'emptyMsg' => __( 'Add the sections you reuse across decks, so a blank deck has something to be built from.', 'blueworx-labs-deck-builder' ),
				'icon'     => 'library',
				'column'   => __( 'Type', 'blueworx-labs-deck-builder' ),
				'rows'     => $rows,
				'notice'   => null,
			]
		);
	}

	/**
	 * Render one list.
	 *
	 * @param array<string,mixed> $config What this particular list holds.
	 * @return void
	 */
	private static function screen( array $config ) {
		Blueworx_Deck_Builder_Admin::open(
			$config['eyebrow'],
			$config['title'],
			$config['lede'],
			[
				[
					'label' => __( 'Decks', 'blueworx-labs-deck-builder' ),
					'href'  => Blueworx_Deck_Builder_Admin::url(),
					'class' => 'bw-btn--secondary',
				],
			]
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reading a confirmation flag, not changing anything.
		$done = isset( $_GET['done'] ) ? sanitize_key( wp_unslash( $_GET['done'] ) ) : '';
		if ( 'deleted' === $done ) {
			Blueworx_Deck_Builder_Admin::notice( 'info', 'trash-2', __( 'Deleted', 'blueworx-labs-deck-builder' ), __( 'That record has been removed. Decks that used it now fall back to whatever else is set up.', 'blueworx-labs-deck-builder' ) );
		}

		if ( ! empty( $config['notice'] ) ) {
			Blueworx_Deck_Builder_Admin::notice( $config['notice']['tone'], $config['notice']['icon'], $config['notice']['title'], $config['notice']['body'] );
		}

		$missing = Blueworx_Deck_Builder_Packages::missing_hours();
		if ( null !== $missing && Blueworx_Deck_Builder_Editor::PACKAGE_SCREEN === $config['screen'] ) {
			Blueworx_Deck_Builder_Admin::notice(
				'warning',
				'triangle-alert',
				__( 'One package has no included hours', 'blueworx-labs-deck-builder' ),
				sprintf(
					/* translators: %s: package name. */
					__( '%s cannot be recommended until its hours are set. Set them below, or turn off automatic recommendation for it.', 'blueworx-labs-deck-builder' ),
					$missing['name']
				)
			);
		}
		?>
			<section class="bw-card bw-card--flush">
				<div class="bw-card__head">
					<div class="bw-card__titles">
						<h2 class="bw-card__title"><?php echo esc_html( $config['title'] ); ?></h2>
						<p class="bw-card__note">
							<?php
							printf(
								/* translators: %d: how many records there are. */
								esc_html( _n( '%d entry', '%d entries', count( $config['rows'] ), 'blueworx-labs-deck-builder' ) ),
								count( $config['rows'] )
							);
							?>
						</p>
					</div>
					<div class="bw-card__actions">
						<?php Blueworx_Deck_Builder_Admin::action_button( $config['add'], $config['action'], 0, 'bw-btn bw-btn--primary bw-btn--sm' ); ?>
					</div>
				</div>

				<?php if ( ! $config['rows'] ) : ?>
					<div class="bw-card__body">
						<div class="bw-empty">
							<i class="bw-icon bw-icon--28 bw-empty__icon" data-lucide="<?php echo esc_attr( $config['icon'] ); ?>"></i>
							<h3 class="bw-empty__title"><?php echo esc_html( $config['empty'] ); ?></h3>
							<p class="bw-empty__text"><?php echo esc_html( $config['emptyMsg'] ); ?></p>
							<div class="bw-empty__actions">
								<?php Blueworx_Deck_Builder_Admin::action_button( $config['add'], $config['action'], 0, 'bw-btn bw-btn--primary' ); ?>
							</div>
						</div>
					</div>
				<?php else : ?>
					<table class="bw-table">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e( 'Item', 'blueworx-labs-deck-builder' ); ?></th>
								<th scope="col"><?php echo esc_html( $config['column'] ); ?></th>
								<th scope="col"><?php esc_html_e( 'Used in', 'blueworx-labs-deck-builder' ); ?></th>
								<th scope="col" class="bw-table__actions"><?php esc_html_e( 'Actions', 'blueworx-labs-deck-builder' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $config['rows'] as $row ) : ?>
								<tr>
									<td>
										<span class="bw-table__primary">
											<?php echo esc_html( $row['name'] ); ?>
											<?php if ( ! empty( $row['badge'] ) ) : ?>
												<span class="bw-badge bw-badge--<?php echo esc_attr( $row['badge'][0] ); ?>"><?php echo esc_html( $row['badge'][1] ); ?></span>
											<?php endif; ?>
										</span>
										<?php if ( '' !== $row['note'] ) : ?>
											<span class="bw-table__sub"><?php echo esc_html( $row['note'] ); ?></span>
										<?php endif; ?>
									</td>
									<td><?php echo esc_html( '' === $row['type'] ? '—' : $row['type'] ); ?></td>
									<td><?php echo esc_html( '' === $row['used'] ? '—' : $row['used'] ); ?></td>
									<td class="bw-table__actions">
										<div class="bw-rowactions">
											<a class="bw-rowactions__link" href="<?php echo esc_url( Blueworx_Deck_Builder_Admin::editor_url( $config['screen'], $row['id'] ) ); ?>"><?php esc_html_e( 'Edit', 'blueworx-labs-deck-builder' ); ?></a>
											<?php
											Blueworx_Deck_Builder_Admin::action_button(
												__( 'Delete', 'blueworx-labs-deck-builder' ),
												'delete_record',
												$row['id'],
												'bw-btn bw-btn--link bw-btn--sm',
												[ 'back' => $config['back'] ]
											);
											?>
										</div>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</section>
		<?php
		Blueworx_Deck_Builder_Admin::close();
	}

	/**
	 * Every record of one type, by title.
	 *
	 * @param string $post_type Post type.
	 * @return array<int,WP_Post>
	 */
	private static function records( $post_type ) {
		return get_posts(
			[
				'post_type'   => $post_type,
				'post_status' => [ 'draft', 'publish', 'private', 'pending' ],
				'numberposts' => 100,
				'orderby'     => 'title',
				'order'       => 'ASC',
			]
		);
	}

	/**
	 * How many decks currently point at one record.
	 *
	 * @param int    $id       Record id.
	 * @param string $meta_key Deck meta key that holds the reference.
	 * @return string
	 */
	private static function decks_using( $id, $meta_key = '' ) {
		$count = 0;
		foreach ( Blueworx_Deck_Builder_Deck::all() as $deck ) {
			if ( '' === $meta_key ) {
				$used = (int) $deck->get( 'override', 0 ) === (int) $id || in_array( (int) $id, $deck->alternatives(), true );
			} else {
				$used = in_array( (string) $id, array_map( 'strval', (array) $deck->get( 'case_studies', [] ) ), true );
			}
			$count += $used ? 1 : 0;
		}
		if ( 0 === $count ) {
			return '';
		}
		return sprintf(
			/* translators: %d: how many decks. */
			_n( '%d deck', '%d decks', $count, 'blueworx-labs-deck-builder' ),
			$count
		);
	}

	/**
	 * The line under a package's name.
	 *
	 * @param array<string,mixed> $package Package.
	 * @return string
	 */
	private static function package_note( array $package ) {
		$parts = [];
		if ( null !== $package['hours'] ) {
			$parts[] = sprintf(
				/* translators: %s: included hours. */
				__( '%s hours included', 'blueworx-labs-deck-builder' ),
				Blueworx_Deck_Builder_Packages::hours( $package['hours'] )
			);
		}
		$prices = 0;
		foreach ( $package['prices'] as $price ) {
			$prices += null === $price ? 0 : 1;
		}
		$parts[] = sprintf(
			/* translators: %d: how many of the four currencies have a price. */
			__( '%d of 4 prices set', 'blueworx-labs-deck-builder' ),
			$prices
		);
		return implode( ' · ', $parts );
	}

	/**
	 * The badge beside a package's name.
	 *
	 * @param array<string,mixed> $package Package.
	 * @return array<int,string>|null
	 */
	private static function package_badge( array $package ) {
		if ( null === $package['hours'] || $package['hours'] <= 0 ) {
			return [ 'danger', __( 'Hours not set', 'blueworx-labs-deck-builder' ) ];
		}
		$missing = 0;
		foreach ( $package['prices'] as $price ) {
			$missing += null === $price ? 1 : 0;
		}
		if ( $missing > 0 ) {
			return [
				'warning',
				sprintf(
					/* translators: %d: how many currencies have no price. */
					_n( '%d price missing', '%d prices missing', $missing, 'blueworx-labs-deck-builder' ),
					$missing
				),
			];
		}
		if ( $package['popular'] ) {
			return [ 'accent', __( 'Most popular', 'blueworx-labs-deck-builder' ) ];
		}
		return [ 'success', __( 'Ready', 'blueworx-labs-deck-builder' ) ];
	}
}
