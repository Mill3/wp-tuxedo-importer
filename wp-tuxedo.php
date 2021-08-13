<?php

require __DIR__ . '/vendor/autoload.php';

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/Mill3/wp-tuxedo-importer
 * @since             0.0.1
 * @package           WP_tuxedo_Importer
 *
 * @wordpress-plugin
 * Plugin Name:       WP - Tuxedo importer
 * Plugin URI:        https://github.com/Mill3/wp-tuxedo-importer
 * Description:       Plugin for daily importation of data from Tuxedo API
 * Version:           0.2.1
 * Author:            Mill3 Studio
 * Author URI:        https://mill3.studio/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-tuxedo
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined('WPINC') ) {
    die;
}

/**
 * Currently plugin version.
 */
define('WP_TUXEDO_VERSION', '0.2.1');

/**
 * Define various constants
 */

if ( ! defined( 'TUXEDO_BASE_URI' ) ) {
    define( 'TUXEDO_BASE_URI', 'https://api.tuxedoticket.ca/' );
}

if ( ! defined( 'WP_TUXEDO_PLUGIN_DIR' ) ) {
    define( 'WP_TUXEDO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if (! defined('WP_TUXEDO_NAMESPACE_PREFIX')) {
    define('WP_TUXEDO_NAMESPACE_PREFIX', 'wp_tuxedo');
}

if (! defined('WP_TUXEDO_IMPORT_ACTION_NAME')) {
    define('WP_TUXEDO_IMPORT_ACTION_NAME', WP_TUXEDO_NAMESPACE_PREFIX . "/import_all");
}

if (! defined('WP_TUXEDO_CRON_SCHEDULE')) {
    define('WP_TUXEDO_CRON_SCHEDULE', WP_TUXEDO_NAMESPACE_PREFIX . "_cron_schedule");
}

if (! defined('WP_TUXEDO_CRON_SCHEDULE_DURATION')) {
    define('WP_TUXEDO_CRON_SCHEDULE_DURATION', 3600 * 12); // every 12 hours
}

if (! defined('WP_TUXEDO_POST_TYPE')) {
    define('WP_TUXEDO_POST_TYPE', "show_date");
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function wp_tuxedo_activate()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-wp-tuxedo-activator.php';
    WP_Tuxedo_Activator::activate();
}

register_activation_hook(__FILE__, 'wp_tuxedo_activate');

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-tdp-tuxedo-deactivator.php
 */
function wp_tuxedo_deactivate()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-wp-tuxedo-deactivator.php';
    WP_Tuxedo_Deactivator::deactivate();
}

register_deactivation_hook(__FILE__, 'wp_tuxedo_deactivate');

/**
 * Create a cron interval for this plugin
 */

function wp_tuxedo_add_cron_interval($schedules)
{
    $schedules[WP_TUXEDO_CRON_SCHEDULE] = [
        'interval'  => WP_TUXEDO_CRON_SCHEDULE_DURATION,
        'display'   => "Every " . WP_TUXEDO_CRON_SCHEDULE_DURATION . " seconds"
    ];
    return $schedules;
}

add_filter('cron_schedules', 'wp_tuxedo_add_cron_interval');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-wp-tuxedo.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.0.1
 */
function run_wp_tuxedo()
{
    $root = new WP_Tuxedo();
    $root->run();
}

run_wp_tuxedo();