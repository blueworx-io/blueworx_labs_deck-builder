<?php
/**
 * Plugin bootstrap for Deck Builder.
 *
 * @package Blueworx\DeckBuilder
 *
 * Plugin Name:       Deck Builder
 * Plugin URI:        https://github.com/blueworx-io/blueworx_labs_deck-builder
 * Description:       Build and present decks from WordPress.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      8.2
 * Author:            BlueWorx
 * Author URI:        https://blueworx.io
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       blueworx-labs-deck-builder
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

/**
 * The single version string. Kept equal to the Version: header above and to
 * package.json — CI fails the build if the three disagree.
 */
define( 'BLUEWORX_DECK_BUILDER_VERSION', '0.1.0' );
define( 'BLUEWORX_DECK_BUILDER_FILE', __FILE__ );
define( 'BLUEWORX_DECK_BUILDER_DIR', plugin_dir_path( __FILE__ ) );
define( 'BLUEWORX_DECK_BUILDER_URL', plugin_dir_url( __FILE__ ) );

require_once __DIR__ . '/includes/class-blueworx-deck-builder-admin.php';

/**
 * Boot the plugin.
 *
 * @return void
 */
function blueworx_deck_builder_boot() {
	Blueworx_Deck_Builder_Admin::instance()->register();
}

add_action( 'plugins_loaded', 'blueworx_deck_builder_boot' );

/*
 * Auto-updates. Sites install this plugin from GitHub Releases, so the update
 * checker is wired up from the first version rather than added once a release
 * has already gone out without it.
 */
require_once BLUEWORX_DECK_BUILDER_DIR . 'plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$blueworx_deck_builder_update_checker = PucFactory::buildUpdateChecker(
	'https://github.com/blueworx-io/blueworx_labs_deck-builder/',
	__FILE__,
	'blueworx-labs-deck-builder'
);

/*
 * The repo is private, so a site needs a token to see releases at all. It lives
 * in wp-config.php, never here and never in the repo:
 *
 *     define( 'BLUEWORX_PLUGIN_UPDATE_TOKEN', 'github_pat_...' );
 */
if ( defined( 'BLUEWORX_PLUGIN_UPDATE_TOKEN' ) && BLUEWORX_PLUGIN_UPDATE_TOKEN ) {
	$blueworx_deck_builder_update_checker->setAuthentication( BLUEWORX_PLUGIN_UPDATE_TOKEN );
}

/*
 * Install the zip attached to the Release, not GitHub's generated source
 * tarball — that extracts to a differently named folder, which WordPress treats
 * as a second plugin, and it carries every development file in the repo.
 */
$blueworx_deck_builder_update_checker->getVcsApi()->enableReleaseAssets();
