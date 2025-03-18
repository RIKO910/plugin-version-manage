<?php
/*
 * Plugin Name:       Plugin Version Manage
 * Plugin URI:        https://www.wooxperto.com/
 * Description:       Plugin Version Manage
 * Version:           1.0.1
 * Requires at least: 6.5
 * Requires PHP:      7.2
 * Author:            WooCopilot
 * Author URI:        https://www.wooxperto.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       plugin-version-manage
 * Domain Path:       /languages/
*/

/*
Plugin Version Manage
*/

defined( 'ABSPATH' ) || exit; // Exist if accessed directly.

// Including classes.
require_once __DIR__ . '/includes/class-plugin-version-manage.php';
require_once __DIR__ . '/includes/class-admin.php';

/**
 * Initializing Plugin.
 *
 * @since 1.0.0
 * @retun Object Plugin object.
 */
function plugin_version_manage() {
    return new PVM_plugin_Version_Manage( __FILE__, '1.0.0' );
}

plugin_version_manage();