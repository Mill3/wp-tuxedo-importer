<?php

/**
 * Fired during plugin activation
 *
 * @link       https://github.com/Mill3/denise-pelletier-tuxedo-importer
 * @since      0.0.1
 *
 * @package    TDP_Tuxedo
 * @subpackage TDP_Tuxedo/includes
 */


/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      0.0.1
 * @package    TDP_Tuxedo
 * @subpackage TDP_Tuxedo/includes
 * @author     Antoine Girard <antoine@mill3.studio>
 */
class TDP_Tuxedo_Activator
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
        if (!wp_next_scheduled(TDP_TUXEDO_IMPORT_ACTION_NAME)) {
            wp_schedule_event(time(), TDP_TUXEDO_CRON_SCHEDULE, TDP_TUXEDO_IMPORT_ACTION_NAME);
        }
    }
}
