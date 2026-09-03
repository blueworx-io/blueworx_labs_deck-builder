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
 * Every deck, package, case study and library entry this plugin created, with
 * its post meta. Nothing else on the site is touched: the media library keeps
 * the logos and screenshots, because those were uploaded by hand and are not
 * this plugin's to delete.
 *
 * The post types are not registered during uninstall — WordPress loads this
 * file on its own — so the query names them directly rather than asking
 * whether they exist.
 */
$blueworx_deck_builder_types = array( 'bw_deck', 'bw_deck_package', 'bw_case_study', 'bw_library_item' );

foreach ( $blueworx_deck_builder_types as $blueworx_deck_builder_type ) {
	$blueworx_deck_builder_ids = get_posts(
		array(
			'post_type'   => $blueworx_deck_builder_type,
			'post_status' => 'any',
			'numberposts' => -1,
			'fields'      => 'ids',
		)
	);
	foreach ( $blueworx_deck_builder_ids as $blueworx_deck_builder_id ) {
		wp_delete_post( $blueworx_deck_builder_id, true );
	}
}

/*
 * The sales agent role, and the capability administrators were given. Removed
 * here and never on deactivation: turning the plugin off and on again must not
 * strip a role off the people who have it.
 */
remove_role( 'blueworx_sales_agent' );
$blueworx_deck_builder_admin = get_role( 'administrator' );
if ( $blueworx_deck_builder_admin instanceof WP_Role ) {
	$blueworx_deck_builder_admin->remove_cap( 'manage_blueworx_decks' );
}

$blueworx_deck_builder_options = array(
	'blueworx_deck_builder_settings',
	'blueworx_deck_builder_version',
	'blueworx_deck_builder_roles_version',
	'blueworx_deck_builder_seed_version',
);

foreach ( $blueworx_deck_builder_options as $blueworx_deck_builder_option ) {
	delete_option( $blueworx_deck_builder_option );
	if ( is_multisite() ) {
		delete_site_option( $blueworx_deck_builder_option );
	}
}
