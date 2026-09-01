<?php
/**
 * Removes everything Deck Builder created, and nothing else.
 *
 * WordPress runs this when the plugin is deleted, not when it is deactivated.
 *
 * @package Blueworx\DeckBuilder
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/*
 * Only the plugin's own options. There are no tables and no post types yet; add
 * their removal here at the same time as adding them, never afterwards.
 */
$blueworx_deck_builder_options = array(
	'blueworx_deck_builder_settings',
	'blueworx_deck_builder_version',
);

foreach ( $blueworx_deck_builder_options as $blueworx_deck_builder_option ) {
	delete_option( $blueworx_deck_builder_option );
	if ( is_multisite() ) {
		delete_site_option( $blueworx_deck_builder_option );
	}
}
