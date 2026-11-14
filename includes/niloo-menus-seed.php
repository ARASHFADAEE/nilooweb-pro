<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Seed header/footer menus + socials to match niloofarvafaei.ir.
 */

define('NASIM_NILOO_MENUS_SEED_VERSION', '1');

/**
 * Featured course slugs for footer "دوره‌ها" column (live footer order).
 *
 * @return string[]
 */
function nasim_niloo_footer_course_slugs(): array
{
    return [
        'capsule-ultra',
        'ultra-web',
        'ultra-plus-webdesign',
        'ultra-dev',
        'ultra-business',
    ];
}

/**
 * @return array<int, array{title:string,url:string}>
 */
function nasim_niloo_footer_course_links(): array
{
    $links = [];
    foreach (nasim_niloo_footer_course_slugs() as $slug) {
        $posts = get_posts([
            'name'           => $slug,
            'post_type'      => 'nasim_course',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
        ]);
        if (!$posts) {
            continue;
        }
        $links[] = [
            'title' => get_the_title($posts[0]),
            'url'   => get_permalink($posts[0]),
        ];
    }

    return $links;
}

/**
 * Create / refresh WP menus for Niloo academy.
 *
 * @return array{primary:int,footer:int}
 */
function nasim_niloo_install_menus(bool $force = false): array
{
    if (!$force && get_option('nasim_niloo_menus_seed_version') === NASIM_NILOO_MENUS_SEED_VERSION) {
        $locs = get_theme_mod('nav_menu_locations', []);
        return [
            'primary' => (int) ($locs['primary'] ?? 0),
            'footer'  => (int) ($locs['footer'] ?? 0),
        ];
    }

    $courses = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/');
    $blog    = function_exists('nasim_blog_url') ? nasim_blog_url() : home_url('/blog/');
    $radio   = home_url('/%d8%b1%d8%a7%d8%af%db%8c%d9%88%d9%86%db%8c%d9%84/');
    if (function_exists('nasim_mod') && nasim_mod('radio_more_url')) {
        $radio = (string) nasim_mod('radio_more_url');
    }

    $primary_items = [
        ['title' => 'خانه', 'url' => home_url('/')],
        ['title' => 'دوره‌ها', 'url' => $courses],
        ['title' => 'مقالات', 'url' => $blog],
        ['title' => 'رادیو نیل', 'url' => $radio],
        ['title' => 'سفارش سایت', 'url' => 'https://weblabs.ir/web-design/'],
        ['title' => 'معرفی من', 'url' => home_url('/connect/')],
        ['title' => 'مشاوره', 'url' => home_url('/request/')],
    ];

    $footer_items = [
        ['title' => 'دوره‌ها', 'url' => $courses],
        ['title' => 'مقالات', 'url' => $blog],
        ['title' => 'رادیو نیل', 'url' => $radio],
        ['title' => 'سفارش سایت', 'url' => 'https://weblabs.ir/web-design/'],
        ['title' => 'معرفی من', 'url' => home_url('/connect/')],
        ['title' => 'مشاوره', 'url' => home_url('/request/')],
        ['title' => 'قوانین', 'url' => home_url('/terms/')],
        ['title' => 'حریم خصوصی', 'url' => home_url('/privacy/')],
    ];

    $primary_id = nasim_niloo_upsert_menu('منوی اصلی نیلو', $primary_items);
    $footer_id  = nasim_niloo_upsert_menu('منوی فوتر نیلو', $footer_items);

    $locations           = get_theme_mod('nav_menu_locations', []);
    $locations           = is_array($locations) ? $locations : [];
    $locations['primary'] = $primary_id;
    $locations['footer']  = $footer_id;
    set_theme_mod('nav_menu_locations', $locations);

    // Socials from live footer.
    $socials = [
        'social_instagram' => 'https://instagram.com/niloofarvafaei.ir',
        'social_twitter'   => 'https://twitter.com/NiIfar77',
        'social_linkedin'  => 'https://www.linkedin.com/in/niloofar-vafaei-55b840242/',
        'social_youtube'   => 'https://www.youtube.com/channel/UColubQELWRLE3miuKRr_Kzw',
        'social_github'    => 'https://github.com/NiIfr',
        'social_discord'   => 'https://discord.gg/nGe6Rv7Cu7',
        'footer_tagline'   => 'آموزش طراحی سایت و کسب درآمد به زبان ساده و کاربردی همراه با پروژه‌های واقعی و پشتیبانی تا ورود شما به بازار کار',
        'footer_copyright' => 'تمامی حقوق برای ©نیلوفر وفایی محفوظ می‌باشد.',
        'header_cta_text'  => 'شروع رایگان',
        'header_cta_url'   => $courses,
    ];
    foreach ($socials as $key => $value) {
        set_theme_mod($key, $value);
    }

    // Courses page → catalog template.
    $courses_page = get_page_by_path('courses');
    if ($courses_page) {
        update_post_meta($courses_page->ID, '_wp_page_template', 'page-courses.php');
        // Replace thin seed body — template owns the UI.
        if ((string) get_post_meta($courses_page->ID, '_nasim_niloo_page_seed', true) === '1') {
            wp_update_post([
                'ID'           => $courses_page->ID,
                'post_content' => '',
                'post_title'   => 'دوره‌ها',
            ]);
        }
    }

    update_option('nasim_niloo_menus_seed_version', NASIM_NILOO_MENUS_SEED_VERSION, false);
    flush_rewrite_rules(false);

    return ['primary' => $primary_id, 'footer' => $footer_id];
}

/**
 * @param array<int, array{title:string,url:string}> $items
 */
function nasim_niloo_upsert_menu(string $name, array $items): int
{
    $menu = wp_get_nav_menu_object($name);
    if (!$menu) {
        $menu_id = (int) wp_create_nav_menu($name);
    } else {
        $menu_id = (int) $menu->term_id;
        $existing = wp_get_nav_menu_items($menu_id) ?: [];
        foreach ($existing as $item) {
            wp_delete_post((int) $item->ID, true);
        }
    }

    $position = 0;
    foreach ($items as $item) {
        $position++;
        wp_update_nav_menu_item($menu_id, 0, [
            'menu-item-title'  => $item['title'],
            'menu-item-url'    => $item['url'],
            'menu-item-status' => 'publish',
            'menu-item-type'   => 'custom',
            'menu-item-position' => $position,
        ]);
    }

    return $menu_id;
}

function nasim_niloo_maybe_seed_menus(): void
{
    if (get_option('nasim_niloo_menus_seed_version') === NASIM_NILOO_MENUS_SEED_VERSION) {
        return;
    }
    nasim_niloo_install_menus(false);
}
add_action('after_switch_theme', static function () {
    nasim_niloo_install_menus(true);
}, 40);
add_action('init', 'nasim_niloo_maybe_seed_menus', 50);

/**
 * Soft redirects for Persian / legacy course URLs → /courses/.
 */
function nasim_niloo_courses_legacy_redirect(): void
{
    if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) {
        return;
    }

    $path = trim((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    $aliases = [
        'دوره-ها',
        'دوره',
        'دورهها',
        'آموزش',
    ];

    if (in_array(rawurldecode($path), $aliases, true) || in_array($path, $aliases, true)) {
        wp_safe_redirect(home_url('/courses/'), 301);
        exit;
    }
}
add_action('template_redirect', 'nasim_niloo_courses_legacy_redirect', 5);
