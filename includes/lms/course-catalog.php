<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Catalog filters + query for /courses/ archive experience.
 */

/**
 * @return array{access:string,cat:string,level:string,q:string,sort:string,paged:int}
 */
function nasim_course_catalog_filters(): array
{
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- public filter form.
    $cat   = sanitize_title(wp_unslash($_GET['cat'] ?? ''));
    $level = sanitize_text_field(wp_unslash($_GET['level'] ?? ''));
    $q     = sanitize_text_field(wp_unslash($_GET['q'] ?? ''));
    $sort  = sanitize_key(wp_unslash($_GET['sort'] ?? 'featured'));
    $paged = max(1, (int) ($_GET['pg'] ?? get_query_var('paged') ?: get_query_var('page') ?: 1));
    // phpcs:enable

    if (!in_array($sort, ['featured', 'newest', 'title', 'price'], true)) {
        $sort = 'featured';
    }
    if ($level && !in_array($level, ['مقدماتی', 'متوسط', 'پیشرفته'], true)) {
        $level = '';
    }

    return compact('cat', 'level', 'q', 'sort', 'paged');
}

/**
 * Build catalog URL with merged query args (empty removes key).
 *
 * @param array<string, mixed> $overrides
 */
function nasim_course_catalog_url(array $overrides = []): string
{
    $base    = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/');
    $current = nasim_course_catalog_filters();
    $merged  = array_merge($current, $overrides);

    $args = [];
    if (!empty($merged['cat'])) {
        $args['cat'] = $merged['cat'];
    }
    if (!empty($merged['level'])) {
        $args['level'] = $merged['level'];
    }
    if (!empty($merged['q'])) {
        $args['q'] = $merged['q'];
    }
    if (!empty($merged['sort']) && 'featured' !== $merged['sort']) {
        $args['sort'] = $merged['sort'];
    }
    if (!empty($merged['paged']) && (int) $merged['paged'] > 1) {
        $args['pg'] = (int) $merged['paged'];
    }

    return $args ? add_query_arg($args, $base) : $base;
}

/**
 * @return array{query:WP_Query,filters:array,stats:array<string,int>,categories:WP_Term[]}
 */
function nasim_course_catalog_query(): array
{
    $filters = nasim_course_catalog_filters();
    $per_page = 12;

    $args = [
        'post_type'      => 'nasim_course',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $filters['paged'],
    ];

    if ($filters['q']) {
        $args['s'] = $filters['q'];
    }

    if ($filters['cat']) {
        $args['tax_query'] = [[
            'taxonomy' => 'nasim_course_cat',
            'field'    => 'slug',
            'terms'    => $filters['cat'],
        ]];
    }

    if ($filters['level']) {
        $args['meta_query'][] = [
            'key'   => '_nasim_course_level',
            'value' => $filters['level'],
        ];
    }

    switch ($filters['sort']) {
        case 'newest':
            $args['orderby'] = 'date';
            $args['order']   = 'DESC';
            break;
        case 'title':
            $args['orderby'] = 'title';
            $args['order']   = 'ASC';
            break;
        case 'price':
            $args['meta_key'] = '_nasim_linked_plan_id';
            $args['orderby']  = 'meta_value_num';
            $args['order']    = 'ASC';
            break;
        default:
            $args['orderby'] = ['menu_order' => 'ASC', 'date' => 'DESC'];
            break;
    }

    $query = new WP_Query($args);

    $categories = get_terms([
        'taxonomy'   => 'nasim_course_cat',
        'hide_empty' => true,
    ]);
    if (is_wp_error($categories)) {
        $categories = [];
    }

    return [
        'query'      => $query,
        'filters'    => $filters,
        'stats'      => ['all' => (int) $query->found_posts],
        'categories' => $categories,
    ];
}

/**
 * Plan price for a purchase course, or 0.
 */
function nasim_course_catalog_price(int $course_id): int
{
    $plan_id = (int) get_post_meta($course_id, '_nasim_linked_plan_id', true);
    if ($plan_id) {
        return (int) get_post_meta($plan_id, '_nasim_plan_price', true);
    }

    return 0;
}

/**
 * One-time: assign categories from seed JSON to existing courses.
 */
function nasim_course_catalog_maybe_seed_terms(): void
{
    if (get_option('nasim_course_cat_seed_version') === '2') {
        return;
    }

    nasim_ensure_course_categories();

    $rows = function_exists('nasim_niloo_courses_seed_data') ? nasim_niloo_courses_seed_data() : [];
    $by_slug = [];
    foreach ($rows as $row) {
        if (!empty($row['slug'])) {
            $by_slug[(string) $row['slug']] = $row;
        }
    }

    $courses = get_posts([
        'post_type'      => 'nasim_course',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ]);

    foreach ($courses as $course) {
        $row = $by_slug[$course->post_name] ?? ['slug' => $course->post_name];
        nasim_assign_course_categories((int) $course->ID, $row);
    }

    update_option('nasim_course_cat_seed_version', '2', false);
}
add_action('init', 'nasim_course_catalog_maybe_seed_terms', 40);
