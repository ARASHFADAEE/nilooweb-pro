<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Course categories taxonomy + Niloo term seed.
 */
function nasim_register_course_taxonomy(): void
{
    register_taxonomy('nasim_course_cat', 'nasim_course', [
        'labels' => [
            'name'          => __('دسته‌بندی دوره‌ها', 'niloo-web'),
            'singular_name' => __('دسته دوره', 'niloo-web'),
            'search_items'  => __('جستجوی دسته', 'niloo-web'),
            'all_items'     => __('همه دسته‌ها', 'niloo-web'),
            'edit_item'     => __('ویرایش دسته', 'niloo-web'),
            'update_item'   => __('بروزرسانی دسته', 'niloo-web'),
            'add_new_item'  => __('افزودن دسته', 'niloo-web'),
            'menu_name'     => __('دسته‌بندی‌ها', 'niloo-web'),
        ],
        'public'            => true,
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => ['slug' => 'course-category', 'with_front' => false],
    ]);
}
add_action('init', 'nasim_register_course_taxonomy', 11);

/**
 * Canonical Niloo categories.
 *
 * @return array<string, string> slug => name
 */
function nasim_course_canonical_categories(): array
{
    return [
        'web-design' => 'طراحی سایت',
        'graphics'   => 'گرافیک',
        'editing'    => 'تدوین و ادیت',
        'business'   => 'کسب‌وکار و درآمد',
        'tools'      => 'ابزارها',
    ];
}

/**
 * Ensure terms exist; returns slug => term_id.
 *
 * @return array<string, int>
 */
function nasim_ensure_course_categories(): array
{
    $map = [];
    foreach (nasim_course_canonical_categories() as $slug => $name) {
        $term = get_term_by('slug', $slug, 'nasim_course_cat');
        if (!$term) {
            $created = wp_insert_term($name, 'nasim_course_cat', ['slug' => $slug]);
            if (!is_wp_error($created)) {
                $map[$slug] = (int) $created['term_id'];
            }
            continue;
        }
        $map[$slug] = (int) $term->term_id;
    }

    return $map;
}

/**
 * Map seed/Woo category labels + slug heuristics → canonical slugs.
 *
 * @param array<int, string> $labels
 * @return string[]
 */
function nasim_map_course_category_slugs(array $labels, string $course_slug = ''): array
{
    $slugs = [];
    $blob  = mb_strtolower(implode(' ', $labels) . ' ' . $course_slug);

    if (preg_match('/طراحی|webdesign|html|css|ultra-web|ultra-plus|capsule|wordpress|وردپرس/u', $blob)) {
        $slugs[] = 'web-design';
    }
    if (preg_match('/گرافیک|photoshop|فتوشاپ|storymotion|موشن|افتر/u', $blob)) {
        $slugs[] = 'graphics';
    }
    if (preg_match('/ادیت|تدوین|premiere|پریمیر|vedit/u', $blob)) {
        $slugs[] = 'editing';
    }
    if (preg_match('/بیزنس|business|بازار|درآمد|فریلنس/u', $blob)) {
        $slugs[] = 'business';
    }
    if (preg_match('/دیسکورد|discord|ابزار|webinar|وبینار/u', $blob)) {
        $slugs[] = 'tools';
    }

    return array_values(array_unique($slugs ?: ['web-design']));
}

/**
 * Assign categories to a course from seed row or heuristics.
 *
 * @param array<string, mixed> $row
 */
function nasim_assign_course_categories(int $course_id, array $row = []): void
{
    $terms = nasim_ensure_course_categories();
    $labels = [];
    foreach ((array) ($row['categories'] ?? []) as $label) {
        $labels[] = (string) $label;
    }
    $slug  = (string) ($row['slug'] ?? get_post_field('post_name', $course_id));
    $mapped = nasim_map_course_category_slugs($labels, $slug);
    $ids    = [];
    foreach ($mapped as $cat_slug) {
        if (!empty($terms[$cat_slug])) {
            $ids[] = $terms[$cat_slug];
        }
    }
    if ($ids) {
        wp_set_object_terms($course_id, $ids, 'nasim_course_cat', false);
    }
}
