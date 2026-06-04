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
     */
    public function __construct()
    {
        add_action('admin_menu',            array($this, 'admin_page_setup_menu'), 10);
        add_action('admin_bar_menu',        array($this, 'admin_page_toolbar_action'), 999);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_log_assets'));
        add_action('wp_ajax_wp_tuxedo_get_log', array($this, 'ajax_get_log'));
        // add_action('admin_notices', array($this, 'admin_notice'));
    }

    /**
     * Set options page menu item
     */
    public function admin_page_setup_menu()
    {
        add_submenu_page('tools.php', 'WP Tuxedo - Log', 'WP Tuxedo - System', 'manage_options', 'wp_tuxedo_logs', array($this, 'render'));
    }

    /**
     * Set a menu item in WP admin toolbar
     */
    public function admin_page_toolbar_action($wp_admin_bar)
    {
        $wp_admin_bar->add_node(
            array(
                'id'    => 'wp-tuxedo-run',
                'title' => __('WP Tuxedo Run Import'),
                'href'  => '/wp-admin/?wp_tuxedo_run_cron=1',
            )
        );
    }

    /**
     * Enqueue JS/CSS assets and pass AJAX config to JS.
     * Only loads on the WP Tuxedo system/log admin page.
     *
     * @param string $hook Current admin page hook suffix.
     */
    public function enqueue_log_assets(string $hook): void
    {
        // tools_page_{menu_slug} is the hook WordPress assigns for submenu pages under tools.php
        if ($hook !== 'tools_page_wp_tuxedo_logs') {
            return;
        }

        wp_enqueue_script(
            'wp-tuxedo-admin',
            plugin_dir_url(__FILE__) . 'js/plugin-name-admin.js',
            ['jquery'],
            WP_TUXEDO_VERSION,
            true
        );

        wp_enqueue_style(
            'wp-tuxedo-admin',
            plugin_dir_url(__FILE__) . 'css/plugin-name-admin.css',
            [],
            WP_TUXEDO_VERSION
        );

        wp_localize_script('wp-tuxedo-admin', 'wpTuxedoLog', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'action'  => 'wp_tuxedo_get_log',
            'nonce'   => wp_create_nonce('wp_tuxedo_get_log'),
        ]);
    }

    /**
     * WP AJAX handler — returns the most recent log file HTML for srcdoc injection.
     * Encoded with htmlspecialchars so it is safe to embed as an HTML attribute value.
     */
    public function ajax_get_log(): void
    {
        check_ajax_referer('wp_tuxedo_get_log', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
            return;
        }

        $directory = WP_TUXEDO_PLUGIN_DIR . 'admin/logs/';
        $files     = $this->get_log($directory);

        if (!$files || !isset($files[0])) {
            wp_send_json_error('No log file found');
            return;
        }

        $path = realpath($directory . $files[0]);

        // Prevent path traversal — resolved path must stay inside the logs directory
        if ($path === false || strpos($path, realpath($directory)) !== 0) {
            wp_send_json_error('Invalid file', 400);
            return;
        }

        $html = file_get_contents($path);

        // Prepend a style block to reset browser defaults that break Monolog's inline-styled output.
        // The log file is a raw fragment (no <html>/<head>) so we inject styles directly.
        $styles = <<<'CSS'
<meta charset="utf-8">
<style>
  * { box-sizing: border-box; }
  body { margin: 0; padding: 8px; font-family: sans-serif; font-size: 13px; background: #fff; }
  h1.monolog-output { margin: 0 0 1px 0; font-size: 13px; letter-spacing: .5px; }
  table.monolog-output { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
  table.monolog-output th { padding: 6px 8px; white-space: nowrap; }
  table.monolog-output td { padding: 0; }
  table.monolog-output td pre { margin: 0; padding: 6px 8px; font-size: 12px; word-break: break-all; white-space: pre-wrap; }
</style>
CSS;

        wp_send_json_success(['html' => $styles . $html]);
    }

    private function system_check()
    {
        // check if ACF is installed
        $this->system_checks_list['acf'] = class_exists('ACF');

        // check if post-type show_date is set
        $this->system_checks_list['post_type'] = post_type_exists('show_date');
    }

    /**
     * Get log files sorted by modification time, newest first.
     *
     * @param string $dir
     * @return array|false
     */
    private function get_log($dir)
    {
        $ignored = array('.', '..');
        $files   = array();

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
     */
    public function render()
    {
        $directory = WP_TUXEDO_PLUGIN_DIR . 'admin/logs/';
        $files     = $this->get_log($directory);

        $this->system_check();

        $icon_valid   = '<div alt="f319" class="dashicons dashicons-cloud-saved" style="color: green;"></div>';
        $icon_invalid = '<div alt="f319" class="dashicons dashicons-admin-plugins" style="color: red;"></div>';
        $has_logs     = $files && isset($files[0]);

        ?>
        <div class="wrap">
        <h1 class="wp-heading-block" style="margin-bottom: 1rem;">WP Tuxedo : system check</h1>

        <ul>
            <li><strong>Advanced Custom Field :</strong> <?= $this->system_checks_list['acf'] ? $icon_valid : $icon_invalid ?></li>
            <li><strong>Custom post type :</strong> <?= $this->system_checks_list['post_type'] ? $icon_valid : $icon_invalid ?></li>
        </ul>

        <h1 class="wp-heading-block" style="margin-bottom: 1rem;">Cron logs</h1>

        <div class="wp-tuxedo-log-wrap">
            <?php if ($has_logs) : ?>
            <button type="button" id="wp-tuxedo-load-log-btn" class="button button-secondary">
                <?php esc_html_e('Load logs', 'wp-tuxedo'); ?>
            </button>
            <div id="wp-tuxedo-log-frame-container" style="display:none;">
                <iframe id="wp-tuxedo-log-frame"></iframe>
            </div>
            <?php else : ?>
            <p><?php esc_html_e('No WP Tuxedo log file found.', 'wp-tuxedo'); ?></p>
            <?php endif; ?>
        </div>

        </div>
        <?php
    }
}
