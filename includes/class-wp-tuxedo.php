<?php

// if(!function_exists('wp_get_current_user')) {
//     include(ABSPATH . "wp-includes/pluggable.php");
// }

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/Mill3/denise-pelletier-tuxedo-importer
 * @since      0.0.1
 *
 * @package    WP_TUXEDO
 * @subpackage WP_TUXEDO/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      0.0.1
 * @package    WP_TUXEDO
 * @subpackage WP_TUXEDO/includes
 * @author     Antoine Girard <antoine@mill3.studio>
 */
class WP_Tuxedo
{

  /**
   * The loader that's responsible for maintaining and registering all hooks that power
   * the plugin.
   *
   * @since    0.0.1
   * @access   protected
   * @var      Plugin_Name_Loader    $loader    Maintains and registers all hooks for the plugin.
   */
    public $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    0.0.1
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    0.0.1
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    0.0.1
     */
    public function __construct()
    {
        if (defined('WP_TUXEDO_VERSION')) {
            $this->version = WP_TUXEDO_VERSION;
        } else {
            $this->version = '0.0.1';
        }

        $this->plugin_name = 'wp-tuxedo';

        $this->load_dependencies();
        $this->define_admin_hooks();

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Plugin_Name_Loader. Orchestrates the hooks of the plugin.
     * - Plugin_Name_i18n. Defines internationalization functionality.
     * - Plugin_Name_Admin. Defines all hooks for the admin area.
     * - Plugin_Name_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    0.0.1
     * @access   private
     */
    private function load_dependencies()
    {

    /**
     * The class responsible for orchestrating the actions and filters of the
     * core plugin.
     */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-tuxedo-loader.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wp-tuxedo-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wp-tuxedo-menu-settings.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wp-tuxedo-menu-system.php';

        /**
         * The classes responsible for handling Tuxedo API actions
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'tuxedo/class-tuxedo.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'tuxedo/class-tuxedo-events.php';

        /**
         * The classes responsible for handling WP Post insertion
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'wp/class-wp-show-date.php';

        // set loader
        $this->loader = new WP_Tuxedo_Loader();
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    0.0.1
     * @access   private
     */
    private function define_admin_hooks()
    {
        // create admin instance
        $wp_tuxedo_admin = new WP_Tuxedo_Admin($this->get_plugin_name(), $this->get_version());
        $wp_tuxedo_menu_settings = new WP_Tuxedo_Menu_Settings();
        $wp_tuxedo_menu_logs = new WP_Tuxedo_Menu_System();

        // actions
        $this->loader->add_action(WP_TUXEDO_IMPORT_ACTION_NAME, $wp_tuxedo_admin, 'wp_tuxedo_admin_cron_task');
        // echo 'wp_ajax_' . WP_TUXEDO_NAMESPACE_PREFIX . '_run_cron';
        // $this->loader->add_action('wp_ajax_wp_tuxedo_run_cron', $this, 'foobar');
        // add_action( 'wp_ajax_wp_tuxedo_run_cron', array($this, 'foobar') );
        // add_action( 'wp_ajax_nopriv_wp_tuxedo_run_cron', array($this, 'foobar') );

        // filters
        $this->loader->add_filter(WP_TUXEDO_NAMESPACE_PREFIX . '/log_event', $wp_tuxedo_admin, 'log_event', 10, 2);

    }

    public function foobar() {
        echo "1";
        die();
    }


    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    0.0.1
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Plugin_Name_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }
}
