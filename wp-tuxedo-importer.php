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
 * Version:           0.2.0
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

if ( ! defined('TUXEDO_BASE_URI') ) {
    return;
}


/**
 * Currently plugin version.
 */
define('WP_TUXEDO_VERSION', '0.2.0');

/**
 * Define various constants
 */
define('WP_TUXEDO_IMPORT_ACTION_NAME', 'wp_tuxedo/import_all');
define('WP_TUXEDO_CRON_SCHEDULE', 'wp_tuxedo_cron_schedule');
define('WP_TUXEDO_CRON_SCHEDULE_DURATION', 60);

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function WP_tuxedo_activate()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-wp-tuxedo-activator.php';
    WP_tuxedo_Activator::activate();
}

register_activation_hook(__FILE__, 'WP_tuxedo_activate');

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-tdp-tuxedo-deactivator.php
 */
function WP_tuxedo_deactivate()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-wp-tuxedo-deactivator.php';
    WP_tuxedo_Deactivator::deactivate();
}

register_deactivation_hook(__FILE__, 'WP_tuxedo_deactivate');

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
    $root = new WP_Tuxedo_Importer();
    // print_r($root);
    $root->run();
}

run_wp_tuxedo();