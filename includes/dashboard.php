<?php
defined('ABSPATH') || exit;

add_action('admin_menu', function (): void {
    add_menu_page(
        'Showtime',
        'Showtime',
        'manage_options',
        'showtime-dashboard',
        'showtime_dashboard_page',
        'dashicons-tickets-alt',
        30
    );

    add_submenu_page(
        'showtime-dashboard',
        'Showtime Dashboard',
        'Dashboard',
        'manage_options',
        'showtime-dashboard',
        'showtime_dashboard_page'
    );
}, 5);

function showtime_dashboard_page(): void {
    if (!current_user_can('manage_options')) return;

    $today = date('Ymd');

    $all   = wp_count_posts('shows');
    $total = (int) ($all->publish ?? 0);

    $upcoming_query = new WP_Query([
        'post_type'      => 'shows',
        'posts_per_page' => -1,
        'meta_key'       => 'show_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => [[
            'key'     => 'show_date',
            'value'   => $today,
            'compare' => '>=',
            'type'    => 'DATE',
        ]],
    ]);
    $upcoming_count = $upcoming_query->found_posts;
    $next_date      = '';
    $next_venue     = '';

    if ($upcoming_query->have_posts()) {
        $upcoming_query->the_post();
        $pid        = get_the_ID();
        $next_date  = get_field('show_date', $pid);
        $next_venue = get_field('show_venue', $pid);
        wp_reset_postdata();
    }

    $preview_query = new WP_Query([
        'post_type'      => 'shows',
        'posts_per_page' => 5,
        'meta_key'       => 'show_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => [[
            'key'     => 'show_date',
            'value'   => $today,
            'compare' => '>=',
            'type'    => 'DATE',
        ]],
    ]);

    $last_sync       = (int) get_option('showtime_last_sync', 0);
    $sync_label      = $last_sync ? human_time_diff($last_sync, time()) . ' ago' : 'Never';
    $sync_log        = (array) get_option('showtime_sync_log', []);
    $sync_log_recent = array_slice($sync_log, 0, 5);
    $default_limit   = absint(get_option('showtime_limit', 5));

    $status_colors = [
        'on_sale'   => ['bg' => '#fff4ef', 'text' => '#d4550a'],
        'sold_out'  => ['bg' => '#f5f0ee', 'text' => '#7a3a1e'],
        'cancelled' => ['bg' => '#fff0f0', 'text' => '#cc0000'],
        'postponed' => ['bg' => '#f5f5f5', 'text' => '#666'],
        'past'      => ['bg' => '#f0f0f1', 'text' => '#444'],
    ];
    ?>
    <div class="wrap st-dashboard">

        <div class="st-header">
            <div class="st-header-left">
                <span class="dashicons dashicons-tickets-alt st-logo-icon"></span>
                <div>
                    <h1>Showtime</h1>
                    <span class="st-version">v<?php echo esc_html(SHOWTIME_VERSION); ?></span>
                </div>
            </div>
            <div class="st-header-actions">
                <a href="<?php echo esc_url(admin_url('post-new.php?post_type=shows')); ?>" class="button button-primary st-btn-primary">
                    <span class="dashicons dashicons-plus-alt2"></span> Add Show
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=showtime-settings')); ?>" class="button">
                    <span class="dashicons dashicons-admin-settings"></span> Settings
                </a>
            </div>
        </div>

        <!-- Stats -->
        <div class="st-stats">
            <div class="st-stat">
                <div class="st-stat-icon" style="background:#fff4ef;color:#d4550a;">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="st-stat-body">
                    <span class="st-stat-value"><?php echo esc_html($upcoming_count); ?></span>
                    <span class="st-stat-label">Upcoming Shows</span>
                </div>
            </div>
            <div class="st-stat">
                <div class="st-stat-icon" style="background:#f0f4ff;color:#2271b1;">
                    <span class="dashicons dashicons-tickets-alt"></span>
                </div>
                <div class="st-stat-body">
                    <span class="st-stat-value"><?php echo esc_html($total); ?></span>
                    <span class="st-stat-label">Total Shows</span>
                </div>
            </div>
            <div class="st-stat">
                <div class="st-stat-icon" style="background:#f0fff4;color:#008a20;">
                    <span class="dashicons dashicons-location-alt"></span>
                </div>
                <div class="st-stat-body">
                    <?php if ($next_date) : ?>
                        <span class="st-stat-value st-stat-value--sm"><?php echo esc_html(date('d M Y', strtotime($next_date))); ?></span>
                        <span class="st-stat-label"><?php echo $next_venue ? 'at ' . esc_html($next_venue) : 'Next Show'; ?></span>
                    <?php else : ?>
                        <span class="st-stat-value">—</span>
                        <span class="st-stat-label">Next Show</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="st-stat">
                <div class="st-stat-icon" style="background:#fdf8f0;color:#b08000;">
                    <span class="dashicons dashicons-update"></span>
                </div>
                <div class="st-stat-body">
                    <span class="st-stat-value st-stat-value--sm st-last-sync-display"><?php echo esc_html($sync_label); ?></span>
                    <span class="st-stat-label">Last Sync</span>
                </div>
            </div>
        </div>

        <div class="st-grid">

            <!-- Upcoming Shows -->
            <div class="st-card st-card--wide">
                <div class="st-card-header">
                    <h2><span class="dashicons dashicons-list-view"></span> Upcoming Shows</h2>
                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=shows')); ?>" class="st-card-link">View all</a>
                </div>
                <div class="st-card-body st-card-body--flush">
                    <?php if ($preview_query->have_posts()) : ?>
                        <table class="st-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Line 1</th>
                                    <th>Line 2</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($preview_query->have_posts()) : $preview_query->the_post();
                                    $pid = get_the_ID();
                                    $d   = get_field('show_date', $pid);
                                    $st  = showtime_get_status($pid);
                                    $sc  = $status_colors[$st] ?? $status_colors['past'];
                                    $city    = get_field('show_city', $pid);
                                    $country = get_field('show_country', $pid);
                                ?>
                                    <tr>
                                        <td class="st-td-date"><?php echo $d ? esc_html(date('d M Y', strtotime($d))) : '—'; ?></td>
                                        <td class="st-td-venue"><?php echo esc_html(get_field('show_venue', $pid) ?: '—'); ?></td>
                                        <td class="st-td-city"><?php echo esc_html(trim($city . ($country ? ', ' . $country : '')) ?: '—'); ?></td>
                                        <td>
                                            <span class="st-badge" style="background:<?php echo esc_attr($sc['bg']); ?>;color:<?php echo esc_attr($sc['text']); ?>">
                                                <?php echo esc_html(str_replace('_', ' ', $st)); ?>
                                            </span>
                                        </td>
                                        <td class="st-td-action">
                                            <a href="<?php echo esc_url(get_edit_post_link($pid)); ?>" class="st-edit-link">
                                                <span class="dashicons dashicons-edit"></span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; wp_reset_postdata(); ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <div class="st-empty">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <p>No upcoming shows. <a href="<?php echo esc_url(admin_url('post-new.php?post_type=shows')); ?>">Add one</a></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right column -->
            <div class="st-sidebar">

                <!-- Bandsintown Sync -->
                <div class="st-card">
                    <div class="st-card-header">
                        <h2><span class="dashicons dashicons-update"></span> Bandsintown Sync</h2>
                    </div>
                    <div class="st-card-body">
                        <div class="st-sync-status">
                            <span class="st-sync-dot <?php echo $last_sync ? 'st-sync-dot--ok' : 'st-sync-dot--idle'; ?>"></span>
                            Last sync: <strong><?php echo esc_html($sync_label); ?></strong>
                        </div>

                        <button id="showtime-sync-btn" class="button button-primary st-btn-primary st-btn-full">
                            <span class="dashicons dashicons-update"></span> Sync Now
                        </button>
                        <span class="showtime-spinner spinner st-spinner"></span>
                        <div id="showtime-sync-result"></div>

                        <?php if (!empty($sync_log_recent)) : ?>
                            <div class="st-log">
                                <p class="st-log-title">Recent syncs</p>
                                <?php foreach ($sync_log_recent as $entry) : ?>
                                    <div class="st-log-row">
                                        <span class="st-log-time"><?php echo esc_html(date('d/m H:i', $entry['timestamp'])); ?></span>
                                        <span class="st-log-source"><?php echo esc_html($entry['source']); ?></span>
                                        <span class="st-log-numbers">
                                            +<?php echo esc_html($entry['imported']); ?>
                                            / <?php echo esc_html($entry['updated']); ?>&#x21bb;
                                        </span>
                                        <?php if (!empty($entry['errors'])) : ?>
                                            <span class="st-log-err" title="<?php echo esc_attr(implode(', ', $entry['errors'])); ?>">&#9888;</span>
                                        <?php else : ?>
                                            <span class="st-log-ok">&#10003;</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Shortcode Generator -->
                <div class="st-card">
                    <div class="st-card-header">
                        <h2><span class="dashicons dashicons-shortcode"></span> Shortcode</h2>
                    </div>
                    <div class="st-card-body">
                        <div class="st-sc-preview">
                            <code id="showtime-shortcode-output">[showtime limit="<?php echo esc_attr($default_limit); ?>"]</code>
                            <button id="showtime-copy-btn" class="st-copy-btn" data-copied="&#10003;">
                                <span class="dashicons dashicons-clipboard"></span>
                            </button>
                        </div>
                        <div class="st-sc-controls">
                            <label>
                                Limit
                                <input type="number" id="sc-limit" min="1" max="100" value="<?php echo esc_attr($default_limit); ?>">
                            </label>
                            <label class="st-sc-check">
                                <input type="checkbox" id="sc-past"> Show past
                            </label>
                        </div>
                        <table class="st-attr-table">
                            <tr><td><code>limit</code></td><td><?php echo esc_html($default_limit); ?></td><td>Visible shows</td></tr>
                            <tr><td><code>show_past</code></td><td>false</td><td>Show past shows</td></tr>
                        </table>
                    </div>
                </div>

            </div><!-- .st-sidebar -->

        </div><!-- .st-grid -->

    </div><!-- .st-dashboard -->
    <?php
}
