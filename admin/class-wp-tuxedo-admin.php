<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Logger;
use WP_Tuxedo\Tuxedo;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link  https://github.com/Mill3/denise-pelletier-tuxedo-importer
 * @since 0.0.1
 *
 * @package    WP_Tuxedo
 * @subpackage WP_Tuxedo/admin
 */

class WP_Tuxedo_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since  0.0.1
     * @access private
     * @var    string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since  0.0.1
     * @access private
     * @var    string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Log instance.
     *
     * @var object
     */
    public $log;


    public $tuxedo_instance;

    /**
     * Initialize the class and set its properties.
     *
     * @since 0.0.1
     * @param string $plugin_name The name of this plugin.
     * @param string $version     The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        // create a logger
        $this->logname = 'wp-tuxedo-log';
        $this->formatter = new HtmlFormatter();
        $this->stream = new RotatingFileHandler(__DIR__."/logs/{$this->logname}.html", 5, Logger::DEBUG);
        $this->stream->setFormatter($this->formatter);
        $this->log = new Logger($this->logname);
        $this->log->pushHandler($this->stream);

        // create tuxedo main importer class instance
        $this->tuxedo_api_events_instance = new \WP_Tuxedo\Tuxedo\Tuxedo_API_Events();

        // force run with GET param
        add_action('admin_init', array($this, 'force_run'));
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since 0.0.1
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/plugin-name-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since 0.0.1
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/plugin-name-admin.js', array( 'jquery' ), $this->version, false);
    }

    /**
     * Filter wp_tuxedo/log_event
     *
     * Filter for sending message to plugin log file
     *
     * @since 0.2.0
     */
    public function log_event($message, $level = 'info')
    {
        switch ($level) {
        case 'error':
            $this->log->error($message);
            $this->log->reset();
            break;
        case 'notice':
            $this->log->notice($message);
            $this->log->reset();
            break;
        case 'warning':
            $this->log->warning($message);
            $this->log->reset();
            break;
        default:
            $this->log->info($message);
            $this->log->reset();
            break;
        }
    }

    public function force_run() {
         if( isset($_GET['wp_tuxedo_run_cron']) ) {
            $this->log_event('Cron: wp_tuxedo_admin_cron_task force run run....', 'notice');
            $this->tuxedo_api_events_instance->run();

            // redirecting to logs page
            wp_redirect( admin_url( '/tools.php?page=wp_tuxedo_logs' ) );

            // TODO: not working..
            // set admin_notices
            add_action( 'admin_notices', array($this, 'admin_notices') );
            exit;
        }
    }

    public function admin_notices() {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e( 'Done!' ); ?></p>
        </div>
        <?php
    }

    /**
     * Register cron jobs
     *
     * @since 0.0.1
     */
    public function wp_tuxedo_admin_cron_task()
    {
        $this->settings = get_option('wp_tuxedo_settings');

        if (isset($this->settings['tuxedo_active'])) {
            $this->log_event('Cron: wp_tuxedo_admin_cron_task starting...', 'notice');
            $this->tuxedo_api_events_instance->run();
        } else {
            $this->log_event('Cron: wp_tuxedo_admin_cron_task is paused, passing...', 'warning');
        }

        wp_die();
    }
}
