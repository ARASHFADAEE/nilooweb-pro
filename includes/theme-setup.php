<?php

if(!defined('ABSPATH')) exit;



/**
 * Register theme supports and menu locations
 */
function nasim_theme_setup() {

    load_theme_textdomain('nasim-fitness', THEME_DIR . '/languages');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', [
        'height'      => 80,
        'width'       => 240,
        'flex-height' => true,
        'flex-width'  => true,
    ]);
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('automatic-feed-links');
    add_theme_support('responsive-embeds');

    register_nav_menus([
        'primary' => __('منوی اصلی', 'nasim-fitness'),
        'footer'  => __('منوی فوتر', 'nasim-fitness'),
    ]);
}
add_action('after_setup_theme', 'nasim_theme_setup');
