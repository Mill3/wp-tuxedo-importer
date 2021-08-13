<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

use WP_Tuxedo\Tuxedo;

/**
 * Handling admin settings page
 *
 * @link  https://github.com/Mill3/denise-pelletier-tuxedo-importer
 * @since 0.2.0
 *
 * @package    WP_Tuxedo
 * @subpackage WP_Tuxedo/admin
 */

class WP_Tuxedo_Menu_System
{
    protected $system_checks_list = [
        'post_type' => null,
        'acf' => null
        // TODO: check if ACF fields exists
    ];

    /**
     * Initialize the class and set its properties.
     *
     * @since 0.0.1
     * @param string $plugin_name The name of this plugin.
     * @param string $version     The version of this plugin.
     */
    public function __construct()
    {
        add_action('admin_menu', array( $this, 'admin_page_setup_menu' ), 10);
        add_action('admin_bar_menu', array($this, 'admin_page_toolbar_action'), 999);
        // add_action('admin_notices', array($this, 'admin_notice'));
    }

    /**
     * Set options page menu item
     */
    public function admin_page_setup_menu()
    {
        add_submenu_page('tools.php', 'WP Tuxedo - Log', 'WP Tuxedo - System', 'manage_options', "wp_tuxedo_logs", array($this, 'render'));
    }

    /**
     * Set a menu item in WP admin toolbar
     */
    public function admin_page_toolbar_action($wp_admin_bar)
    {
        $wp_admin_bar->add_node(
            array(
                'id'     => 'wp-tuxedo-run',
                'title'  => __('WP Tuxedo Run Import'),
                'href'   => '/wp-admin/?wp_tuxedo_run_cron=1',
            )
        );
    }

    private function system_check()
    {
        // check if ACF is installed
        $this->system_checks_list['acf'] = class_exists('ACF');

        // check if post-type show_date is set
        $this->system_checks_list['post_type'] = post_type_exists('show_date');
    }

    /**
     * Get most recent log file
     *
     * @param [string] $dir
     *
     * @return array
     */
    private function get_log($dir)
    {
        $ignored = array('.', '..');
        $files = array();
        foreach (scandir($dir) as $file) {
            if (in_array($file, $ignored)) {
                continue;
            }
            $files[$file] = filemtime($dir . '/' . $file);
        }
        arsort($files);
        $files = array_keys($files);

        return ($files) ? $files : false;
    }

    public function admin_notice()
    {
        echo '<div class="notice notice-info is-dismissible">notice!!</div>';
    }

    /**
     * Render system page
     *
     * @return string
     */
    public function render()
    {
        // get log files
        $directory = WP_TUXEDO_PLUGIN_DIR . '/admin/logs/';
        $files = $this->get_log($directory);

        // run system check
        $this->system_check();

        $icon_valid = '<div alt="f319" class="dashicons dashicons-cloud-saved" style="color: green;"></div>';
        $icon_invalid = '<div alt="f319" class="dashicons dashicons-admin-plugins" style="color: red;"></div>';

        ?>
        <div class="wrap">
        <h1 class="wp-heading-block" style="margin-bottom: 1rem;">WP Tuxedo : system check</h1>

        <ul>
            <li><strong>Advanced Custom Field :</strong> <?= $this->system_checks_list['acf'] ? $icon_valid : $icon_invalid ?></li>
            <li><strong>Custom post type :</strong> <?= $this->system_checks_list['post_type'] ? $icon_valid : $icon_invalid ?></li>
        </ul>

        <h1 class="wp-heading-block" style="margin-bottom: 1rem;">Cron logs</h1>

        <?php

        if (isset($files[0])) {
            include WP_TUXEDO_PLUGIN_DIR . 'admin/logs/' . $files[0];
        } else {
            echo "<p>No WP Tuxedo log file found</p>";
        } ?>
        </div>
        <?php
    }
}
