<?php

if(!defined('ABSPATH')) exit;



/**
 * Register courses post type and the "learn" endpoint (/courses/{slug}/learn/{lesson-id}/)
 */
function nasim_register_course_post_type() {

    $args = [
        'labels' => [
            'name'               => __('دوره‌ها', 'niloo-web'),
            'singular_name'      => __('دوره', 'niloo-web'),
            'add_new'            => __('افزودن دوره', 'niloo-web'),
            'add_new_item'       => __('افزودن دوره جدید', 'niloo-web'),
            'edit_item'          => __('ویرایش دوره', 'niloo-web'),
            'all_items'          => __('همه دوره‌ها', 'niloo-web'),
            'view_item'          => __('مشاهده دوره', 'niloo-web'),
            'search_items'       => __('جستجوی دوره', 'niloo-web'),
            'not_found'          => __('دوره‌ای پیدا نشد.', 'niloo-web'),
            'featured_image'     => __('تصویر دوره', 'niloo-web'),
            'set_featured_image' => __('انتخاب تصویر دوره', 'niloo-web'),
        ],
        'public'        => true,
        'has_archive'   => 'courses',
        'rewrite'       => ['slug' => 'courses', 'with_front' => false, 'ep_mask' => EP_PERMALINK],
        'show_in_rest'  => true,
        'menu_icon'     => 'dashicons-welcome-learn-more',
        'menu_position' => 5,
        'supports'      => ['title', 'editor', 'excerpt', 'thumbnail', 'author'],
    ];

    $args = apply_filters('nasim_course_post_type_registration', $args);

    register_post_type('nasim_course', $args);

    add_rewrite_endpoint('learn', EP_PERMALINK);
}
add_action('init', 'nasim_register_course_post_type');


/**
 * Whether the current request is the course player
 */
function nasim_is_course_player() {

    return is_singular('nasim_course') && '' !== (string) get_query_var('learn');
}


/**
 * Lesson requested in the course player
 */
function nasim_current_lesson() {

    if (!nasim_is_course_player()) {
        return null;
    }

    return nasim_find_lesson(get_queried_object_id(), sanitize_key(get_query_var('learn')));
}


/**
 * Unknown lesson ids redirect back to the course page
 */
function nasim_course_player_redirect() {

    if (nasim_is_course_player() && !nasim_current_lesson()) {
        wp_safe_redirect(get_permalink(get_queried_object_id()));
        exit;
    }
}
add_action('template_redirect', 'nasim_course_player_redirect');


/**
 * Use the player template for the "learn" endpoint
 */
function nasim_course_player_template($template) {

    if (nasim_is_course_player()) {
        return locate_template('templates/course-player.php') ?: $template;
    }

    return $template;
}
add_filter('template_include', 'nasim_course_player_template');


/**
 * Lesson title in the document title of the player
 */
function nasim_course_player_title($parts) {

    $lesson = nasim_current_lesson();

    if ($lesson) {
        $parts['title'] = $lesson['title'] . ' | ' . get_the_title(get_queried_object_id());
    }

    return $parts;
}
add_filter('document_title_parts', 'nasim_course_player_title');
