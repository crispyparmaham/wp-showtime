<?php
defined('ABSPATH') || exit;

add_action('init', function () {
    if (!isset($_GET['showtime_ical']) || !isset($_GET['show_id'])) return;

    $post_id = absint($_GET['show_id']);
    if (!$post_id || get_post_type($post_id) !== 'shows') return;

    $date    = get_field('show_date', $post_id);      // Ymd
    $venue   = get_field('show_venue', $post_id);
    $city    = get_field('show_city', $post_id);
    $country = get_field('show_country', $post_id);
    $title   = get_the_title($post_id);
    $url     = get_field('show_ticket_url', $post_id);

    if (!$date) wp_die('Invalid show.');

    $dtstart  = date('Ymd', strtotime($date));
    $dtend    = date('Ymd', strtotime($date . ' +1 day'));
    $dtstamp  = gmdate('Ymd\THis\Z');
    $uid      = $post_id . '-' . $dtstart . '@' . parse_url(home_url(), PHP_URL_HOST);
    $location = trim($venue . ', ' . $city . ', ' . $country, ', ');
    $summary  = $title ?: 'Show';

    $ical  = "BEGIN:VCALENDAR\r\n";
    $ical .= "VERSION:2.0\r\n";
    $ical .= "PRODID:-//Showtime//EN\r\n";
    $ical .= "CALSCALE:GREGORIAN\r\n";
    $ical .= "BEGIN:VEVENT\r\n";
    $ical .= "UID:{$uid}\r\n";
    $ical .= "DTSTAMP:{$dtstamp}\r\n";
    $ical .= "DTSTART;VALUE=DATE:{$dtstart}\r\n";
    $ical .= "DTEND;VALUE=DATE:{$dtend}\r\n";
    $ical .= "SUMMARY:{$summary}\r\n";
    $ical .= "LOCATION:{$location}\r\n";
    if ($url) $ical .= "URL:{$url}\r\n";
    $ical .= "END:VEVENT\r\n";
    $ical .= "END:VCALENDAR\r\n";

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="show-' . $post_id . '.ics"');
    header('Cache-Control: no-cache, no-store');
    echo $ical;
    exit;
});