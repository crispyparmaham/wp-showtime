<?php
defined('ABSPATH') || exit;

add_action('init', function () {
    register_post_type('shows', [
        'labels' => [
            'name'               => 'Shows',
            'singular_name'      => 'Show',
            'add_new_item'       => 'Add New Show',
            'edit_item'          => 'Edit Show',
            'new_item'           => 'New Show',
            'view_item'          => 'View Show',
            'search_items'       => 'Search Shows',
            'not_found'          => 'No shows found',
            'not_found_in_trash' => 'No shows found in trash',
        ],
        'public'       => true,
        'show_in_menu' => 'showtime-dashboard',
        'menu_icon'    => 'dashicons-tickets-alt',
        'supports'     => ['title'],
        'has_archive'  => false,
        'rewrite'      => ['slug' => 'shows'],
        'show_in_rest' => false,
    ]);
});