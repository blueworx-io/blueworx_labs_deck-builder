<?php
/**
 * Who may use the deck builder, and the role that exists so somebody can.
 *
 * @package Blueworx\DeckBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * One capability decides everything in wp-admin: the menu, every screen, every
 * record and the REST routes behind them. Administrators have it, the sales
 * agent role has it, and nobody else does — not an editor, not an author, not
 * a shop manager somebody adds next year.
 *
 * A capability of this plugin's own rather than `manage_options`, because
 * `manage_options` is the site's own settings. Handing that to a sales agent
 * to let them write a quote would also hand them permalinks, users and the
 * plugin screen. This grants exactly the deck builder and nothing else.
 *
 * The client link is deliberately outside all of this. A published deck is
 * meant to be opened by somebody with no WordPress account at all, so nothing
 * on that route asks about capabilities — see Blueworx_Deck_Builder_Link.
 */
final class Blueworx_Deck_Builder_Roles {

	/**
	 * The capability every screen and record in this plugin is behind.
	 */
	const CAPABILITY = 'manage_blueworx_decks';

	/**
	 * The role that exists so somebody who is not an administrator can build
	 * decks without being given the run of the site.
	 */
	const ROLE = 'blueworx_sales_agent';

	/**
	 * What the sales agent role can do, and the whole of it.
	 *
	 * `read` is what lets them into wp-admin at all. `upload_files` is what
	 * lets the media picker open, which a deck needs for a client logo and a
	 * case study's screenshots. Everything else about the site — posts, pages,
	 * comments, users, settings, plugins — is not on this list and so is not
	 * theirs.
	 *
	 * @var array<int,string>
	 */
	const ROLE_CAPS = [ 'read', 'upload_files', self::CAPABILITY ];

	/**
	 * Where the installed shape of the role is recorded.
	 */
	const VERSION_OPTION = 'blueworx_deck_builder_roles_version';

	/**
	 * Bumped whenever ROLE_CAPS or the granting below changes, so a site that
	 * already has the plugin picks the change up on its next request rather
	 * than only on a fresh activation. Activation does not run on an update,
	 * which is how a permissions change silently misses every existing site.
	 */
	const VERSION = 1;

	/**
	 * Boot.
	 *
	 * @return void
	 */
	public static function register() {
		// Late on init, after the post types are registered, so anything
		// reading capabilities during a role change sees the finished shape.
		add_action( 'init', [ __CLASS__, 'maybe_install' ], 20 );
	}

	/**
	 * Install the role and the capability, but only when they are not already
	 * as this version wants them. Reads one option on a normal request and
	 * writes nothing.
	 *
	 * @return void
	 */
	public static function maybe_install() {
		if ( (int) get_option( self::VERSION_OPTION, 0 ) === self::VERSION ) {
			return;
		}
		self::install();
	}

	/**
	 * Create or correct the role, and give administrators the capability.
	 *
	 * @return void
	 */
	public static function install() {
		$caps = [];
		foreach ( self::ROLE_CAPS as $cap ) {
			$caps[ $cap ] = true;
		}

		// add_role() does nothing at all when the role already exists, so the
		// capabilities are set explicitly afterwards as well. Without that, a
		// role installed by an older version would keep the older version's
		// capabilities for ever.
		add_role( self::ROLE, __( 'BlueWorx: Sales Agent', 'blueworx-labs-deck-builder' ), $caps );

		$role = get_role( self::ROLE );
		if ( $role instanceof WP_Role ) {
			foreach ( self::ROLE_CAPS as $cap ) {
				$role->add_cap( $cap );
			}
		}

		$administrator = get_role( 'administrator' );
		if ( $administrator instanceof WP_Role ) {
			$administrator->add_cap( self::CAPABILITY );
		}

		update_option( self::VERSION_OPTION, self::VERSION );
	}

	/**
	 * Take the role and the capability back out. For uninstall only —
	 * deactivating must not do this, or turning the plugin off and on again
	 * would leave every sales agent on the site with a role that no longer
	 * exists and no way back into their own work.
	 *
	 * @return void
	 */
	public static function remove() {
		$administrator = get_role( 'administrator' );
		if ( $administrator instanceof WP_Role ) {
			$administrator->remove_cap( self::CAPABILITY );
		}
		remove_role( self::ROLE );
		delete_option( self::VERSION_OPTION );
	}

	/**
	 * The capability map every one of this plugin's post types is registered
	 * with, so WordPress's own checks — `edit_post`, `delete_post`,
	 * `read_post`, and the REST and revision machinery behind them — ask the
	 * same question this plugin's screens ask.
	 *
	 * Every entry is the same capability on purpose. There is no useful
	 * distinction here between editing your own deck and editing somebody
	 * else's: a sales team works on each other's quotes, and a deck nobody but
	 * its author could open would be worse than no permissions at all.
	 *
	 * Without this the four post types would fall back to the built-in post
	 * capabilities, and an editor — who has `edit_others_posts` — would be
	 * able to change any deck on the site through any route that checks the
	 * post type rather than this plugin's screens.
	 *
	 * @return array<string,string>
	 */
	public static function post_type_caps() {
		$caps = [
			'edit_posts',
			'edit_others_posts',
			'edit_published_posts',
			'edit_private_posts',
			'publish_posts',
			'read_private_posts',
			'delete_posts',
			'delete_others_posts',
			'delete_published_posts',
			'delete_private_posts',
			'create_posts',
		];

		$map = [];
		foreach ( $caps as $cap ) {
			$map[ $cap ] = self::CAPABILITY;
		}
		return $map;
	}
}
