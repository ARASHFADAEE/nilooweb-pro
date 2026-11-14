<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Technical SEO helpers for Arash Fadaee (WordPress support / plugins / custom plugins).
 *
 * Covers: meta fallbacks, JSON-LD, BreadcrumbList, sanitize preview URLs,
 * junk-post cleanup, contact canonical redirect, and internal-link hubs.
 */

/**
 * Service / conversion hubs used for internal linking and article CTAs.
 *
 * @return array<string, array{title:string,url:string,desc:string}>
 */
function nasim_seo_hubs() {

    $products = nasim_store_enabled() ? (get_post_type_archive_link('nasim_product') ?: home_url('/products/')) : '';
    $courses  = function_exists('nasim_courses_listing_url')
        ? nasim_courses_listing_url()
        : (get_post_type_archive_link('nasim_course') ?: home_url('/courses/'));
    $contact  = nasim_seo_contact_url();
    $about    = home_url('/about/');
    $blog     = nasim_blog_url();

    $hubs = [
        'support'  => [
            'title' => 'پشتیبانی ماهانه وردپرس',
            'url'   => $contact,
            'desc'  => 'نگهداری، رفع خطا و به‌روزرسانی امن سایت وردپرسی شما',
        ],
        'plugins'  => [
            'title' => 'افزونه‌ها و قالب‌های آماده',
            'url'   => $products,
            'desc'  => 'افزونه‌های تخصصی برای فروش، سرعت و اتوماسیون وردپرس',
        ],
        'custom'   => [
            'title' => 'توسعه افزونه اختصاصی',
            'url'   => $contact,
            'desc'  => 'ساخت پلاگین سفارشی متناسب با فرآیند کسب‌وکار شما',
        ],
        'courses'  => [
            'title' => 'دوره‌های آموزشی',
            'url'   => $courses,
            'desc'  => 'مسیر یادگیری طراحی سایت از پایه تا پروژه واقعی',
        ],
        'blog'     => [
            'title' => 'مقالات و آموزش‌ها',
            'url'   => $blog,
            'desc'  => 'مقالات کاربردی طراحی سایت، وردپرس و رادیو نیل',
        ],
        'about'    => [
            'title' => 'درباره نیلوفر وفایی',
            'url'   => function_exists('nasim_profile_bio_url') ? nasim_profile_bio_url() : $about,
            'desc'  => 'مدرس طراحی سایت و مسیر ورود به بازار کار',
        ],
    ];

    return apply_filters('nasim_seo_hubs', $hubs);
}


/**
 * Preferred contact URL (Latin slug when available).
 */
function nasim_seo_contact_url() {

    foreach (['contact', 'request', 'connect'] as $slug) {
        $page = get_page_by_path($slug);
        if ($page instanceof WP_Post && 'publish' === $page->post_status) {
            return get_permalink($page);
        }
    }

    $fallback = nasim_default_page_url('contact');
    return $fallback ?: home_url('/contact/');
}


/**
 * Strip Customizer preview query args from any URL.
 */
function nasim_seo_clean_url($url) {

    if (!is_string($url) || '' === $url) {
        return $url;
    }

    $parts = wp_parse_url($url);
    if (empty($parts['query'])) {
        return $url;
    }

    parse_str($parts['query'], $query);
    $dirty = [
        'customize_changeset_uuid',
        'customize_autosaved',
        'customize_messenger_channel',
        'customize_theme',
        'customize_preview_nonce',
    ];

    $changed = false;
    foreach ($dirty as $key) {
        if (isset($query[$key])) {
            unset($query[$key]);
            $changed = true;
        }
    }

    if (!$changed) {
        return $url;
    }

    $path  = ($parts['path'] ?? '/');
    $base  = (isset($parts['scheme'], $parts['host']) ? $parts['scheme'] . '://' . $parts['host'] : '') . $path;
    $clean = $query ? $base . '?' . http_build_query($query) : $base;

    if (!empty($parts['fragment'])) {
        $clean .= '#' . $parts['fragment'];
    }

    return $clean;
}


/**
 * Clean permalinks and menu/customizer URLs site-wide.
 */
function nasim_seo_filter_clean_url($url) {

    return nasim_seo_clean_url($url);
}
add_filter('post_link', 'nasim_seo_filter_clean_url', 99);
add_filter('page_link', 'nasim_seo_filter_clean_url', 99);
add_filter('post_type_link', 'nasim_seo_filter_clean_url', 99);
add_filter('term_link', 'nasim_seo_filter_clean_url', 99);
add_filter('nav_menu_link_attributes', function ($atts) {
    if (!empty($atts['href'])) {
        $atts['href'] = nasim_seo_clean_url($atts['href']);
    }
    return $atts;
}, 99);
add_filter('theme_mod_about_button_url', 'nasim_seo_filter_clean_url', 99);
add_filter('theme_mod_cta_button_url', 'nasim_seo_filter_clean_url', 99);
add_filter('theme_mod_hero_primary_url', 'nasim_seo_filter_clean_url', 99);
add_filter('theme_mod_hero_secondary_url', 'nasim_seo_filter_clean_url', 99);


/**
 * Known junk / thin transliterated slugs that should leave the index.
 *
 * @return string[]
 */
function nasim_seo_junk_slugs() {

    return [
        'chgvnh-dr-cursor-skill-tar-f-n-m-omxpltsdqhnr',
        'chgvnh-cursor-ra-bh-figma-mtsl-n-m-ut2sickszfwt',
        'nhvh-astfadh-az-ai-sdk-dr-laravel-fcnkkblixdzx',
    ];
}


/**
 * One-time content & theme-mod migration aligned with WordPress support positioning.
 */
function nasim_seo_run_migrations() {

    if (get_option('nasim_seo_migrate_v5') === '1') {
        return;
    }

    // Niloo client: Customizer defaults from client-niloo-brand — skip legacy AWC support copy migration.
    if (defined('NASIM_FORCE_THEME_HOMEPAGE') && NASIM_FORCE_THEME_HOMEPAGE
        && function_exists('nasim_store_enabled') && !nasim_store_enabled()) {
        set_theme_mod('content_source', 'theme');
        update_option('nasim_seo_migrate_v5', '1');

        return;
    }

    // Prefer SEO-ready theme defaults for the homepage unless the owner opts into custom copy.
    set_theme_mod('content_source', 'theme');

    $force_mods = [
        'footer_tagline'         => 'آموزش طراحی سایت و کسب درآمد به زبان ساده و کاربردی',
        'footer_copyright'       => '© تمامی حقوق برای نیلوفر وفایی محفوظ می‌باشد.',
        'cta_title'              => 'برای رشد در مسیر طراحی سایت؛ همراه ما باشید',
        'cta_text'               => 'جدیدترین آموزش‌ها، مقالات و دوره‌ها را با ما دنبال کنید.',
        'cta_button_text'        => '',
        'cta_button_url'         => '',
        'articles_eyebrow'       => 'مقالات ما',
        'announcement_link_text' => 'عضویت در کانال بله',
    ];

    $fitness_markers = ['سالم‌تر', 'نیلو وب', 'تناسب', 'تمرین اصولی', 'تغذیه متعادل', 'قوی‌تر، سالم', 'مجله سلامتی', 'درباره نیلو'];

    foreach ($force_mods as $key => $value) {
        $current = (string) get_theme_mod($key, '');
        $dirty   = false !== strpos($current, 'customize_changeset');
        foreach ($fitness_markers as $marker) {
            if (false !== strpos($current, $marker)) {
                $dirty = true;
                break;
            }
        }
        if ('' === $current || $dirty || in_array($key, ['footer_tagline', 'footer_copyright', 'cta_title', 'cta_text', 'cta_button_text', 'cta_button_url'], true)) {
            set_theme_mod($key, nasim_seo_clean_url($value));
        }
    }

    // Fix known typos / weak hero copy when still present.
    $hero_desc = (string) get_theme_mod('hero_description', '');
    if ('' === $hero_desc || preg_match('/کاربرذی|مبتونی|فروشت|سالم‌تر|تناسب اندام/u', $hero_desc)) {
        set_theme_mod(
            'hero_description',
            'پشتیبانی ماهانه سایت وردپرسی، توسعه افزونه اختصاصی و فروش افزونه‌های آماده برای رشد پایدار فروش آنلاین شما.'
        );
    }

    $benefit_defaults = [
        1 => ['پشتیبانی پایدار', 'نگهداری ماهانه سایت وردپرسی'],
        2 => ['افزونه اختصاصی', 'ساخت پلاگین متناسب با کسب‌وکار'],
        3 => ['همراهی مستمر', 'برای ادامه دادن و پیشرفت'],
        4 => ['ابزارهای آماده', 'افزونه‌هایی برای رشد فروش'],
    ];
    foreach ($benefit_defaults as $i => $pair) {
        $title = (string) get_theme_mod("benefit_{$i}_title", '');
        if ('' === $title || preg_match('/تمرین|تغذیه|سالم|فیتنس/u', $title)) {
            set_theme_mod("benefit_{$i}_title", $pair[0]);
            set_theme_mod("benefit_{$i}_text", $pair[1]);
        }
    }

    // Profile-driven About section on the homepage.
    $profile = function_exists('nasim_profile') ? nasim_profile() : null;
    $bio_url = function_exists('nasim_profile_bio_url') ? nasim_profile_bio_url() : home_url('/connect/');

    if ($profile) {
        set_theme_mod('about_eyebrow', 'چرا این مسیر؟');
        set_theme_mod('about_title', 'من');
        set_theme_mod('about_title_highlight', ($profile['name'] ?? 'نیلوفر وفایی') . ' هستم');
        set_theme_mod('about_text', $profile['about_short']);
        set_theme_mod('about_sticker', 'آموزش · طراحی سایت');
        set_theme_mod('about_stat_1_value', '+۱۰۰۰');
        set_theme_mod('about_stat_1_label', 'دانشجویان');
        set_theme_mod('about_stat_2_value', '۱۰۰٪');
        set_theme_mod('about_stat_2_label', 'پروژه‌محور');
        set_theme_mod('about_stat_3_value', '۲۴/۷');
        set_theme_mod('about_stat_3_label', 'پشتیبانی');
        set_theme_mod('about_button_text', $profile['bio_label'] ?? 'درباره من');
        set_theme_mod('about_button_url', $bio_url);
        if (!empty($profile['telegram'])) {
            set_theme_mod('social_telegram', $profile['telegram']);
        }
        if (!empty($profile['instagram'])) {
            set_theme_mod('social_instagram', $profile['instagram']);
        }
    }

    // Always sanitize customize preview leftovers on the about button.
    $about_url = (string) get_theme_mod('about_button_url', '');
    if (false !== strpos($about_url, 'customize_changeset') || '' === $about_url) {
        set_theme_mod('about_button_url', home_url('/about/'));
    } else {
        set_theme_mod('about_button_url', nasim_seo_clean_url($about_url));
    }

    // Refresh About & Contact pages with the canonical profile (one-time on v4).
    foreach (['about' => 'nasim_profile_about_page_blocks', 'contact' => 'nasim_profile_contact_page_blocks'] as $key => $builder) {
        if (!function_exists($builder)) {
            continue;
        }
        $page_id = function_exists('nasim_default_page_id') ? nasim_default_page_id($key) : 0;
        if (!$page_id) {
            $page = get_page_by_path($key === 'about' ? 'about' : 'contact');
            $page_id = $page instanceof WP_Post ? (int) $page->ID : 0;
        }
        if (!$page_id) {
            continue;
        }
        wp_update_post([
            'ID'           => $page_id,
            'post_content' => implode("\n\n", $builder()),
        ]);
    }

    // Draft known junk posts and noindex them.
    foreach (nasim_seo_junk_slugs() as $slug) {
        $posts = get_posts([
            'name'           => $slug,
            'post_type'      => 'post',
            'post_status'    => ['publish', 'private'],
            'posts_per_page' => 1,
        ]);
        if (!$posts) {
            continue;
        }
        wp_update_post([
            'ID'          => $posts[0]->ID,
            'post_status' => 'draft',
        ]);
        update_post_meta($posts[0]->ID, 'rank_math_robots', ['noindex', 'nofollow']);
    }

    // Redirect duplicate Persian contact page to /contact/ when both exist.
    $encoded = get_page_by_path(rawurldecode('%d8%aa%d9%85%d8%a7%d8%b3-%d8%a8%d8%a7-%d9%85%d9%86'));
    if (!$encoded) {
        $encoded = get_page_by_path('تماس-با-من');
    }
    $contact = get_page_by_path('contact');
    if ($encoded instanceof WP_Post && $contact instanceof WP_Post && (int) $encoded->ID !== (int) $contact->ID) {
        update_post_meta($encoded->ID, 'rank_math_robots', ['noindex', 'nofollow']);
        update_option('nasim_seo_contact_redirect_from', (int) $encoded->ID);
        update_option('nasim_seo_contact_redirect_to', (int) $contact->ID);
    }

    // Author E-E-A-T bio (replace fitness leftover on any matching author).
    $author_bio = $profile['author_bio'] ?? 'توسعه‌دهنده وب با تمرکز روی وردپرس، لاراول و اتوماسیون.';
    foreach ([get_user_by('slug', 'arsiwkfhd12'), get_user_by('login', 'admin')] as $user) {
        if (!$user instanceof WP_User) {
            continue;
        }
        $desc = (string) $user->description;
        if ('' === trim($desc) || false !== strpos($desc, 'سالم') || false !== strpos($desc, 'تناسب')) {
            wp_update_user([
                'ID'          => $user->ID,
                'description' => $author_bio,
            ]);
        }
    }

    // Fix product title typo "انشار" → "انتشار" when present.
    $typo_products = get_posts([
        'post_type'      => 'nasim_product',
        'post_status'    => 'publish',
        'posts_per_page' => 20,
        's'              => 'انشار',
    ]);
    foreach ($typo_products as $product) {
        if (false === strpos($product->post_title, 'انشار')) {
            continue;
        }
        wp_update_post([
            'ID'         => $product->ID,
            'post_title' => str_replace('انشار', 'انتشار', $product->post_title),
        ]);
    }

    update_option('nasim_seo_migrate_v5', '1');
    delete_option('nasim_seo_migrate_v4');
    delete_option('nasim_seo_migrate_v3');
    delete_option('nasim_seo_migrate_v2');
}
add_action('init', 'nasim_seo_run_migrations', 20);


/**
 * 301 duplicate contact page → Latin /contact/
 */
function nasim_seo_contact_redirect() {

    $from = (int) get_option('nasim_seo_contact_redirect_from');
    $to   = (int) get_option('nasim_seo_contact_redirect_to');

    if (!$from || !$to || !is_page($from)) {
        return;
    }

    wp_safe_redirect(get_permalink($to), 301);
    exit;
}
add_action('template_redirect', 'nasim_seo_contact_redirect', 5);


/**
 * Meta description fallback when no SEO plugin filled the field.
 */
function nasim_seo_meta_description() {

    // Rank Math / Yoast: prefer their pipeline (see nasim_seo_rank_math_description).
    if (defined('RANK_MATH_VERSION') || defined('WPSEO_VERSION')) {
        return;
    }

    $description = '';

    if (is_front_page()) {
        $description = function_exists('nasim_niloo_default_description')
            ? nasim_niloo_default_description()
            : 'آموزش طراحی سایت و کسب درآمد به زبان ساده — آکادمی نیلوفر وفایی.';
    } elseif (is_singular('post')) {
        $description = has_excerpt() ? get_the_excerpt() : wp_trim_words(wp_strip_all_tags(get_post_field('post_content')), 28, '…');
    } elseif (is_post_type_archive('nasim_product')) {
        $description = 'فروشگاه افزونه‌ها و ابزارهای وردپرس؛ راهکارهای آماده برای فروش، سرعت و اتوماسیون.';
    } elseif (is_post_type_archive('nasim_course') || (function_exists('nasim_courses_listing_url') && is_page())) {
        $description = 'دوره‌های آموزش طراحی سایت از پایه تا پروژه واقعی — آکادمی نیلوفر وفایی.';
    } elseif (is_home() || nasim_is_blog_route()) {
        $description = 'مقالات آموزش طراحی سایت، وردپرس و رادیو نیل — آکادمی نیلوفر وفایی.';
    }

    $description = trim(wp_strip_all_tags((string) $description));
    if ('' === $description) {
        return;
    }

    printf('<meta name="description" content="%s">' . "\n", esc_attr(mb_substr($description, 0, 160)));
}
add_action('wp_head', 'nasim_seo_meta_description', 2);


/**
 * Force a description into Rank Math when the front page field is empty.
 */
function nasim_seo_rank_math_description($description) {

    if (is_string($description) && '' !== trim(wp_strip_all_tags($description))) {
        return $description;
    }

    if (is_front_page()) {
        return function_exists('nasim_niloo_default_description')
            ? nasim_niloo_default_description()
            : 'آموزش طراحی سایت و کسب درآمد به زبان ساده — آکادمی نیلوفر وفایی.';
    }

    if (is_post_type_archive('nasim_product')) {
        return 'فروشگاه افزونه‌ها و ابزارهای وردپرس؛ راهکارهای آماده برای فروش، سرعت و اتوماسیون.';
    }

    if (is_post_type_archive('nasim_course')) {
        return 'دوره‌ها و آموزش‌های عملی وردپرس، بهینه‌سازی سرعت و توسعه.';
    }

    if (is_home() || (function_exists('nasim_is_blog_route') && nasim_is_blog_route())) {
        return 'مقالات آموزشی وردپرس: سرعت، پشتیبانی، افزونه و رشد کسب‌وکار آنلاین.';
    }

    return $description;
}
add_filter('rank_math/frontend/description', 'nasim_seo_rank_math_description');

/**
 * Open Graph / Twitter description fallback via Rank Math.
 */
function nasim_seo_rank_math_og_desc($description) {

    return nasim_seo_rank_math_description($description);
}
add_filter('rank_math/opengraph/facebook/description', 'nasim_seo_rank_math_og_desc');
add_filter('rank_math/opengraph/twitter/description', 'nasim_seo_rank_math_og_desc');


/**
 * Soften wrong Article schema on the static front page.
 *
 * @param array $data Rank Math graph.
 * @return array
 */
function nasim_seo_rank_math_json_ld($data) {

    if (!is_front_page() || empty($data['@graph']) || !is_array($data['@graph'])) {
        return $data;
    }

    $cleaned = [];
    foreach ($data['@graph'] as $node) {
        $type = $node['@type'] ?? '';
        if ('Article' === $type || 'BlogPosting' === $type || (is_array($type) && array_intersect($type, ['Article', 'BlogPosting']))) {
            continue;
        }
        $cleaned[] = $node;
    }
    $data['@graph'] = $cleaned;

    return $data;
}
add_filter('rank_math/json_ld', 'nasim_seo_rank_math_json_ld', 99, 1);


/**
 * Theme-owned JSON-LD: WebSite + Organization/Person + BreadcrumbList + Article.
 */
function nasim_seo_json_ld() {

    $graph = [];
    $home  = home_url('/');
    $name  = get_bloginfo('name') ?: 'نیلوفر وفایی';
    $logo  = '';

    if (has_custom_logo()) {
        $logo_id = (int) get_theme_mod('custom_logo');
        $logo    = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '';
    }

    $person = [
        '@type' => ['Person', 'Organization'],
        '@id'   => $home . '#person',
        'name'  => $name,
        'url'   => $home,
        'description' => function_exists('nasim_niloo_default_description')
            ? nasim_niloo_default_description()
            : 'آموزش طراحی سایت و کسب درآمد — آکادمی نیلوفر وفایی.',
    ];
    if ($logo) {
        $person['logo'] = [
            '@type' => 'ImageObject',
            'url'   => $logo,
        ];
        $person['image'] = $logo;
    }
    $graph[] = $person;

    $graph[] = [
        '@type'           => 'WebSite',
        '@id'             => $home . '#website',
        'url'             => $home,
        'name'            => $name,
        'inLanguage'      => 'fa-IR',
        'publisher'       => ['@id' => $home . '#person'],
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => $home . '?s={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ];

    // Service offers for commercial intent.
    if (is_front_page()) {
        $service_keys = nasim_store_enabled() ? ['support', 'plugins', 'custom'] : ['support', 'courses'];
        foreach ($service_keys as $key) {
            if (!isset(nasim_seo_hubs()[$key])) {
                continue;
            }
            $hub = nasim_seo_hubs()[$key];
            $graph[] = [
                '@type'       => 'Service',
                'name'        => $hub['title'],
                'description' => $hub['desc'],
                'url'         => $hub['url'],
                'provider'    => ['@id' => $home . '#person'],
                'areaServed'  => 'IR',
            ];
        }
    }

    $crumbs = nasim_seo_breadcrumb_items();
    if (count($crumbs) > 1) {
        $elements = [];
        foreach ($crumbs as $i => $crumb) {
            $elements[] = [
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $crumb['name'],
                'item'     => $crumb['url'],
            ];
        }
        $graph[] = [
            '@type'           => 'BreadcrumbList',
            '@id'             => trailingslashit(nasim_seo_clean_url(is_singular() ? get_permalink() : $home)) . '#breadcrumb',
            'itemListElement' => $elements,
        ];
    }

    if (is_singular('post')) {
        $post_id = get_the_ID();
        $image   = get_the_post_thumbnail_url($post_id, 'full');
        $author  = nasim_post_author_name();
        $graph[] = [
            '@type'            => 'BlogPosting',
            '@id'              => get_permalink($post_id) . '#article',
            'headline'         => get_the_title($post_id),
            'datePublished'    => get_the_date('c', $post_id),
            'dateModified'     => get_the_modified_date('c', $post_id),
            'author'           => [
                '@type' => 'Person',
                'name'  => $author,
                'url'   => get_author_posts_url((int) get_the_author_meta('ID')),
            ],
            'publisher'        => ['@id' => $home . '#person'],
            'mainEntityOfPage' => get_permalink($post_id),
            'image'            => $image ?: $logo,
            'description'      => has_excerpt($post_id) ? get_the_excerpt($post_id) : wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $post_id)), 28),
            'inLanguage'       => 'fa-IR',
            'isPartOf'         => ['@id' => $home . '#website'],
        ];
    }

    // Skip duplicate heavy graph when Rank Math already outputs BlogPosting on singles —
    // still emit BreadcrumbList + Service via a leaner payload.
    if (defined('RANK_MATH_VERSION') && is_singular('post')) {
        $graph = array_values(array_filter($graph, function ($node) {
            $type = $node['@type'] ?? '';
            return 'BlogPosting' !== $type && 'WebSite' !== $type && !(is_array($type) && in_array('Person', $type, true));
        }));
        // Keep BreadcrumbList always.
    }

    if (!$graph) {
        return;
    }

    echo '<script type="application/ld+json">' . wp_json_encode([
        '@context' => 'https://schema.org',
        '@graph'   => $graph,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}
add_action('wp_head', 'nasim_seo_json_ld', 30);


/**
 * Breadcrumb trail items for UI + schema.
 *
 * @return array<int, array{name:string,url:string}>
 */
function nasim_seo_breadcrumb_items() {

    $items = [
        ['name' => 'خانه', 'url' => home_url('/')],
    ];

    if (is_singular('post')) {
        $items[] = ['name' => nasim_mod('articles_eyebrow') ?: 'مقالات', 'url' => nasim_blog_url()];
        $cat = nasim_primary_category();
        if ($cat) {
            $items[] = ['name' => $cat->name, 'url' => get_category_link($cat)];
        }
        $items[] = ['name' => get_the_title(), 'url' => get_permalink()];
    } elseif (is_singular('nasim_product')) {
        $items[] = ['name' => 'افزونه‌ها', 'url' => get_post_type_archive_link('nasim_product')];
        $items[] = ['name' => get_the_title(), 'url' => get_permalink()];
    } elseif (is_singular('nasim_course')) {
        $items[] = ['name' => 'دوره‌ها', 'url' => get_post_type_archive_link('nasim_course')];
        $items[] = ['name' => get_the_title(), 'url' => get_permalink()];
    } elseif (is_post_type_archive('nasim_product')) {
        $items[] = ['name' => 'افزونه‌ها', 'url' => get_post_type_archive_link('nasim_product')];
    } elseif (is_post_type_archive('nasim_course')) {
        $items[] = ['name' => 'دوره‌ها', 'url' => get_post_type_archive_link('nasim_course')];
    } elseif (is_home() || nasim_is_blog_route()) {
        $items[] = ['name' => nasim_mod('articles_eyebrow') ?: 'مقالات', 'url' => nasim_blog_url()];
    } elseif (is_page() && !is_front_page()) {
        $items[] = ['name' => get_the_title(), 'url' => get_permalink()];
    }

    return $items;
}


/**
 * Default author bio for E-E-A-T when the profile is empty.
 */
function nasim_seo_author_bio($author_id = 0) {

    $bio = $author_id ? get_the_author_meta('description', $author_id) : '';
    if (is_string($bio) && '' !== trim($bio) && false === strpos($bio, 'سالم‌تر') && false === strpos($bio, 'تناسب')) {
        return $bio;
    }

    return nasim_profile()['author_bio'];
}


/**
 * Article sidebar CTA aligned with business goals.
 *
 * @return array{title:string,text:string,button:string,url:string}
 */
function nasim_seo_article_cta() {

    $hubs = nasim_seo_hubs();

    return [
        'title'  => 'سایت وردپرسی‌ات نیاز به پشتیبان دارد؟',
        'text'   => 'از نگهداری ماهانه تا افزونه اختصاصی و ابزارهای آماده — مسیر رشد فروش را با هم می‌سازیم.',
        'button' => 'درخواست پشتیبانی',
        'url'    => $hubs['support']['url'],
    ];
}


/**
 * Internal-link cluster block for the end of articles.
 */
function nasim_seo_render_hub_links() {

    $hubs = nasim_seo_hubs();
    $keys = array_keys($hubs);
    if (nasim_store_enabled()) {
        $keys = array_values(array_unique(array_merge(['support', 'plugins', 'custom', 'courses', 'blog'], $keys)));
    } else {
        $keys = array_values(array_intersect(['support', 'courses', 'blog', 'bio', 'about'], $keys));
        if (!$keys) {
            $keys = array_keys($hubs);
        }
    }
    ?>
    <section class="article-hubs" aria-label="مسیرهای پیشنهادی">
      <div class="section-heading">
        <div>
          <span class="eyebrow">ادامه مسیر</span>
          <h2>از این مقاله به <span>خدمات و منابع مرتبط</span></h2>
        </div>
      </div>
      <div class="article-hubs-grid">
        <?php foreach ($keys as $key) :
            $hub = $hubs[$key];
            ?>
        <a class="article-hub-card" href="<?php echo esc_url($hub['url']); ?>">
          <strong><?php echo esc_html($hub['title']); ?></strong>
          <span><?php echo esc_html($hub['desc']); ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php
}


/**
 * Mid-article soft CTA after the first H2 (once per post).
 */
function nasim_seo_inject_mid_cta($html) {

    if (!is_singular('post') || !in_the_loop() || !is_main_query()) {
        return $html;
    }

    static $done = false;
    if ($done) {
        return $html;
    }

    $hubs = nasim_seo_hubs();
    $cta  = '<aside class="article-mid-cta" aria-label="پیشنهاد مرتبط">'
        . '<strong>نیاز به پشتیبانی یا افزونه تخصصی داری؟</strong>'
        . '<p>اگر همین مشکل را روی سایت واقعی می‌بینی، می‌توانم نگهداری ماهانه یا یک پلاگین اختصاصی برایت پیاده کنم.</p>'
        . '<p class="article-mid-cta-links">'
        . '<a href="' . esc_url($hubs['support']['url']) . '">درخواست پشتیبانی</a>'
        . '<a href="' . esc_url($hubs['plugins']['url']) . '">مشاهده افزونه‌ها</a>'
        . '</p></aside>';

    $count = 0;
    $html  = preg_replace_callback('/<\/h2>/i', function ($m) use (&$count, $cta) {
        $count++;
        if (1 === $count) {
            return $m[0] . $cta;
        }
        return $m[0];
    }, $html, 1);

    $done = true;

    return $html;
}


/**
 * Share URLs for the current post.
 *
 * @return array<string,string>
 */
function nasim_seo_share_links($post_id = 0) {

    $post_id = $post_id ?: get_the_ID();
    $url     = rawurlencode(nasim_seo_clean_url(get_permalink($post_id)));
    $title   = rawurlencode(get_the_title($post_id));

    return [
        'telegram'  => 'https://t.me/share/url?url=' . $url . '&text=' . $title,
        'twitter'   => 'https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title,
        'linkedin'  => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url,
        'whatsapp'  => 'https://api.whatsapp.com/send?text=' . $title . '%20' . $url,
    ];
}


/**
 * Preload LCP image on singles when featured image exists.
 */
function nasim_seo_preload_featured_image() {

    if (!is_singular(['post', 'nasim_product', 'nasim_course']) || !has_post_thumbnail()) {
        return;
    }

    $id  = get_post_thumbnail_id();
    $src = wp_get_attachment_image_url($id, 'large');
    if (!$src) {
        return;
    }

    printf('<link rel="preload" as="image" href="%s">' . "\n", esc_url($src));
}
add_action('wp_head', 'nasim_seo_preload_featured_image', 1);


/**
 * Add skip link for accessibility + SEO crawl of main content.
 */
function nasim_seo_skip_link() {

    echo '<a class="skip-link" href="#main-content">رفتن به محتوای اصلی</a>';
}
add_action('wp_body_open', 'nasim_seo_skip_link', 1);
