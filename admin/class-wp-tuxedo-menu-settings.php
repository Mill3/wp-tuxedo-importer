<?php

use Monolog\Handler\StreamHandler;
use Monolog\Formatter\HtmlFormatter;
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
class WP_Tuxedo_Menu_Settings
{

    /**
     * Initialize the class and set its properties.
     *
     * @since    0.0.1
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct()
    {
        // admin page panels
        $this->panel_prefix = WP_TUXEDO_NAMESPACE_PREFIX;
        $this->panel  = new \TDP\OptionsKit( $this->panel_prefix );
        $this->panel->set_page_title( __( 'WP Tuxedo - settings' ) );

        // admin page filters
        add_filter( $this->panel_prefix . '_menu', array($this, 'admin_page_setup_menu') );
        add_filter( $this->panel_prefix . '_settings_tabs', array($this, 'admin_page_setup_tabs'));
        add_filter( $this->panel_prefix . '_registered_settings', array($this, 'admin_page_settings'));
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    0.0.1
     */
    public function enqueue_styles()
    {
        // wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/plugin-name-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    0.0.1
     */
    public function enqueue_scripts()
    {
        // wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/plugin-name-admin.js', array( 'jquery' ), $this->version, false);
    }

    /**
     * Set options page menu item
     */
    public function admin_page_setup_menu($menu) {
        $menu['parent'] = 'tools.php';
	    $menu['capability'] = 'manage_options';
        $menu['page_title'] = __( 'WP Tuxedo - Settings' );
        $menu['menu_title'] = $menu['page_title'];

        return $menu;
    }

    /**
     * Set options page tabs
     */
    public function admin_page_setup_tabs($tabs) {
        return array(
            'general' => __('General')
        );
    }

    /**
     * Set options page menu item
     */
    public function admin_page_settings($settings) {
        $settings = array(
            'general' => array(
                array(
                    'id'   => 'tuxedo_account',
                    'name' => __('Tuxedo account'),
                    'desc' => __('Enter your tuxedo account name'),
                    'type' => 'text',
                ),
                array(
                    'id'   => 'tuxedo_username',
                    'name' => __('Username'),
                    'desc' => __('Enter your tuxedo username'),
                    'type' => 'text',
                ),
                array(
                    'id'   => 'tuxedo_password',
                    'name' => __('Password'),
                    'desc' => __('Enter your tuxedo password'),
                    'type' => 'text',
                ),
                array(
                    'id'   => 'tuxedo_active',
                    'name' => __('Run Cron ?'),
                    'type' => 'checkbox',
                    'desc' => __('Uncheck this box to disable the hourly cron job importing data from Tuxedo API.'),
                ),
            ),
        );

        return $settings;
    }

}
