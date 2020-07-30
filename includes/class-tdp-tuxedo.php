<?php



/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/Mill3/denise-pelletier-tuxedo-importer
 * @since      0.0.1
 *
 * @package    TDP_Tuxedo
 * @subpackage TDP_Tuxedo/includes
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
 * @package    TDP_Tuxedo
 * @subpackage TDP_Tuxedo/includes
 * @author     Antoine Girard <antoine@mill3.studio>
 */
class TDP_Tuxedo_Importer
{

  /**
   * The loader that's responsible for maintaining and registering all hooks that power
   * the plugin.
   *
   * @since    0.0.1
   * @access   protected
   * @var      Plugin_Name_Loader    $loader    Maintains and registers all hooks for the plugin.
   */
    protected $loader;

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
        if (defined('TDP_TUXEDO_VERSION')) {
            $this->version = TDP_TUXEDO_VERSION;
        } else {
            $this->version = '0.0.1';
        }

        $this->plugin_name = 'tdp-tuxedo';

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
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-tdp-tuxedo-loader.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-tdp-tuxedo-admin.php';

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
        $this->loader = new TDP_Tuxedo_Loader();
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
        $plugin_admin = new TDP_Tuxedo_Admin($this->get_plugin_name(), $this->get_version());

        // register action in plugin admin class
        $this->loader->add_action(TDP_TUXEDO_IMPORT_ACTION_NAME, $plugin_admin, 'wp_cron_tuxedo_import');
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
