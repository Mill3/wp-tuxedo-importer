<?php

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use WP_Tuxedo\Tuxedo;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/Mill3/denise-pelletier-tuxedo-importer
 * @since      0.0.1
 *
 * @package    WP_TUXEDO
 * @subpackage WP_TUXEDO/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WP_TUXEDO
 * @subpackage WP_TUXEDO/admin
 * @author     Antoine Girard <antoine@mill3.studio>
 */
class WP_Tuxedo_Admin
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


    private $tuxedo_instance;

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

        $this->logname = 'wp-tuxedo-admin';
        // create a logger
        $this->log = new Logger($this->logname);
        $this->log->pushHandler(new StreamHandler(__DIR__."/logs/{$this->logname}.log", Logger::DEBUG));
        $this->tuxedo_instance = new \WP_Tuxedo\Tuxedo\Tuxedo_API();
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
    public function wp_tuxedo_admin_cron_task()
    {
        $this->log->info('Starting wp_tuxedo_admin_cron_task');
        $this->tuxedo_instance->run();
    }
}
