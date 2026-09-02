<?php
/**
 * The decks dashboard.
 *
 * @package Blueworx\DeckBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * The one screen the page editor library does not build: a list of decks with
 * their state at a glance. Making a deck is an action rather than a screen —
 * see Blueworx_Deck_Builder_Admin::handle_create().
 */
final class Blueworx_Deck_Builder_Decks_Screen {

	/**
	 * The decks dashboard.
	 *
	 * @return void
	 */
	public static function render() {
		$decks = Blueworx_Deck_Builder_Deck::all();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- reading filters off the address bar, not changing anything.
		$query  = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$notice = isset( $_GET['done'] ) ? sanitize_key( wp_unslash( $_GET['done'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$shown = array_values(
			array_filter(
				$decks,
				static function ( $deck ) use ( $query, $status ) {
					if ( '' !== $status && $deck->status() !== $status ) {
						return false;
					}
					if ( '' === $query ) {
						return true;
					}
					$haystack = strtolower( $deck->title() . ' ' . (string) $deck->get( 'client' ) );
					return false !== strpos( $haystack, strtolower( $query ) );
				}
			)
		);

		Blueworx_Deck_Builder_Admin::open(
			__( 'Deck Builder', 'blueworx-labs-deck-builder' ),
			__( 'Decks', 'blueworx-labs-deck-builder' ),
			__( 'Client decks, each one a copy of the content library made for that client.', 'blueworx-labs-deck-builder' ),
			[
				[
					'label' => __( 'Support packages', 'blueworx-labs-deck-builder' ),
					'href'  => Blueworx_Deck_Builder_Admin::url( Blueworx_Deck_Builder_Admin::PAGE_SLUG . '-packages' ),
					'class' => 'bw-btn--secondary',
				],
				[
					'label' => __( 'Create new deck', 'blueworx-labs-deck-builder' ),
					'href'  => Blueworx_Deck_Builder_Admin::create_url(),
					'class' => 'bw-btn--primary',
					'icon'  => 'plus',
				],
			]
		);

		self::done_notice( $notice );
		self::stats( $decks );

		$missing = Blueworx_Deck_Builder_Packages::missing_hours();
		if ( null !== $missing ) {
			Blueworx_Deck_Builder_Admin::notice(
				'warning',
				'triangle-alert',
				__( 'One package has no included hours', 'blueworx-labs-deck-builder' ),
				sprintf(
					/* translators: %s: package name. */
					__( '%s is eligible for automatic recommendation but has no hours set, so it can never be recommended.', 'blueworx-labs-deck-builder' ),
					$missing['name']
				)
			);
		}

		?>
			<section class="bw-card bw-card--flush">
				<div class="bw-toolbar bw-toolbar--card">
					<form class="bw-toolbar__group" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
						<input type="hidden" name="page" value="<?php echo esc_attr( Blueworx_Deck_Builder_Admin::PAGE_SLUG ); ?>" />
						<span class="bw-toolbar__search">
							<input class="bw-input bw-input--sm" type="search" name="q" value="<?php echo esc_attr( $query ); ?>" placeholder="<?php esc_attr_e( 'Search client or deck title', 'blueworx-labs-deck-builder' ); ?>" aria-label="<?php esc_attr_e( 'Search decks', 'blueworx-labs-deck-builder' ); ?>" />
						</span>
						<span class="bw-select">
							<select class="bw-select__el" name="status" aria-label="<?php esc_attr_e( 'Filter by status', 'blueworx-labs-deck-builder' ); ?>">
								<?php
								foreach (
									[
										''          => __( 'All statuses', 'blueworx-labs-deck-builder' ),
										'draft'     => __( 'Draft', 'blueworx-labs-deck-builder' ),
										'published' => __( 'Published', 'blueworx-labs-deck-builder' ),
										'archived'  => __( 'Archived', 'blueworx-labs-deck-builder' ),
									] as $value => $label
								) :
									?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $status, $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
							<i class="bw-icon bw-select__arrow" data-lucide="chevron-down"></i>
						</span>
						<button type="submit" class="bw-btn bw-btn--secondary bw-btn--sm"><?php esc_html_e( 'Filter', 'blueworx-labs-deck-builder' ); ?></button>
					</form>
					<span class="bw-toolbar__spacer"></span>
					<span class="bw-bulk__count">
						<?php
						printf(
							/* translators: 1: decks shown, 2: decks in total. */
							esc_html__( '%1$d of %2$d decks', 'blueworx-labs-deck-builder' ),
							count( $shown ),
							count( $decks )
						);
						?>
					</span>
				</div>

				<?php if ( ! $shown ) : ?>
					<div class="bw-card__body">
						<div class="bw-empty">
							<i class="bw-icon bw-icon--28 bw-empty__icon" data-lucide="<?php echo $decks ? 'search' : 'layout-dashboard'; ?>"></i>
							<h3 class="bw-empty__title">
								<?php
								echo $decks
									? esc_html__( 'No decks match those filters', 'blueworx-labs-deck-builder' )
									: esc_html__( 'No decks yet', 'blueworx-labs-deck-builder' );
								?>
							</h3>
							<p class="bw-empty__text">
								<?php
								echo $decks
									? esc_html__( 'Nothing here matches what you searched for. Clear the filters to see everything again.', 'blueworx-labs-deck-builder' )
									: esc_html__( 'Create a deck for a client and it will appear here, with its hours and its recommended package.', 'blueworx-labs-deck-builder' );
								?>
							</p>
							<div class="bw-empty__actions">
								<?php if ( $decks ) : ?>
									<a class="bw-btn bw-btn--secondary" href="<?php echo esc_url( Blueworx_Deck_Builder_Admin::url() ); ?>"><?php esc_html_e( 'Clear filters', 'blueworx-labs-deck-builder' ); ?></a>
								<?php else : ?>
									<a class="bw-btn bw-btn--primary" href="<?php echo esc_url( Blueworx_Deck_Builder_Admin::create_url() ); ?>"><?php esc_html_e( 'Create new deck', 'blueworx-labs-deck-builder' ); ?></a>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php else : ?>
					<div class="bw-tablescroll">
						<table class="bw-table">
							<thead>
								<tr>
									<th scope="col"><?php esc_html_e( 'Client', 'blueworx-labs-deck-builder' ); ?></th>
									<th scope="col" class="bw-table__num"><?php esc_html_e( 'Hours', 'blueworx-labs-deck-builder' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Recommended', 'blueworx-labs-deck-builder' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Status', 'blueworx-labs-deck-builder' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Updated', 'blueworx-labs-deck-builder' ); ?></th>
									<th scope="col" class="bw-table__actions"><?php esc_html_e( 'Actions', 'blueworx-labs-deck-builder' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $shown as $deck ) : ?>
									<?php self::row( $deck ); ?>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<div class="bw-tablefoot">
						<span><?php esc_html_e( 'Archived decks keep their content but their client link stops working.', 'blueworx-labs-deck-builder' ); ?></span>
					</div>
				<?php endif; ?>
			</section>
		<?php

		Blueworx_Deck_Builder_Admin::close();
	}

	/**
	 * One deck row.
	 *
	 * @param Blueworx_Deck_Builder_Deck $deck Deck.
	 * @return void
	 */
	private static function row( Blueworx_Deck_Builder_Deck $deck ) {
		$recommendation = $deck->recommendation();
		$status         = $deck->status();
		$badges         = [
			'draft'     => [ 'warning', __( 'Draft', 'blueworx-labs-deck-builder' ) ],
			'published' => [ 'success', __( 'Published', 'blueworx-labs-deck-builder' ) ],
			'archived'  => [ 'neutral', __( 'Archived', 'blueworx-labs-deck-builder' ) ],
		];
		?>
		<tr>
			<td>
				<span class="bw-table__primary"><?php echo esc_html( $deck->client_name() ); ?></span>
				<span class="bw-table__sub"><?php echo esc_html( $deck->title() ); ?></span>
			</td>
			<td class="bw-table__num"><?php echo esc_html( Blueworx_Deck_Builder_Packages::hours( $deck->project_total() ) ); ?></td>
			<td><?php self::recommendation_badge( $recommendation ); ?></td>
			<td>
				<span class="bw-badge bw-badge--<?php echo esc_attr( $badges[ $status ][0] ); ?>">
					<span class="bw-badge__dot"></span><?php echo esc_html( $badges[ $status ][1] ); ?>
				</span>
			</td>
			<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $deck->updated() ) ); ?></td>
			<td class="bw-table__actions">
				<div class="bw-rowactions">
					<a class="bw-rowactions__link" href="<?php echo esc_url( Blueworx_Deck_Builder_Admin::editor_url( Blueworx_Deck_Builder_Editor::DECK_SCREEN, $deck->id() ) ); ?>"><?php esc_html_e( 'Edit', 'blueworx-labs-deck-builder' ); ?></a>
					<a class="bw-rowactions__link" href="<?php echo esc_url( add_query_arg( 'preview', '1', $deck->link() ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Preview', 'blueworx-labs-deck-builder' ); ?></a>
					<?php Blueworx_Deck_Builder_Admin::action_button( __( 'Duplicate', 'blueworx-labs-deck-builder' ), 'duplicate', $deck->id(), 'bw-btn bw-btn--link bw-btn--sm' ); ?>
					<?php if ( 'archived' === $status ) : ?>
						<?php Blueworx_Deck_Builder_Admin::action_button( __( 'Restore', 'blueworx-labs-deck-builder' ), 'restore', $deck->id(), 'bw-btn bw-btn--link bw-btn--sm' ); ?>
					<?php else : ?>
						<?php Blueworx_Deck_Builder_Admin::action_button( __( 'Archive', 'blueworx-labs-deck-builder' ), 'archive', $deck->id(), 'bw-btn bw-btn--link bw-btn--sm' ); ?>
					<?php endif; ?>
					<?php if ( 'published' !== $status ) : ?>
						<?php Blueworx_Deck_Builder_Admin::action_button( __( 'Publish', 'blueworx-labs-deck-builder' ), 'publish', $deck->id(), 'bw-btn bw-btn--link bw-btn--sm' ); ?>
					<?php endif; ?>
					<span class="bw-rowactions__sep"></span>
					<?php if ( $deck->link_live() ) : ?>
						<a class="bw-rowactions__link" href="<?php echo esc_url( $deck->link() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open link', 'blueworx-labs-deck-builder' ); ?></a>
					<?php else : ?>
						<span class="bw-rowactions__link"><?php esc_html_e( 'Link off', 'blueworx-labs-deck-builder' ); ?></span>
					<?php endif; ?>
				</div>
			</td>
		</tr>
		<?php
	}

	/**
	 * The recommendation badge on a row.
	 *
	 * @param array<string,mixed> $recommendation Recommendation.
	 * @return void
	 */
	private static function recommendation_badge( array $recommendation ) {
		if ( 'CUSTOM' === $recommendation['state'] ) {
			?>
			<span class="bw-badge bw-badge--danger"><?php esc_html_e( 'Custom required', 'blueworx-labs-deck-builder' ); ?></span>
			<?php
			return;
		}
		if ( null === $recommendation['package'] ) {
			?>
			<span class="bw-badge bw-badge--neutral"><?php esc_html_e( 'Not estimated', 'blueworx-labs-deck-builder' ); ?></span>
			<?php
			return;
		}
		?>
		<span class="bw-badge bw-badge--accent"><?php echo esc_html( $recommendation['package']['name'] ); ?></span>
		<?php
	}

	/**
	 * The four stat tiles.
	 *
	 * @param array<int,Blueworx_Deck_Builder_Deck> $decks Every deck.
	 * @return void
	 */
	private static function stats( array $decks ) {
		$live      = 0;
		$hours     = 0.0;
		$attention = 0;
		foreach ( $decks as $deck ) {
			$live  += $deck->link_live() ? 1 : 0;
			$hours += $deck->project_total();
			if ( 'CUSTOM' === $deck->recommendation()['state'] ) {
				++$attention;
			}
		}

		$tiles = [
			[ 'layout-dashboard', __( 'Decks', 'blueworx-labs-deck-builder' ), (string) count( $decks ), __( 'Drafts, published and archived', 'blueworx-labs-deck-builder' ) ],
			[ 'link', __( 'Live client links', 'blueworx-labs-deck-builder' ), (string) $live, __( 'Open without a WordPress login', 'blueworx-labs-deck-builder' ) ],
			[ 'clock', __( 'Hours estimated', 'blueworx-labs-deck-builder' ), Blueworx_Deck_Builder_Packages::hours( $hours ), __( 'Across every project estimate', 'blueworx-labs-deck-builder' ) ],
			[ 'triangle-alert', __( 'Need attention', 'blueworx-labs-deck-builder' ), (string) $attention, __( 'Above the largest package', 'blueworx-labs-deck-builder' ) ],
		];
		?>
		<div class="bw-stats">
			<?php foreach ( $tiles as $tile ) : ?>
				<div class="bw-stat">
					<div class="bw-stat__row">
						<i class="bw-icon bw-icon--14" data-lucide="<?php echo esc_attr( $tile[0] ); ?>"></i>
						<span class="bw-stat__label"><?php echo esc_html( $tile[1] ); ?></span>
					</div>
					<p class="bw-stat__value"><?php echo esc_html( $tile[2] ); ?></p>
					<p class="bw-stat__foot"><?php echo esc_html( $tile[3] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * The confirmation after an action, if there was one.
	 *
	 * @param string $done Action that completed.
	 * @return void
	 */
	private static function done_notice( $done ) {
		$messages = [
			'published'  => [ 'success', 'circle-check', __( 'Published', 'blueworx-labs-deck-builder' ), __( 'The client link is live and the package price is locked to this version.', 'blueworx-labs-deck-builder' ) ],
			'archived'   => [ 'info', 'archive', __( 'Deck archived', 'blueworx-labs-deck-builder' ), __( 'It keeps its content, but its client link now returns a not-found page.', 'blueworx-labs-deck-builder' ) ],
			'restored'   => [ 'success', 'circle-check', __( 'Deck restored', 'blueworx-labs-deck-builder' ), __( 'It is back in the list, and its link works again once it is published.', 'blueworx-labs-deck-builder' ) ],
			'duplicated' => [ 'success', 'copy', __( 'Deck duplicated', 'blueworx-labs-deck-builder' ), __( 'The copy is a draft with its own client link.', 'blueworx-labs-deck-builder' ) ],
			'deleted'    => [ 'info', 'trash-2', __( 'Deleted', 'blueworx-labs-deck-builder' ), __( 'That record has been removed.', 'blueworx-labs-deck-builder' ) ],
			'missing'    => [ 'warning', 'triangle-alert', __( 'That deck could not be found', 'blueworx-labs-deck-builder' ), __( 'Nothing was changed. It may have been deleted in another tab.', 'blueworx-labs-deck-builder' ) ],
			'notmade'    => [ 'danger', 'triangle-alert', __( 'The deck could not be created', 'blueworx-labs-deck-builder' ), __( 'Nothing was saved. Try again, and if it keeps happening the site is refusing to write posts.', 'blueworx-labs-deck-builder' ) ],
		];
		if ( isset( $messages[ $done ] ) ) {
			Blueworx_Deck_Builder_Admin::notice( $messages[ $done ][0], $messages[ $done ][1], $messages[ $done ][2], $messages[ $done ][3] );
		}
	}

	/* --- Actions ------------------------------------------------------------ */

	/**
	 * Carry out one action and say where to go next.
	 *
	 * The caller has already checked the capability and the nonce.
	 *
	 * @param string              $action Action name.
	 * @param int                 $id     Record id.
	 * @param array<string,mixed> $input  The rest of the request.
	 * @return string Where to redirect to.
	 */
	public static function do_action( $action, $id, array $input ) {
		$decks = Blueworx_Deck_Builder_Admin::url();

		switch ( $action ) {
			case 'publish':
				$deck = Blueworx_Deck_Builder_Deck::find( $id );
				if ( null !== $deck ) {
					wp_update_post( [ 'ID' => $id, 'post_status' => 'publish' ] );
					delete_post_meta( $id, 'bw_deck_archived' );
					Blueworx_Deck_Builder_Deck::find( $id )->take_snapshot();
				}
				return add_query_arg( 'done', 'published', $decks );

			// Both of these look up the deck first, and do nothing when the id
			// is not one. They took the id on trust while only an
			// administrator could reach this handler, which made it academic;
			// a sales agent can reach it now, and an id nobody checked is a
			// way to write plugin meta onto any post on the site.
			case 'archive':
				if ( null === Blueworx_Deck_Builder_Deck::find( $id ) ) {
					return add_query_arg( 'done', 'missing', $decks );
				}
				update_post_meta( $id, 'bw_deck_archived', true );
				return add_query_arg( 'done', 'archived', $decks );

			case 'restore':
				if ( null === Blueworx_Deck_Builder_Deck::find( $id ) ) {
					return add_query_arg( 'done', 'missing', $decks );
				}
				delete_post_meta( $id, 'bw_deck_archived' );
				return add_query_arg( 'done', 'restored', $decks );

			case 'duplicate':
				$copy = self::duplicate( $id );
				return $copy > 0 ? add_query_arg( 'done', 'duplicated', $decks ) : $decks;

			case 'new_package':
				return self::new_record( Blueworx_Deck_Builder_Types::PACKAGE, __( 'New package', 'blueworx-labs-deck-builder' ), Blueworx_Deck_Builder_Editor::PACKAGE_SCREEN );

			case 'new_case_study':
				return self::new_record( Blueworx_Deck_Builder_Types::CASE_STUDY, __( 'New case study', 'blueworx-labs-deck-builder' ), Blueworx_Deck_Builder_Editor::STUDY_SCREEN );

			// There is deliberately no "new library entry". The library is the
			// single source every deck is copied from, so adding to it is a
			// change to what the business offers — made in the seed, in code,
			// and reviewed like anything else.

			case 'delete_record':
				$post = get_post( $id );
				$back = isset( $input['back'] ) ? sanitize_key( wp_unslash( $input['back'] ) ) : Blueworx_Deck_Builder_Admin::PAGE_SLUG;
				if ( null !== $post && in_array( $post->post_type, [ Blueworx_Deck_Builder_Types::PACKAGE, Blueworx_Deck_Builder_Types::CASE_STUDY, Blueworx_Deck_Builder_Types::LIBRARY, Blueworx_Deck_Builder_Types::DECK ], true ) ) {
					wp_delete_post( $id, true );
				}
				return add_query_arg( 'done', 'deleted', Blueworx_Deck_Builder_Admin::url( $back ) );

			default:
				return $decks;
		}
	}

	/**
	 * Make an empty record of one type and open its editor.
	 *
	 * @param string $post_type Post type.
	 * @param string $title     Working title.
	 * @param string $screen    Editor screen slug.
	 * @return string
	 */
	private static function new_record( $post_type, $title, $screen ) {
		$id = wp_insert_post(
			[
				'post_type'   => $post_type,
				'post_status' => 'publish',
				'post_title'  => $title,
			]
		);
		if ( is_wp_error( $id ) || ! $id ) {
			return Blueworx_Deck_Builder_Admin::url();
		}
		return Blueworx_Deck_Builder_Admin::editor_url( $screen, (int) $id );
	}

	/**
	 * Copy a deck, including every field it holds — but not its client link.
	 * Two decks sharing one link would mean sending a client somebody else's
	 * deck the moment either was published.
	 *
	 * @param int $id Deck id.
	 * @return int The new deck id, or 0.
	 */
	private static function duplicate( $id ) {
		$source = get_post( $id );
		if ( null === $source || Blueworx_Deck_Builder_Types::DECK !== $source->post_type ) {
			return 0;
		}

		$copy = wp_insert_post(
			[
				'post_type'   => Blueworx_Deck_Builder_Types::DECK,
				'post_status' => 'draft',
				'post_title'  => sprintf(
					/* translators: %s: the deck being copied. */
					__( '%s (copy)', 'blueworx-labs-deck-builder' ),
					$source->post_title
				),
			]
		);
		if ( is_wp_error( $copy ) || ! $copy ) {
			return 0;
		}

		foreach ( get_post_meta( $id ) as $key => $values ) {
			if ( in_array( $key, [ 'bw_deck_link_slug', 'bw_deck_archived', 'bw_deck_snapshot' ], true ) ) {
				continue;
			}
			update_post_meta( $copy, $key, maybe_unserialize( $values[0] ) );
		}
		update_post_meta( $copy, 'bw_deck_link_slug', Blueworx_Deck_Builder_Deck::mint_slug() );

		return (int) $copy;
	}
}
