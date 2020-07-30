<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use TDP_Tuxedo\Tuxedo;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/Mill3/denise-pelletier-tuxedo-importer
 * @since      0.0.1
 *
 * @package    TDP_Tuxedo
 * @subpackage TDP_Tuxedo/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    TDP_Tuxedo
 * @subpackage TDP_Tuxedo/admin
 * @author     Antoine Girard <antoine@mill3.studio>
 */
class TDP_Tuxedo_Admin
{

  /**
   * The ID of this plugin.
   *
   * @since    0.0.1
   * @access   private
   * @var      string    $plugin_name    The ID of this plugin.
   */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    0.0.1
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Log instance.
     *
     * @var object
     */
    private $log;

    /**
     * Initialize the class and set its properties.
     *
     * @since    0.0.1
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    0.0.1
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/plugin-name-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    0.0.1
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/plugin-name-admin.js', array( 'jquery' ), $this->version, false);
    }

    /**
     * Register cron jobs
     */
    public function wp_cron_tuxedo_import()
    {
        $tuxedo = new \TDP_Tuxedo\Tuxedo\Tuxedo_API();
        $tuxedo->run();
    }
}
