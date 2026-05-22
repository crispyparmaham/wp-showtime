<?php
defined('ABSPATH') || exit;

// Define columns
add_filter('manage_shows_posts_columns', function ($cols) {
    unset($cols['date']);
    return array_merge($cols, [
        'show_date'   => 'Date',
        'show_venue'  => 'Line 1',
        'show_city'   => 'Line 2',
        'show_status' => 'Status',
    ]);
});

// Populate columns
add_action('manage_shows_posts_custom_column', function ($col, $post_id) {
    switch ($col) {
        case 'show_date':
            $d = get_field('show_date', $post_id);
            echo $d ? esc_html(date('d/m/Y', strtotime($d))) : '—';
            break;
        case 'show_venue':
            echo esc_html(get_field('show_venue', $post_id) ?: '—');
            break;
        case 'show_city':
            $city    = get_field('show_city', $post_id);
            $country = get_field('show_country', $post_id);
            echo esc_html(trim($city . ($country ? ', ' . $country : ''), ', ') ?: '—');
            break;
        case 'show_status':
            $status = showtime_get_status($post_id);
            $colors = [
                'on_sale'   => '#d4550a',
                'sold_out'  => '#8a3506',
                'cancelled' => '#cc0000',
                'postponed' => '#888',
                'past'      => '#444',
            ];
            $color = $colors[$status] ?? '#888';
            printf(
                '<span style="color:%s;font-weight:600;text-transform:uppercase;font-size:11px;">%s</span>',
                esc_attr($color),
                esc_html(str_replace('_', ' ', $status))
            );
            break;
    }
}, 10, 2);

// Make columns sortable
add_filter('manage_edit-shows_sortable_columns', function ($cols) {
    $cols['show_date']  = 'show_date';
    $cols['show_venue'] = 'show_venue';
    return $cols;
});

add_action('pre_get_posts', function ($query) {
    if (!is_admin() || !$query->is_main_query()) return;
    if ($query->get('post_type') !== 'shows') return;

    $orderby = $query->get('orderby');
    if ($orderby === 'show_date') {
        $query->set('meta_key', 'show_date');
        $query->set('orderby', 'meta_value');
    }
    if ($orderby === 'show_venue') {
        $query->set('meta_key', 'show_venue');
        $query->set('orderby', 'meta_value');
    }
});

// ── Duplicate Row Action ──────────────────────────────────────────────────────

add_filter('post_row_actions', function (array $actions, WP_Post $post): array {
    if ($post->post_type !== 'shows') return $actions;
    if (!current_user_can('edit_posts')) return $actions;

    $url = wp_nonce_url(
        add_query_arg([
            'action'  => 'showtime_duplicate',
            'post_id' => $post->ID,
        ], admin_url('admin.php')),
        'showtime_duplicate_' . $post->ID
    );

    $actions['duplicate'] = '<a href="' . esc_url($url) . '">' . __('Duplicate') . '</a>';
    return $actions;
}, 10, 2);

add_action('admin_action_showtime_duplicate', function (): void {
    $post_id = absint($_GET['post_id'] ?? 0);
    if (!$post_id) wp_die('Invalid request.');
    if (!current_user_can('edit_posts')) wp_die('Permission denied.');
    check_admin_referer('showtime_duplicate_' . $post_id);

    $original = get_post($post_id);
    if (!$original || $original->post_type !== 'shows') wp_die('Show not found.');

    $new_id = wp_insert_post([
        'post_title'  => $original->post_title . ' (Copy)',
        'post_type'   => 'shows',
        'post_status' => 'draft',
        'post_author' => get_current_user_id(),
    ], true);

    if (is_wp_error($new_id)) wp_die($new_id->get_error_message());

    foreach (get_post_meta($post_id) as $key => $values) {
        foreach ($values as $value) {
            add_post_meta($new_id, $key, maybe_unserialize($value));
        }
    }

    wp_redirect(add_query_arg([
        'post_type' => 'shows',
        'duplicated' => 1,
    ], admin_url('edit.php')));
    exit;
});

add_action('admin_notices', function (): void {
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'edit-shows') return;
    if (empty($_GET['duplicated'])) return;
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Show duplicated and saved as draft.') . '</p></div>';
});