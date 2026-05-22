<?php
defined('ABSPATH') || exit;

/**
 * Shortcode: [showtime]
 * Attributes:
 *   limit="5"           – overrides settings
 *   show_past="false"   – show past shows toggle
 */
add_shortcode('showtime', 'showtime_render');

function showtime_render($atts = []): string {

    $atts = shortcode_atts([
        'limit'     => absint(get_option('showtime_limit', 5)),
        'show_past' => 'false',
    ], $atts);

    $limit     = absint($atts['limit']);
    $show_past = filter_var($atts['show_past'], FILTER_VALIDATE_BOOLEAN);
    $today     = date('Ymd');

    $meta_query = $show_past ? [] : [[
        'key'     => 'show_date',
        'value'   => $today,
        'compare' => '>=',
        'type'    => 'DATE',
    ]];

    $all_shows = new WP_Query([
        'post_type'      => 'shows',
        'posts_per_page' => -1,
        'meta_key'       => 'show_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => $meta_query,
    ]);

    if (!$all_shows->have_posts()) {
        return '<p class="showtime-no-shows">No upcoming shows.</p>';
    }

    $posts   = $all_shows->posts;
    $visible = array_slice($posts, 0, $limit);
    $hidden  = array_slice($posts, $limit);

    ob_start();
    ?>
    <div class="showtime-shows">

        <?php foreach ($visible as $post) : ?>
            <?php echo showtime_row($post, $today); ?>
        <?php endforeach; ?>

        <?php if (!empty($hidden)) : ?>
            <div class="showtime-hidden" style="display:none;">
                <?php foreach ($hidden as $post) : ?>
                    <?php echo showtime_row($post, $today); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="showtime-footer">
            <?php if (!empty($hidden)) : ?>
                <button class="showtime-btn-all showtime-btn-expand"
                        data-label-more="All Dates"
                        data-label-less="Show Less">
                    All Dates
                </button>
            <?php else : ?>
                <span class="showtime-btn-all showtime-btn-no-more">All Dates</span>
            <?php endif; ?>
        </div>

    </div>
    <?php

    wp_reset_postdata();
    return ob_get_clean();
}

function showtime_row($post, string $today): string {
    $post_id    = $post->ID;
    $date       = get_field('show_date', $post_id);
    $venue      = get_field('show_venue', $post_id);
    $city       = get_field('show_city', $post_id);
    $country    = get_field('show_country', $post_id);
    $label      = get_field('show_label', $post_id);
    $highlight  = get_field('show_highlight', $post_id);
    $presale    = get_field('show_presale_date', $post_id);
    $status     = showtime_get_status($post_id);

    $day   = $date ? date('d', strtotime($date)) : '';
    $month = $date ? strtoupper(date('M', strtotime($date))) : '';
    $year  = $date ? date('Y', strtotime($date)) : '';

    $classes = 'showtime-row';
    if ($date === $today || $highlight) $classes .= ' is-highlighted';
    if ($status === 'past') $classes .= ' is-past';

    // Presale countdown: show only if presale date is in the future
    $countdown_html = '';
    if ($presale && $presale > $today && $status === 'on_sale') {
        $countdown_html = sprintf(
            '<span class="showtime-presale" data-presale="%s">Presale in <span class="showtime-countdown"></span></span>',
            esc_attr($presale)
        );
    }

    // iCal download URL
    $ical_url = add_query_arg([
        'showtime_ical' => 1,
        'show_id'       => $post_id,
    ], home_url('/'));

    ob_start();
    ?>
    <div class="<?php echo esc_attr($classes); ?>">

        <div class="showtime-date">
            <span class="showtime-day"><?php echo esc_html($day); ?></span>
            <span class="showtime-month"><?php echo esc_html($month . ' ' . $year); ?></span>
        </div>

        <div class="showtime-info">
            <span class="showtime-venue"><?php echo esc_html($venue); ?></span>
            <span class="showtime-city"><?php echo esc_html($city . ($country ? ', ' . $country : '')); ?></span>
            <?php if ($label) : ?>
                <span class="showtime-label"><?php echo esc_html($label); ?></span>
            <?php endif; ?>
            <?php echo $countdown_html; ?>
        </div>

        <div class="showtime-action">
            <?php echo showtime_status_button($post_id); ?>
            <?php if ($status !== 'past') : ?>
                <a href="<?php echo esc_url($ical_url); ?>" class="showtime-ical" title="Add to Calendar">
                    + iCal
                </a>
            <?php endif; ?>
        </div>

    </div>
    <?php
    return ob_get_clean();
}