<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Deployment profile: آکادمی نیلوفر وفایی (niloofarvafaei.ir).
 *
 * - صفحات موجود و المنتور حفظ می‌شوند (بدون seed خودکار روی سایت‌های پر‌صفحه).
 * - فروشگاه پلاگین/لایسنس قالب خاموش است؛ فقط LMS دوره.
 * - آرشیو CPT دوره روی slug «courses» ثبت نمی‌شود تا برگهٔ /courses/ قفل نشود.
 */

if (!defined('NASIM_ENABLE_STORE')) {
    define('NASIM_ENABLE_STORE', false);
}

if (!defined('NASIM_INSTALL_SEED_CONTENT')) {
    define('NASIM_INSTALL_SEED_CONTENT', false);
}

if (!defined('NASIM_COURSE_HAS_ARCHIVE')) {
    define('NASIM_COURSE_HAS_ARCHIVE', false);
}

if (!defined('NASIM_COURSE_SINGLE_SLUG')) {
    define('NASIM_COURSE_SINGLE_SLUG', 'course');
}

if (!defined('NASIM_FORCE_THEME_HOMEPAGE')) {
    define('NASIM_FORCE_THEME_HOMEPAGE', true);
}

include_once THEME_DIR . '/includes/client-niloo-brand.php';

/**
 * Whether the theme product store (nasim_product) is active.
 */
function nasim_store_enabled(): bool
{
    if (defined('NASIM_ENABLE_STORE') && !NASIM_ENABLE_STORE) {
        return false;
    }

    return (bool) apply_filters('nasim_store_enabled', true);
}

/**
 * Skip demo pages, testimonials, and default plans on sites that already have content.
 */
function nasim_should_install_seed_content(): bool
{
    if (defined('NASIM_INSTALL_SEED_CONTENT') && !NASIM_INSTALL_SEED_CONTENT) {
        return false;
    }

    $published_pages = isset(wp_count_posts('page')->publish)
        ? (int) wp_count_posts('page')->publish
        : 0;

    if ($published_pages >= 5) {
        return false;
    }

    return (bool) apply_filters('nasim_should_install_seed_content', true);
}

/**
 * Public URL for the course catalog (Elementor page when CPT archive is off).
 */
function nasim_courses_listing_url(): string
{
  $archive = get_post_type_archive_link('nasim_course');

    if ($archive) {
        return $archive;
    }

    $page = get_page_by_path('courses');

    return $page ? get_permalink($page) : home_url('/courses/');
}

/**
 * Elementor builder detection (safe when Elementor is inactive).
 */
function nasim_is_built_with_elementor(int $post_id = 0): bool
{
    $post_id = $post_id ?: (int) get_queried_object_id();

    if (!$post_id) {
        return false;
    }

    if ('builder' === get_post_meta($post_id, '_elementor_edit_mode', true)) {
        return true;
    }

    if (class_exists('\Elementor\Plugin')) {
        $plugin = \Elementor\Plugin::$instance;
        if (isset($plugin->db) && method_exists($plugin->db, 'is_built_with_elementor')) {
            return (bool) $plugin->db->is_built_with_elementor($post_id);
        }
    }

    return (bool) apply_filters('nasim_is_built_with_elementor', false, $post_id);
}

/**
 * Use hard-coded front-page sections vs. the static front page content (Elementor).
 */
function nasim_use_theme_front_sections(): bool
{
    if (defined('NASIM_FORCE_THEME_HOMEPAGE') && NASIM_FORCE_THEME_HOMEPAGE) {
        return (bool) apply_filters('nasim_use_theme_front_sections', true);
    }

    $page_id = (int) get_option('page_on_front');

    if (!$page_id) {
        return (bool) apply_filters('nasim_use_theme_front_sections', true);
    }

    if (nasim_is_built_with_elementor($page_id)) {
        return false;
    }

    $content = (string) get_post_field('post_content', $page_id);
    if (strlen(wp_strip_all_tags($content)) > 80) {
        return false;
    }

    return (bool) apply_filters('nasim_use_theme_front_sections', 'theme' === nasim_content_source());
}

/**
 * Course CPT rewrite: keep legacy /courses/ page, singles under /course/{slug}/.
 *
 * @param array<string, mixed> $args
 * @return array<string, mixed>
 */
function nasim_client_filter_course_post_type(array $args): array
{
    if (defined('NASIM_COURSE_HAS_ARCHIVE') && false === NASIM_COURSE_HAS_ARCHIVE) {
        $args['has_archive'] = false;
    } elseif (defined('NASIM_COURSE_ARCHIVE_SLUG') && NASIM_COURSE_ARCHIVE_SLUG) {
        $args['has_archive'] = NASIM_COURSE_ARCHIVE_SLUG;
    }

    if (defined('NASIM_COURSE_SINGLE_SLUG') && NASIM_COURSE_SINGLE_SLUG) {
        if (!isset($args['rewrite']) || !is_array($args['rewrite'])) {
            $args['rewrite'] = [];
        }
        $args['rewrite']['slug'] = NASIM_COURSE_SINGLE_SLUG;
        $args['rewrite']['with_front'] = false;
    }

    return $args;
}
add_filter('nasim_course_post_type_registration', 'nasim_client_filter_course_post_type');

/**
 * SEO hub cards tuned for the academy (no plugin storefront).
 *
 * @param array<string, array{title:string,url:string,desc:string}> $hubs
 */
function nasim_client_seo_hubs(array $hubs): array
{
    if (nasim_store_enabled()) {
        return $hubs;
    }

    unset($hubs['plugins'], $hubs['custom']);

    $hubs['support'] = [
        'title' => 'مشاوره و سفارش طراحی سایت',
        'url'   => home_url('/request/'),
        'desc'  => 'برای پروژه واقعی و مسیر درآمدزایی از طراحی وب',
    ];
    $hubs['courses'] = [
        'title' => 'دوره‌های آموزشی',
        'url'   => nasim_courses_listing_url(),
        'desc'  => 'مسیر یادگیری طراحی سایت از مقدماتی تا پروژه‌محور',
    ];
    $hubs['blog'] = [
        'title' => 'مقالات و رادیو نیل',
        'url'   => home_url('/%d8%b1%d8%a7%d8%af%db%8c%d9%88%d9%86%db%8c%d9%84/'),
        'desc'  => 'آموزش‌ها، پادکست و نکات کاربردی',
    ];

    return $hubs;
}
add_filter('nasim_seo_hubs', 'nasim_client_seo_hubs');