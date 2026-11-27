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
        'social_bale',
        'social_twitter',
        'social_linkedin',
        'social_youtube',
        'social_github',
        'social_discord',
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

    // Per-render overrides (Elementor widget settings) always win over defaults and Customizer.
    if (!empty($GLOBALS['nasim_mod_overrides']) && array_key_exists($key, $GLOBALS['nasim_mod_overrides'])) {
        return $GLOBALS['nasim_mod_overrides'][$key];
    }

    $use_custom = ('custom' === nasim_content_source())
        || in_array($key, nasim_mod_always_custom_keys(), true);

    if (!$use_custom) {
        return $defaults[$key] ?? '';
    }

    $from_customizer = get_theme_mod($key, null);

    // Prefer explicitly saved Mino Option values for social / footer / home count.
    if (defined('NILOO_SETTINGS_PAGE') && (0 === strpos($key, 'social_') || in_array($key, ['footer_copyright', 'footer_tagline', 'courses_home_count'], true))) {
        $mino_raw = get_option(NILOO_SETTINGS_PAGE, []);
        if (is_array($mino_raw) && array_key_exists($key, $mino_raw) && '' !== $mino_raw[$key] && null !== $mino_raw[$key]) {
            return $mino_raw[$key];
        }
    }

    if (null !== $from_customizer && '' !== $from_customizer) {
        return $from_customizer;
    }

    return $defaults[$key] ?? '';
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

    $connect_url = home_url('/connect/');
    $contact_url = function_exists('nasim_seo_contact_url') ? nasim_seo_contact_url() : home_url('/contact/');
    $request_url = home_url('/request/');
    $courses_url = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : $courses_url;

    $home_url  = is_front_page() ? '#home' : home_url('/');
    $radio_url = function_exists('nasim_radio_url') ? nasim_radio_url() : home_url('/blog/');

    $items = [
        'primary' => [
            ['title' => 'خانه', 'url' => $home_url],
            ['title' => 'دوره‌ها', 'url' => $courses_url],
            ['title' => 'مقالات', 'url' => $articles_url],
            ['title' => 'رادیو نیل', 'url' => $radio_url],
            ['title' => 'سفارش سایت', 'url' => 'https://weblabs.ir/web-design/'],
            ['title' => 'معرفی من', 'url' => $connect_url],
            ['title' => 'مشاوره', 'url' => $request_url],
        ],
        'footer' => [
            ['title' => 'دوره‌ها', 'url' => $courses_url],
            ['title' => 'مقالات', 'url' => $articles_url],
            ['title' => 'رادیو نیل', 'url' => $radio_url],
            ['title' => 'سفارش سایت', 'url' => 'https://weblabs.ir/web-design/'],
            ['title' => 'معرفی من', 'url' => $connect_url],
            ['title' => 'مشاوره', 'url' => $request_url],
            ['title' => 'قوانین', 'url' => nasim_default_page_url('terms', home_url('/terms/'))],
            ['title' => 'حریم خصوصی', 'url' => nasim_default_page_url('privacy', home_url('/privacy/'))],
        ],
    ];

    if ($products_url && function_exists('nasim_store_enabled') && nasim_store_enabled()) {
        array_splice($items['primary'], 3, 0, [['title' => 'افزونه‌ها', 'url' => $products_url]]);
    }

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
        'active' => is_singular('nasim_course')
            || is_post_type_archive('nasim_course')
            || nasim_is_course_player()
            || is_page('courses')
            || is_page_template('page-courses.php'),
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
        // Hero / benefits: پروژه محور
        'spark'     => '<path d="M12 3.2v3.2M12 17.6v3.2M3.2 12h3.2M17.6 12h3.2M6.2 6.2l2.2 2.2M15.6 15.6l2.2 2.2M17.8 6.2l-2.2 2.2M8.4 15.6l-2.2 2.2"/><circle cx="12" cy="12" r="2.6"/>',
        // Hero / benefits: پشتیبانی
        'check'     => '<path d="M5.2 12.4 9.6 16.8 18.8 7.2"/>',
        // Benefits: آپدیت دائم
        'refresh'   => '<path d="M19.2 12a7.2 7.2 0 1 1-2.1-5.1"/><path d="M19.2 4.8v4.4h-4.4"/>',
        // Benefits: جامعه دانشجویان
        'users'     => '<circle cx="9" cy="8.2" r="2.8"/><circle cx="16.2" cy="9" r="2.2"/><path d="M3.8 18.6c.9-3 3-4.6 5.2-4.6s4.3 1.6 5.2 4.6"/><path d="M14.4 14.4c1.5.2 2.9 1.2 3.8 3.2"/>',
        // Benefits / about: پروژه
        'layers'    => '<path d="M12 4.2 3.8 8.8 12 13.4l8.2-4.6z"/><path d="M3.8 12.2 12 16.8l8.2-4.6"/><path d="M3.8 15.6 12 20.2l8.2-4.6"/>',
        'dot'       => '<circle cx="12" cy="12" r="3"/>',
        // Social brands (filled)
        'twitter'   => '<path fill="currentColor" stroke="none" d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.727-8.835L1.254 2.25H8.08l4.253 5.622L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z"/>',
        'linkedin'  => '<path fill="currentColor" stroke="none" d="M6.94 6.5A2.19 2.19 0 1 1 6.94 2.1a2.19 2.19 0 0 1 0 4.4zM4.94 21.9h4V9.1h-4v12.8zM13.07 9.1h3.83v1.75h.05c.53-1 1.84-2.05 3.79-2.05 4.05 0 4.8 2.67 4.8 6.14V21.9h-4v-5.94c0-1.42-.03-3.24-1.97-3.24-1.98 0-2.28 1.54-2.28 3.14V21.9h-4V9.1z"/>',
        'instagram' => '<path fill="currentColor" stroke="none" d="M12 7.2A4.8 4.8 0 1 0 12 16.8 4.8 4.8 0 0 0 12 7.2zm0 7.9a3.1 3.1 0 1 1 0-6.2 3.1 3.1 0 0 1 0 6.2z"/><circle fill="currentColor" stroke="none" cx="17.5" cy="6.6" r="1.15"/><path fill="currentColor" stroke="none" d="M16.5 2.1H7.5A5.4 5.4 0 0 0 2.1 7.5v9A5.4 5.4 0 0 0 7.5 21.9h9a5.4 5.4 0 0 0 5.4-5.4v-9A5.4 5.4 0 0 0 16.5 2.1zm3.7 14.4a3.7 3.7 0 0 1-3.7 3.7h-9a3.7 3.7 0 0 1-3.7-3.7v-9a3.7 3.7 0 0 1 3.7-3.7h9a3.7 3.7 0 0 1 3.7 3.7v9z"/>',
        'youtube'   => '<path fill="currentColor" stroke="none" fill-rule="evenodd" d="M21.58 7.2a2.2 2.2 0 0 0-1.55-1.55C18.48 5.3 12 5.3 12 5.3s-6.48 0-8.03.35A2.2 2.2 0 0 0 2.42 7.2 24.6 24.6 0 0 0 2 12a24.6 24.6 0 0 0 .42 4.8 2.2 2.2 0 0 0 1.55 1.55C5.52 18.7 12 18.7 12 18.7s6.48 0 8.03-.35a2.2 2.2 0 0 0 1.55-1.55A24.6 24.6 0 0 0 22 12a24.6 24.6 0 0 0-.42-4.8zM10 15.15V8.85l5.4 3.15L10 15.15z"/>',
        'github'    => '<path fill="currentColor" stroke="none" d="M12 2.1A9.9 9.9 0 0 0 8.8 21.4c.5.1.68-.22.68-.48v-1.7c-2.78.6-3.37-1.34-3.37-1.34-.46-1.16-1.12-1.47-1.12-1.47-.91-.63.07-.62.07-.62 1 .07 1.53 1.04 1.53 1.04.9 1.54 2.36 1.1 2.94.84.09-.65.35-1.1.64-1.35-2.22-.25-4.55-1.11-4.55-4.94 0-1.09.39-1.98 1.03-2.68-.1-.25-.45-1.27.1-2.64 0 0 .84-.27 2.75 1.02A9.6 9.6 0 0 1 12 6.8c.85 0 1.7.11 2.5.33 1.9-1.29 2.74-1.02 2.74-1.02.55 1.37.2 2.39.1 2.64.64.7 1.03 1.59 1.03 2.68 0 3.84-2.34 4.69-4.57 4.94.36.31.68.92.68 1.86v2.76c0 .26.18.59.69.49A9.9 9.9 0 0 0 12 2.1z"/>',
        'discord'   => '<path fill="currentColor" stroke="none" d="M19.27 5.33A16.5 16.5 0 0 0 15.2 4.2l-.2.37a14.9 14.9 0 0 1 3.55 1.4 14.2 14.2 0 0 0-12.9.03 13.7 13.7 0 0 1 3.4-1.36l-.15-.28A16.4 16.4 0 0 0 4.72 5.35C1.9 9.57 1.18 13.68 1.53 17.73A16.6 16.6 0 0 0 6.6 20.2l.7-1.1a10.7 10.7 0 0 1-1.7-.82l.4-.32a11.8 11.8 0 0 0 10 0l.4.32c-.55.33-1.12.6-1.71.83l.7 1.1a16.5 16.5 0 0 0 5.07-2.47c.48-4.7-.73-8.77-3.19-12.59zM8.7 15.05c-1.03 0-1.87-.95-1.87-2.11s.82-2.12 1.87-2.12 1.9.96 1.88 2.12-.83 2.11-1.88 2.11zm6.6 0c-1.03 0-1.87-.95-1.87-2.11s.82-2.12 1.87-2.12 1.9.96 1.88 2.12-.84 2.11-1.88 2.11z"/>',
        'telegram'  => '<path fill="currentColor" stroke="none" d="M21.5 4.4 2.9 11.5c-1.27.5-1.25 1.2-.23 1.52l4.77 1.49 1.84 5.64c.23.65.12.9.83.9.54 0 .78-.25 1.08-.54l2.6-2.52 5.4 4c1 .55 1.7.26 1.95-.92L22.9 5.7c.3-1.2-.46-1.75-1.4-1.3zM9.7 14.7l-.3 3.5 2.7-2.6 5.8-5.2c.26-.2.5-.3.3 0L9.7 14.7z"/>',
        'bale'      => '<path fill="currentColor" stroke="none" d="M12 2.2A9.8 9.8 0 0 0 2.2 12c0 2.1.66 4.05 1.8 5.65L2.9 21.1l3.6-1.05A9.75 9.75 0 0 0 12 21.8 9.8 9.8 0 0 0 21.8 12 9.8 9.8 0 0 0 12 2.2zm4.55 7.05-4.9 4.65c-.2.2-.45.3-.7.3h-.05c-.27 0-.52-.12-.7-.33l-1.85-2.15a.95.95 0 0 1 1.45-1.22l1.2 1.4 4.2-4a.95.95 0 1 1 1.35 1.35z"/>',
    ];

    $fill = in_array($name, ['twitter', 'linkedin', 'instagram', 'youtube', 'github', 'discord', 'telegram', 'bale'], true);
    $attrs = $fill
        ? 'viewBox="0 0 24 24" aria-hidden="true" focusable="false"'
        : 'viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"';

    return '<svg ' . $attrs . '>' . ($paths[$name] ?? $paths['dot']) . '</svg>';
}


/**
 * Icons for the 4 homepage benefit cards (aligned with hero floating notes).
 *
 * @return string[]
 */
function nasim_benefit_icons() {

    return ['spark', 'check', 'refresh', 'users'];
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
