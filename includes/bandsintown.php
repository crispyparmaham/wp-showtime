<?php
defined('ABSPATH') || exit;

// Custom cron schedule
add_filter('cron_schedules', function (array $schedules): array {
    if (!isset($schedules['showtime_daily'])) {
        $schedules['showtime_daily'] = [
            'interval' => DAY_IN_SECONDS,
            'display'  => 'Once Daily (Showtime)',
        ];
    }
    return $schedules;
});

// Cron hook
add_action('showtime_daily_sync', function (): void {
    if (!get_option('showtime_auto_sync', 0)) return;
    $result = showtime_bandsintown_import();
    showtime_log_sync($result, 'cron');
});

// Auto-Sync toggle: Cron ein-/ausplanen wenn Setting gespeichert wird
add_action('update_option_showtime_auto_sync', function ($old, $new): void {
    if ($new) {
        if (!wp_next_scheduled('showtime_daily_sync')) {
            wp_schedule_event(time(), 'showtime_daily', 'showtime_daily_sync');
        }
    } else {
        $timestamp = wp_next_scheduled('showtime_daily_sync');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'showtime_daily_sync');
        }
    }
}, 10, 2);

// AJAX – nur für eingeloggte Admins
add_action('wp_ajax_showtime_manual_import', function (): void {
    check_ajax_referer('showtime_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized'], 403);
    }
    $result = showtime_bandsintown_import();
    showtime_log_sync($result, 'manual');
    wp_send_json_success($result);
});

function showtime_bandsintown_import(): array {
    $artist = sanitize_text_field(get_option('showtime_artist_name', ''));
    $app_id = sanitize_text_field(get_option('showtime_app_id', ''));
    $past   = (bool) get_option('showtime_import_past', 0);
    $result = ['imported' => 0, 'updated' => 0, 'errors' => []];

    if (!$artist || !$app_id) {
        $result['errors'][] = 'Artist name or App ID not configured.';
        return $result;
    }

    $url = sprintf(
        'https://rest.bandsintown.com/artists/%s/events?app_id=%s',
        rawurlencode($artist),
        rawurlencode($app_id)
    );

    $response = wp_remote_get($url, ['timeout' => 15]);

    if (is_wp_error($response)) {
        $result['errors'][] = $response->get_error_message();
        return $result;
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) {
        $result['errors'][] = 'API returned HTTP ' . $code;
        return $result;
    }

    $body   = wp_remote_retrieve_body($response);
    $events = json_decode($body, true);

    if (!is_array($events)) {
        $result['errors'][] = 'Invalid JSON response from API.';
        return $result;
    }

    $today = date('Ymd');

    foreach ($events as $event) {
        if (empty($event['id']) || empty($event['datetime'])) {
            continue;
        }

        $event_date = date('Ymd', strtotime($event['datetime']));

        if (!$past && $event_date < $today) {
            continue;
        }

        $status_map = [
            'onsale'    => 'on_sale',
            'soldout'   => 'sold_out',
            'cancelled' => 'cancelled',
        ];
        $status = $status_map[$event['status'] ?? 'onsale'] ?? 'on_sale';

        $ticket_url = '';
        if (!empty($event['offers']) && is_array($event['offers'])) {
            foreach ($event['offers'] as $offer) {
                if (!empty($offer['url'])) {
                    $ticket_url = $offer['url'];
                    break;
                }
            }
        }

        $venue = $event['venue'] ?? [];
        $title = !empty($event['title']) ? $event['title'] : ($venue['name'] ?? 'Show');

        $post_data = [
            'post_type'   => 'shows',
            'post_title'  => sanitize_text_field($title),
            'post_status' => 'publish',
        ];

        $existing = showtime_find_by_bandsintown_id((string) $event['id']);

        if ($existing) {
            $post_data['ID'] = $existing;
            $post_id         = wp_update_post($post_data, true);
            if (is_wp_error($post_id)) {
                $result['errors'][] = 'Update failed for event ' . $event['id'] . ': ' . $post_id->get_error_message();
                continue;
            }
            $result['updated']++;
        } else {
            $post_id = wp_insert_post($post_data, true);
            if (is_wp_error($post_id)) {
                $result['errors'][] = 'Insert failed for event ' . $event['id'] . ': ' . $post_id->get_error_message();
                continue;
            }
            update_post_meta($post_id, '_bandsintown_event_id', sanitize_text_field((string) $event['id']));
            $result['imported']++;
        }

        update_field('show_date',       $event_date,                                  $post_id);
        update_field('show_venue',      sanitize_text_field($venue['name'] ?? ''),    $post_id);
        update_field('show_city',       sanitize_text_field($venue['city'] ?? ''),    $post_id);
        update_field('show_country',    sanitize_text_field($venue['country'] ?? ''), $post_id);
        update_field('show_ticket_url', esc_url_raw($ticket_url),                     $post_id);
        update_field('show_status',     $status,                                      $post_id);
    }

    update_option('showtime_last_sync', time());

    return $result;
}

function showtime_find_by_bandsintown_id(string $event_id): int {
    global $wpdb;
    $post_id = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta}
         WHERE meta_key = '_bandsintown_event_id' AND meta_value = %s
         LIMIT 1",
        $event_id
    ));
    return (int) $post_id;
}

function showtime_log_sync(array $result, string $source): void {
    $log = get_option('showtime_sync_log', []);
    if (!is_array($log)) {
        $log = [];
    }

    array_unshift($log, [
        'timestamp' => time(),
        'imported'  => $result['imported'],
        'updated'   => $result['updated'],
        'errors'    => $result['errors'],
        'source'    => $source,
    ]);

    update_option('showtime_sync_log', array_slice($log, 0, 20));
}
