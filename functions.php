<?php
/**
 * Plugin Name: Showtime
 * Plugin URI:  https://morethanads.de
 * Description: Tour date management. CPT Shows + ACF fields + Shortcode + iCal + Countdown + Admin columns.
 * Version:     1.4.0
 * Author:      more than ads GmbH & Co. KG
 * Text Domain: showtime
 */

defined('ABSPATH') || exit;

define('SHOWTIME_PATH', plugin_dir_path(__FILE__));
define('SHOWTIME_URL',  plugin_dir_url(__FILE__));
define('SHOWTIME_VERSION', '1.4.0');

require_once SHOWTIME_PATH . 'includes/cpt.php';
require_once SHOWTIME_PATH . 'includes/status.php';
require_once SHOWTIME_PATH . 'includes/shortcode.php';
require_once SHOWTIME_PATH . 'includes/settings.php';
require_once SHOWTIME_PATH . 'includes/design-system.php';
require_once SHOWTIME_PATH . 'includes/design-page.php';
require_once SHOWTIME_PATH . 'includes/admin.php';
require_once SHOWTIME_PATH . 'includes/ical.php';
require_once SHOWTIME_PATH . 'includes/bandsintown.php';
require_once SHOWTIME_PATH . 'includes/dashboard.php';

// Frontend assets
function showtime_frontend_assets(): void {
    wp_enqueue_style('showtime', SHOWTIME_URL . 'assets/css/main.css', [], SHOWTIME_VERSION);
    wp_enqueue_script('showtime', SHOWTIME_URL . 'assets/js/showtime.js', [], SHOWTIME_VERSION, true);
    wp_localize_script('showtime', 'Showtime', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('showtime_nonce'),
    ]);
}

add_action('wp_enqueue_scripts', 'showtime_frontend_assets');

// Oxygen Builder – Editor-iframe hat eigenen Head-Hook
add_action('ct_builder_head_inside', function (): void {
    echo '<link rel="stylesheet" href="' . esc_url(SHOWTIME_URL . 'assets/css/main.css?v=' . SHOWTIME_VERSION) . '" type="text/css">' . "\n";
    echo '<script src="' . esc_url(SHOWTIME_URL . 'assets/js/showtime.js?v=' . SHOWTIME_VERSION) . '" defer></script>' . "\n";
});

// Admin assets – nur auf Showtime-Screens laden
add_action('admin_enqueue_scripts', function (): void {
    $screen = get_current_screen();
    if (!$screen) return;

    $showtime_screens = [
        'toplevel_page_showtime-dashboard',
        'showtime_page_showtime-settings',
        'showtime_page_showtime-design',
        'shows',
        'edit-shows',
    ];

    if (!in_array($screen->id, $showtime_screens, true)) return;

    wp_enqueue_style(
        'showtime-admin',
        SHOWTIME_URL . 'assets/showtime-admin.css',
        [],
        SHOWTIME_VERSION
    );

    $deps = ['jquery'];
    if ($screen->id === 'showtime_page_showtime-design') {
        wp_enqueue_style('wp-color-picker');
        $deps[] = 'wp-color-picker';
    }

    wp_enqueue_script(
        'showtime-admin',
        SHOWTIME_URL . 'assets/showtime-admin.js',
        $deps,
        SHOWTIME_VERSION,
        true
    );
    wp_localize_script('showtime-admin', 'ShowtimeAdmin', [
        'ajaxUrl'      => admin_url('admin-ajax.php'),
        'nonce'        => wp_create_nonce('showtime_admin_nonce'),
        'designNonce'  => wp_create_nonce('showtime_design_nonce'),
        'presets'      => showtime_design_presets(),
    ]);
});

// ACF JSON auto-load
add_filter('acf/settings/load_json', function (array $paths): array {
    $paths[] = SHOWTIME_PATH . 'acf';
    return $paths;
});

// Activation / Deactivation
register_activation_hook(__FILE__, 'showtime_activate');
register_deactivation_hook(__FILE__, 'showtime_deactivate');

function showtime_activate(): void {
    if (get_option('showtime_auto_sync', 0) && !wp_next_scheduled('showtime_daily_sync')) {
        wp_schedule_event(time(), 'showtime_daily', 'showtime_daily_sync');
    }
}

function showtime_deactivate(): void {
    $timestamp = wp_next_scheduled('showtime_daily_sync');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'showtime_daily_sync');
    }
}
