<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Fired during plugin activation
 *
 * @link       https://github.com/Mill3/denise-pelletier-tuxedo-importer
 * @since      0.0.1
 *
 * @package    WP_TUXEDO
 * @subpackage WP_TUXEDO/includes
 */


/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      0.0.1
 * @package    WP_TUXEDO
 * @subpackage WP_TUXEDO/includes
 * @author     Antoine Girard <antoine@mill3.studio>
 */
class WP_tuxedo_Activator
{

  /**
   * Short Description. (use period)
   *
   * Long Description.
   *
   * @since    0.0.1
   */
    public static function activate()
    {
        if (!wp_next_scheduled(WP_TUXEDO_IMPORT_ACTION_NAME)) {
            wp_schedule_event(time(), WP_TUXEDO_CRON_SCHEDULE, WP_TUXEDO_IMPORT_ACTION_NAME);
        }
    }
}
