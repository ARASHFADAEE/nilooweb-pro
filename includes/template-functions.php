<?php

if(!defined('ABSPATH')) exit;



/**
 * Built-in defaults from nasim_customizer_sections() (cached).
 *
 * @return array<string, mixed>
 */
function nasim_mod_defaults() {

    static $defaults = null;

    if (null === $defaults) {
        $defaults = [];
        foreach (nasim_customizer_sections() as $section) {
            foreach ($section['fields'] as $field_id => $field) {
                $defaults[$field_id] = $field['default'];
            }
        }
    }

    return $defaults;
}


/**
 * Source of front-page copy: theme defaults (SEO-ready) or values saved in Customizer.
 *
 * @return string 'theme'|'custom'
 */
function nasim_content_source() {

    $source = get_theme_mod('content_source', 'theme');

    return in_array($source, ['theme', 'custom'], true) ? $source : 'theme';
}


/**
 * Keys that always read from Customizer (even when content_source = theme).
 * Images, counts, and social URLs are site-specific assets — not marketing copy.
 *
 * @return string[]
 */
function nasim_mod_always_custom_keys() {

    return [
        'content_source',
        'hero_image',
        'about_image',
        'program_1_image',
        'program_2_image',
        'products_count',
        'articles_count',
        'testimonials_count',
        'social_instagram',
        'social_telegram',
    ];
}


/**
 * Get a front-page / theme string.
 *
 * When content_source = theme → use built-in SEO defaults for copy fields.
 * When content_source = custom → use Customizer values with default fallback.
 * Images / counts / social links always come from Customizer.
 */
function nasim_mod($key) {

    $defaults = nasim_mod_defaults();

    if ('content_source' === $key) {
        return nasim_content_source();
    }

    $use_custom = ('custom' === nasim_content_source())
        || in_array($key, nasim_mod_always_custom_keys(), true);

    if (!$use_custom) {
        return $defaults[$key] ?? '';
    }

    return get_theme_mod($key, $defaults[$key] ?? '');
}


/**
 * Resolve in-page anchors (e.g. "#about") to the front page when rendered on inner pages
 */
function nasim_url($url) {

    if (is_string($url) && 0 === strpos($url, '#') && !is_front_page()) {
        return home_url('/' . $url);
    }

    return $url;
}


/**
 * Convert latin digits to Persian digits
 */
function nasim_fa_digits($value) {

    return strtr((string) $value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
}


/**
 * Estimated reading time of a post in minutes
 */
function nasim_reading_time($post = null) {

    $content = wp_strip_all_tags(strip_shortcodes(get_post_field('post_content', $post)));

    // str_word_count() ignores Persian letters, so split on whitespace instead.
    $words = preg_split('/\s+/u', trim($content), -1, PREG_SPLIT_NO_EMPTY);

    return max(1, (int) ceil(count($words) / 200));
}


/**
 * Split a comma separated string (latin or Persian comma) into trimmed items
 */
function nasim_split_list($value) {

    return array_filter(array_map('trim', preg_split('/[,،]/u', (string) $value)));
}


/**
 * Alt text of a customizer image, read from the media library when the image is uploaded there
 */
function nasim_image_alt($url, $fallback = '') {

    $attachment_id = attachment_url_to_postid($url);
    $alt           = $attachment_id ? get_post_meta($attachment_id, '_wp_attachment_image_alt', true) : '';

    return $alt ?: $fallback;
}


/**
 * Section heading: eyebrow + title with highlighted part, read from "{prefix}_eyebrow|title|title_highlight"
 */
function nasim_section_title($prefix, $line_break = false) {
    ?>
    <div>
        <span class="eyebrow"><?php echo esc_html(nasim_mod($prefix . '_eyebrow')); ?></span>
        <h2><?php echo esc_html(nasim_mod($prefix . '_title')); ?><?php echo $line_break ? '<br>' : ' '; ?><span><?php echo esc_html(nasim_mod($prefix . '_title_highlight')); ?></span></h2>
    </div>
    <?php
}


/**
 * Theme-bundled Niloo logos (light header / dark footer).
 */
function nasim_theme_logo_url($variant = 'main') {

    $file = 'footer' === $variant || 'white' === $variant
        ? 'assets/images/logo-vafaei-main-w.webp'
        : 'assets/images/logo-vafaei-main.webp';

    $path = trailingslashit(get_template_directory()) . $file;

    return file_exists($path) ? get_template_directory_uri() . '/' . $file : '';
}


/**
 * Site brand: Customizer logo, else Niloo theme logo, else text mark.
 */
function nasim_brand($extra_class = '') {

    $name     = get_bloginfo('name') ?: 'نیلوفر وفایی';
    $home_url = is_front_page() ? '#home' : home_url('/');
    $is_footer = false !== strpos($extra_class, 'brand-footer');

    if (has_custom_logo() && !$is_footer) {
        echo get_custom_logo();
        return;
    }

    $logo = nasim_theme_logo_url($is_footer ? 'white' : 'main');
    if ($logo) {
        printf(
            '<a href="%s" class="brand brand-logo %s" aria-label="%s"><img src="%s" alt="%s" width="160" height="72" decoding="async"%s></a>',
            esc_url($home_url),
            esc_attr($extra_class),
            esc_attr($name),
            esc_url($logo),
            esc_attr($name),
            $is_footer ? '' : ' fetchpriority="high"'
        );
        return;
    }

    $parts     = preg_split('/\s+/u', trim($name), 2);
    $primary   = $parts[0] ?? $name;
    $secondary = $parts[1] ?? '';
    $mark      = mb_substr($primary, 0, 1);
    ?>
    <a href="<?php echo esc_url($home_url); ?>" class="brand <?php echo esc_attr($extra_class); ?>" aria-label="<?php echo esc_attr($name); ?>">
        <span class="brand-mark"><?php echo esc_html($mark); ?></span>
        <span class="brand-copy"><strong><?php echo esc_html($primary); ?></strong><?php if ($secondary) : ?><small><?php echo esc_html($secondary); ?></small><?php endif; ?></span>
    </a>
    <?php
}


/**
 * Links shown when no menu is assigned to a location
 */
function nasim_fallback_menu_items($location) {

    $articles_url = nasim_blog_url();
    $courses_url  = get_post_type_archive_link('nasim_course');
    $products_url = get_post_type_archive_link('nasim_product');

    $items = [
        'primary' => [
            ['title' => 'خانه', 'url' => '#home'],
            ['title' => 'درباره من', 'url' => '#about'],
            ['title' => 'دوره‌ها', 'url' => $courses_url],
            ['title' => 'افزونه‌ها', 'url' => $products_url],
            ['title' => 'مقالات', 'url' => $articles_url],
            ['title' => 'ارتباط با من', 'url' => '#contact'],
        ],
        'footer' => [
            ['title' => 'درباره من', 'url' => '#about'],
            ['title' => 'دوره‌ها', 'url' => $courses_url],
            ['title' => 'افزونه‌ها', 'url' => $products_url],
            ['title' => 'اشتراک', 'url' => nasim_subscription_url()],
            ['title' => 'مقالات', 'url' => $articles_url],
            ['title' => 'قوانین', 'url' => nasim_default_page_url('terms')],
        ],
    ];

    return array_filter($items[$location] ?? [], function ($item) {
        return '' !== (string) $item['url'];
    });
}


/**
 * Sticky mobile bottom bar: home, products, courses, account
 *
 * Fixed for the store + LMS system (not taken from Appearance → Menus).
 */
function nasim_mobile_bottom_nav_items() {

    $home_url     = is_front_page() ? '#home' : home_url('/');
    $products_url = nasim_store_enabled() ? (get_post_type_archive_link('nasim_product') ?: '') : '';
    $courses_url  = nasim_courses_listing_url();

    $items = [
        [
            'title'  => 'خانه',
            'url'    => $home_url,
            'icon'   => 'home',
            'active' => is_front_page(),
        ],
    ];

    if ($products_url) {
        $items[] = [
            'title'  => 'افزونه‌ها',
            'url'    => $products_url,
            'icon'   => 'products',
            'active' => is_singular('nasim_product') || is_post_type_archive('nasim_product') || is_tax('nasim_product_cat'),
        ];
    }

    $items[] = [
        'title'  => 'دوره‌ها',
        'url'    => $courses_url,
        'icon'   => 'courses',
        'active' => is_singular('nasim_course') || is_post_type_archive('nasim_course') || nasim_is_course_player(),
    ];

    $items[] = [
        'title'  => is_user_logged_in() ? 'پنل من' : 'ورود',
        'url'    => nasim_account_url(),
        'icon'   => 'user',
        'active' => nasim_is_subscription_page() || nasim_is_auth_page(),
    ];

    return array_values(array_filter($items, function ($item) {
        return '' !== (string) $item['url'];
    }));
}


/**
 * Render the sticky mobile bottom navigation
 */
function nasim_mobile_bottom_nav() {

    foreach (nasim_mobile_bottom_nav_items() as $item) {
        echo Nasim_Nav_Walker::render_link('mobile', $item['title'], nasim_url($item['url']), !empty($item['active']), $item['icon']); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in render_link().
    }
}


/**
 * User panel link: the account/subscription page, or login first for guests
 */
function nasim_account_url() {

    return is_user_logged_in() ? nasim_subscription_url() : nasim_auth_url('login', nasim_subscription_url());
}


/**
 * Minimal line icons (24px grid, stroke-based; colored via currentColor)
 *
 * Also usable from the admin menu editor: put the icon name (home, courses, products, user, articles) in the item description.
 */
function nasim_icon($name) {

    $paths = [
        'home'      => '<path d="M4 10.2 12 4l8 6.2"/><path d="M6 8.8V19a1 1 0 0 0 1 1h3.3v-5.2h3.4V20H17a1 1 0 0 0 1-1V8.8"/>',
        'courses'   => '<rect x="3.5" y="4.8" width="17" height="12" rx="2.6"/><path d="M10.4 8.6v4.4l3.8-2.2z"/><path d="M8.5 20h7"/>',
        'products'  => '<path d="M4.5 8.2 12 4l7.5 4.2v7.6L12 20l-7.5-4.2z"/><path d="M12 12v8M4.5 8.2 12 12l7.5-3.8"/>',
        'nutrition' => '<path d="M12 8c-1.5-1.1-4.6-1.3-6 1-1.6 2.6-.5 6.7 1.5 9.1 1.1 1.3 2.3 1.6 3.3 1.1a2.7 2.7 0 0 1 2.4 0c1 .5 2.2.2 3.3-1.1 2-2.4 3.1-6.5 1.5-9.1-1.4-2.3-4.5-2.1-6-1Z"/><path d="M12 8c0-1.9.9-3.3 2.6-4"/>',
        'user'      => '<circle cx="12" cy="8.3" r="3.6"/><path d="M5 19.6c1.1-3.2 3.8-5 7-5s5.9 1.8 7 5"/>',
        'articles'  => '<path d="M6 4h9l3 3v13H6z"/><path d="M9 11h6M9 14.5h6M9 18h3.5"/>',
        'eye'       => '<path d="M2.8 12S6.2 5.8 12 5.8 21.2 12 21.2 12 17.8 18.2 12 18.2 2.8 12 2.8 12Z"/><circle cx="12" cy="12" r="2.8"/>',
        'dot'       => '<circle cx="12" cy="12" r="3"/>',
    ];

    return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . ($paths[$name] ?? $paths['dot']) . '</svg>';
}


/**
 * Render a menu location as flat <a> links (the design styles direct anchors, not <ul> lists)
 */
function nasim_nav_menu($location) {

    if ('mobile' === $location) {
        nasim_mobile_bottom_nav();
        return;
    }

    if (has_nav_menu($location)) {
        wp_nav_menu([
            'theme_location' => $location,
            'container'      => false,
            'items_wrap'     => '%3$s',
            'depth'          => 1,
            'fallback_cb'    => false,
            'walker'         => new Nasim_Nav_Walker($location),
        ]);
        return;
    }

    $courses_url  = get_post_type_archive_link('nasim_course');
    $products_url = get_post_type_archive_link('nasim_product');
    $in_courses   = is_singular('nasim_course') || is_post_type_archive('nasim_course');
    $in_products  = is_singular('nasim_product') || is_post_type_archive('nasim_product') || is_tax('nasim_product_cat');
    $blog_url     = nasim_blog_url();
    $in_blog      = nasim_is_blog_context();

    foreach (nasim_fallback_menu_items($location) as $item) {
        $is_active = !empty($item['active'])
            || (is_front_page() && '#home' === $item['url'])
            || ($in_courses && $courses_url === $item['url'])
            || ($in_products && $products_url === $item['url'])
            || ($in_blog && $blog_url === $item['url']);
        echo Nasim_Nav_Walker::render_link($location, $item['title'], nasim_url($item['url']), $is_active, $item['icon'] ?? ''); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in render_link().
    }
}
