<?php
/**
 * Where every part of the plugin is booted.
 *
 * @package Blueworx\DeckBuilder
 */

defined( 'ABSPATH' ) || exit;

/**
 * One place that knows what this plugin is made of, so the bootstrap file
 * stays a bootstrap file.
 */
final class Blueworx_Deck_Builder_Plugin {

	/**
	 * Boot everything.
	 *
	 * @return void
	 */
	public static function boot() {
		Blueworx_Deck_Builder_Roles::register();
		Blueworx_Deck_Builder_Types::register();
		Blueworx_Deck_Builder_Editor::register();
		Blueworx_Deck_Builder_Link::register();
		Blueworx_Deck_Builder_Admin::instance()->register();
	}

	/**
	 * On activation: install the sales agent role and the capability every
	 * screen is behind, register the post types, add the rewrite rule, flush so
	 * client links work immediately, and seed the packages, case studies and
	 * library entries a fresh install needs to be usable.
	 *
	 * Activation does not run on an update, so the role is also checked on
	 * every request — see Blueworx_Deck_Builder_Roles::maybe_install().
	 *
	 * @return void
	 */
	public static function activate() {
		Blueworx_Deck_Builder_Roles::install();
		Blueworx_Deck_Builder_Types::types();
		Blueworx_Deck_Builder_Link::rewrite();
		flush_rewrite_rules();
		Blueworx_Deck_Builder_Starter::seed();
	}

	/**
	 * On deactivation: take the rewrite rule back out, so /deck/... stops
	 * resolving rather than 404ing through this plugin's route.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
