<?php
/**
 * Plugin bootstrap for Deck Builder.
 *
 * @package Blueworx\DeckBuilder
 *
 * Plugin Name:       BlueWorx Labs | Deck Builder
 * Plugin URI:        https://github.com/blueworx-io/blueworx_labs_deck-builder
 * Description:       Build client decks in wp-admin and publish each one to its own private client link.
 * Version:           0.4.1
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
define( 'BLUEWORX_DECK_BUILDER_VERSION', '0.4.1' );
define( 'BLUEWORX_DECK_BUILDER_FILE', __FILE__ );
define( 'BLUEWORX_DECK_BUILDER_DIR', plugin_dir_path( __FILE__ ) );
define( 'BLUEWORX_DECK_BUILDER_URL', plugin_dir_url( __FILE__ ) );

/*
 * The shared page editor library, which builds every record editor this plugin
 * has. Several plugins on one site may each carry a copy; the loader works out
 * which is newest and that one serves them all.
 */
require_once __DIR__ . '/blueworx-page-editor/blueworx-page-editor.php';

require_once __DIR__ . '/includes/class-blueworx-deck-builder-roles.php';
require_once __DIR__ . '/includes/class-blueworx-deck-builder-types.php';
require_once __DIR__ . '/includes/class-blueworx-deck-builder-starter.php';
require_once __DIR__ . '/includes/class-blueworx-deck-builder-packages.php';
require_once __DIR__ . '/includes/class-blueworx-deck-builder-deck.php';
require_once __DIR__ . '/includes/class-blueworx-deck-builder-library.php';
require_once __DIR__ . '/includes/class-blueworx-deck-builder-editor.php';
require_once __DIR__ . '/includes/class-blueworx-deck-builder-render.php';
require_once __DIR__ . '/includes/class-blueworx-deck-builder-link.php';
require_once __DIR__ . '/includes/class-blueworx-deck-builder-decks-screen.php';
require_once __DIR__ . '/includes/class-blueworx-deck-builder-list-screen.php';
require_once __DIR__ . '/includes/class-blueworx-deck-builder-admin.php';
require_once __DIR__ . '/includes/class-blueworx-deck-builder-plugin.php';

/**
 * Boot the plugin.
 *
 * @return void
 */
function blueworx_deck_builder_boot() {
	Blueworx_Deck_Builder_Plugin::boot();
}

add_action( 'plugins_loaded', 'blueworx_deck_builder_boot' );

register_activation_hook( __FILE__, [ 'Blueworx_Deck_Builder_Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'Blueworx_Deck_Builder_Plugin', 'deactivate' ] );

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
