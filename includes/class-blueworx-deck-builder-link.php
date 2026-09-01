<?php
/**
 * The client link: how a deck is reached, and who is allowed to reach it.
 *
 * @package Blueworx\DeckBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * A deck lives at /deck/<twelve characters>/ and nowhere else.
 *
 * The route deliberately does not go through WordPress's template hierarchy:
 * the deck is its own document, so a theme cannot style it, another plugin
 * cannot inject into it, and nothing on the site can change what a client sees.
 */
final class Blueworx_Deck_Builder_Link {

	const QUERY_VAR = 'bw_deck_slug';
	const COOKIE    = 'bw_deck_pass_';

	/**
	 * Boot.
	 *
	 * @return void
	 */
	public static function register() {
		add_action( 'init', [ __CLASS__, 'rewrite' ] );
		add_filter( 'query_vars', [ __CLASS__, 'query_var' ] );
		add_action( 'template_redirect', [ __CLASS__, 'route' ] );
	}

	/**
	 * The one rewrite rule this plugin adds.
	 *
	 * @return void
	 */
	public static function rewrite() {
		add_rewrite_rule( '^deck/([a-z0-9]{12})/?$', 'index.php?' . self::QUERY_VAR . '=$matches[1]', 'top' );
	}

	/**
	 * Let WordPress carry the slug through.
	 *
	 * @param array<int,string> $vars Query variables.
	 * @return array<int,string>
	 */
	public static function query_var( array $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * Serve a deck, ask for its password, or refuse.
	 *
	 * @return void
	 */
	public static function route() {
		$slug = get_query_var( self::QUERY_VAR );
		if ( ! $slug ) {
			return;
		}

		$deck = Blueworx_Deck_Builder_Deck::find_by_slug( $slug );
		if ( null === $deck || ! self::may_view( $deck ) ) {
			self::not_found();
			return;
		}

		if ( ! self::password_met( $deck ) ) {
			self::headers();
			Blueworx_Deck_Builder_Render::password_page( $deck, self::password_failed() );
			exit;
		}

		self::headers();
		Blueworx_Deck_Builder_Render::deck( $deck );
		exit;
	}

	/**
	 * Whether this deck may be shown at all.
	 *
	 * A draft is visible to somebody who could edit it, so Preview works
	 * before publishing — and to nobody else. Archived and link-disabled are
	 * absolute: they are how an administrator takes a deck back, so a logged-in
	 * preview must not quietly still work.
	 *
	 * @param Blueworx_Deck_Builder_Deck $deck Deck.
	 * @return bool
	 */
	private static function may_view( Blueworx_Deck_Builder_Deck $deck ) {
		if ( 'archived' === $deck->status() ) {
			return false;
		}
		if ( ! $deck->get( 'link_enabled', true ) ) {
			return false;
		}
		if ( 'published' === $deck->status() ) {
			return true;
		}
		return current_user_can( Blueworx_Deck_Builder_Admin::CAPABILITY );
	}

	/**
	 * Whether the password, if there is one, has been given.
	 *
	 * @param Blueworx_Deck_Builder_Deck $deck Deck.
	 * @return bool
	 */
	private static function password_met( Blueworx_Deck_Builder_Deck $deck ) {
		$password = (string) $deck->get( 'password' );
		if ( ! $deck->get( 'password_on' ) || '' === $password ) {
			return true;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- a client has no WordPress session to carry a nonce; the password is the check.
		if ( isset( $_POST['bw_deck_password'] ) ) {
			$given = sanitize_text_field( wp_unslash( $_POST['bw_deck_password'] ) );
			if ( hash_equals( $password, $given ) ) {
				setcookie( self::cookie_name( $deck ), self::cookie_value( $deck ), time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
				return true;
			}
			return false;
		}

		$cookie = self::cookie_name( $deck );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitised -- compared with hash_equals against a value this plugin wrote; sanitising it would change what is compared.
		$held = isset( $_COOKIE[ $cookie ] ) ? (string) wp_unslash( $_COOKIE[ $cookie ] ) : '';
		return '' !== $held && hash_equals( self::cookie_value( $deck ), $held );
	}

	/**
	 * Whether a password was given and was wrong.
	 *
	 * @return bool
	 */
	private static function password_failed() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- see password_met().
		return isset( $_POST['bw_deck_password'] );
	}

	/**
	 * The cookie that remembers a client got the password right.
	 *
	 * @param Blueworx_Deck_Builder_Deck $deck Deck.
	 * @return string
	 */
	private static function cookie_name( Blueworx_Deck_Builder_Deck $deck ) {
		return self::COOKIE . substr( (string) $deck->get( 'link_slug' ), 0, 12 );
	}

	/**
	 * What that cookie holds. Derived from the password and this site's own
	 * salt, so the cookie is not the password and changing the password stops
	 * every cookie already handed out.
	 *
	 * @param Blueworx_Deck_Builder_Deck $deck Deck.
	 * @return string
	 */
	private static function cookie_value( Blueworx_Deck_Builder_Deck $deck ) {
		return wp_hash( 'bw-deck|' . $deck->get( 'link_slug' ) . '|' . $deck->get( 'password' ) );
	}

	/**
	 * A deck is never indexed and never cached by a shared cache — the link is
	 * the only thing keeping it private.
	 *
	 * @return void
	 */
	private static function headers() {
		if ( ! headers_sent() ) {
			header( 'X-Robots-Tag: noindex, nofollow, noarchive' );
			header( 'Cache-Control: private, no-store' );
			header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
		}
	}

	/**
	 * Nothing here. The same answer for a slug that never existed, a deck that
	 * was archived and a link that was turned off — telling them apart would
	 * let somebody map what exists.
	 *
	 * @return void
	 */
	private static function not_found() {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}
}
