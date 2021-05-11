<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://github.com/Mill3/denise-pelletier-tuxedo-importer
 * @since      0.0.1
 *
 * @package    WP_TUXEDO
 * @subpackage WP_TUXEDO/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      0.0.1
 * @package    WP_TUXEDO
 * @subpackage WP_TUXEDO/includes
 * @author     Antoine Girard <antoine@mill3.studio>
 */
class WP_tuxedo_Deactivator
{

  /**
   * Short Description. (use period)
   *
   * Long Description.
   *
   * @since    0.0.1
   */
    public static function deactivate()
    {
        $timestamp = wp_next_scheduled(WP_TUXEDO_IMPORT_ACTION_NAME);
        wp_unschedule_event($timestamp, WP_TUXEDO_IMPORT_ACTION_NAME);
    }
}
