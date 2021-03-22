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
 * @link              https://github.com/Mill3/denise-pelletier-tuxedo-importer
 * @since             0.0.1
 * @package           TDP_Tuxedo_Importer
 *
 * @wordpress-plugin
 * Plugin Name:       TDP - Tuxedo importer
 * Plugin URI:        https://github.com/Mill3/denise-pelletier-tuxedo-importer
 * Description:       Plugin for daily importation of data from Tuxedo API
 * Version:           0.0.5
 * Author:            Mill3 Studio
 * Author URI:        https://mill3.studio/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       tdp-tuxedo
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
define('TDP_TUXEDO_VERSION', '0.0.5');

/**
 * Define various constants
 */
define('TDP_TUXEDO_IMPORT_ACTION_NAME', 'tdp_tuxedo_import_action');
define('TDP_TUXEDO_CRON_SCHEDULE', 'tdp_tuxedo_cron_schedule');
define('TDP_TUXEDO_CRON_SCHEDULE_DURATION', 43200);

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-plugin-name-activator.php
 */
function tdp_tuxedo_activate()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-tdp-tuxedo-activator.php';
    TDP_Tuxedo_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-tdp-tuxedo-deactivator.php
 */
function tdp_tuxedo_deactivate()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-tdp-tuxedo-deactivator.php';
    TDP_Tuxedo_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'tdp_tuxedo_activate');
register_deactivation_hook(__FILE__, 'tdp_tuxedo_deactivate');

/**
 * Create a cron interval for this plugin
 */

function tdp_tuxedo_add_cron_interval($schedules)
{
    $schedules[TDP_TUXEDO_CRON_SCHEDULE] = [
        'interval'  => TDP_TUXEDO_CRON_SCHEDULE_DURATION,
        'display'   => "Every " . TDP_TUXEDO_CRON_SCHEDULE_DURATION . " seconds"
    ];
    return $schedules;
}

add_filter('cron_schedules', 'tdp_tuxedo_add_cron_interval');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-tdp-tuxedo.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.0.1
 */
function run_tdp_tuxedo_importer()
{
    $plugin = new TDP_Tuxedo_Importer();
    $plugin->run();
}

run_tdp_tuxedo_importer();
