<?php
defined('ABSPATH') || exit;

// Register as submenu page (priority 10, after dashboard.php)
add_action('admin_menu', function (): void {
    add_submenu_page(
        'showtime-dashboard',
        'Showtime Settings',
        'Settings',
        'manage_options',
        'showtime-settings',
        'showtime_settings_page'
    );
}, 10);

add_action('admin_init', function (): void {
    // General
    register_setting('showtime_settings', 'showtime_limit', [
        'type'              => 'integer',
        'default'           => 5,
        'sanitize_callback' => 'absint',
    ]);

    // Bandsintown
    register_setting('showtime_settings', 'showtime_artist_name', [
        'type'              => 'string',
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    register_setting('showtime_settings', 'showtime_app_id', [
        'type'              => 'string',
        'default'           => '',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    register_setting('showtime_settings', 'showtime_auto_sync', [
        'type'              => 'integer',
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ]);
    register_setting('showtime_settings', 'showtime_import_past', [
        'type'              => 'integer',
        'default'           => 0,
        'sanitize_callback' => 'absint',
    ]);
});

function showtime_settings_page(): void {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="wrap">
        <h1>Showtime – Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('showtime_settings'); ?>

            <h2>General</h2>
            <table class="form-table">
                <tr>
                    <th><label for="showtime_limit">Shows visible by default</label></th>
                    <td>
                        <input type="number" id="showtime_limit" name="showtime_limit"
                            value="<?php echo esc_attr(get_option('showtime_limit', 5)); ?>"
                            min="1" max="50" style="width:80px;">
                        <p class="description">Number of shows visible before the "All Dates" button.</p>
                    </td>
                </tr>
            </table>

            <h2>Bandsintown</h2>
            <table class="form-table">
                <tr>
                    <th><label for="showtime_artist_name">Artist Name</label></th>
                    <td>
                        <input type="text" id="showtime_artist_name" name="showtime_artist_name"
                            value="<?php echo esc_attr(get_option('showtime_artist_name', '')); ?>"
                            class="regular-text">
                        <p class="description">Exact artist name as registered on Bandsintown.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="showtime_app_id">App ID</label></th>
                    <td>
                        <input type="text" id="showtime_app_id" name="showtime_app_id"
                            value="<?php echo esc_attr(get_option('showtime_app_id', '')); ?>"
                            class="regular-text">
                        <p class="description">Bandsintown API App ID.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="showtime_auto_sync">Auto-Sync</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="showtime_auto_sync" name="showtime_auto_sync" value="1"
                                <?php checked(1, get_option('showtime_auto_sync', 0)); ?>>
                            Enable daily automatic sync via WP-Cron
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label for="showtime_import_past">Import Past Shows</label></th>
                    <td>
                        <label>
                            <input type="checkbox" id="showtime_import_past" name="showtime_import_past" value="1"
                                <?php checked(1, get_option('showtime_import_past', 0)); ?>>
                            Also import past events from Bandsintown
                        </label>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
