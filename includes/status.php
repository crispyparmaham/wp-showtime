<?php
defined('ABSPATH') || exit;

/**
 * Show status helper
 * ACF field: show_status (select)
 * Values: on_sale | sold_out | cancelled | postponed
 * Auto-status: if show_date < today → past
 */

function showtime_get_status(int $post_id): string {
    $date   = get_field('show_date', $post_id);
    $status = get_field('show_status', $post_id) ?: 'on_sale';
    $today  = date('Ymd');

    if ($date && $date < $today) {
        return 'past';
    }

    return $status;
}

/**
 * Returns the button HTML based on status
 */
function showtime_status_button(int $post_id): string {
    if (get_field('show_hide_button', $post_id)) {
        return '';
    }

    $status     = showtime_get_status($post_id);
    $ticket_url = get_field('show_ticket_url', $post_id);

    $default_labels = [
        'on_sale'   => 'Buy Tickets',
        'sold_out'  => 'Sold Out',
        'cancelled' => 'Cancelled',
        'postponed' => 'Postponed',
        'past'      => 'Past Show',
    ];

    $custom_label = trim((string) get_field('show_button_label', $post_id));
    $label = ($status === 'on_sale' && $custom_label !== '')
        ? $custom_label
        : ($default_labels[$status] ?? 'Buy Tickets');

    if ($status === 'on_sale' && $ticket_url) {
        return sprintf(
            '<a href="%s" target="_blank" rel="noopener" class="showtime-btn-tickets" data-status="%s">%s</a>',
            esc_url($ticket_url),
            esc_attr($status),
            esc_html($label)
        );
    }

    return sprintf(
        '<span class="showtime-btn-tickets" data-status="%s">%s</span>',
        esc_attr($status),
        esc_html($label)
    );
}