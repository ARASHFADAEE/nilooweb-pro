<?php

if(!defined('ABSPATH')) exit;



/**
 * Bump when the blog route changes
 */
define('NASIM_BLOG_REWRITE_VERSION', '1');


/**
 * Blog index URL: the "Posts page" from Settings → Reading, otherwise the theme's /blog/ route
 */
function nasim_blog_url() {

    $posts_page = (int) get_option('page_for_posts');

    if ($posts_page && 'page' === get_option('show_on_front')) {
        return get_permalink($posts_page);
    }

    return get_option('permalink_structure') ? home_url('/blog/') : add_query_arg('nasim_page', 'blog', home_url('/'));
}


function nasim_register_blog_route() {

    add_rewrite_rule('^blog/?$', 'index.php?nasim_page=blog', 'top');
    add_rewrite_rule('^blog/page/([0-9]+)/?$', 'index.php?nasim_page=blog&paged=$matches[1]', 'top');
}
add_action('init', 'nasim_register_blog_route');


function nasim_blog_maybe_flush_rewrite_rules() {

    if (get_option('nasim_blog_rewrite_version') === NASIM_BLOG_REWRITE_VERSION) {
        return;
    }

    flush_rewrite_rules(false);
    update_option('nasim_blog_rewrite_version', NASIM_BLOG_REWRITE_VERSION);
}
add_action('init', 'nasim_blog_maybe_flush_rewrite_rules', 99);


function nasim_is_blog_route() {

    return 'blog' === get_query_var('nasim_page');
}


/**
 * Any post listing that belongs to the blog (index, categories, tags, authors, dates)
 */
function nasim_is_blog_context() {

    if (is_front_page()) {
        return false;
    }

    return is_home() || nasim_is_blog_route() || is_singular('post') || is_category() || is_tag() || is_author() || is_date();
}


/**
 * /blog/ is only a fallback; send it to the real posts page once one is configured
 */
function nasim_blog_route_redirect() {

    // A posts page with the "blog" slug has the same URL as the route; redirecting would loop.
    if (nasim_is_blog_route() && (int) get_option('page_for_posts') && 'page' === get_option('show_on_front') && trailingslashit(nasim_blog_url()) !== home_url('/blog/')) {
        wp_safe_redirect(nasim_blog_url(), 301);
        exit;
    }
}
add_action('template_redirect', 'nasim_blog_route_redirect');


function nasim_blog_document_title($parts) {

    if (is_404()) {
        $parts['title'] = 'صفحه پیدا نشد';
    } elseif (is_search()) {
        $parts['title'] = sprintf('نتایج جستجو برای «%s»', get_search_query(false));
    } elseif (nasim_is_blog_route()) {
        $parts['title'] = nasim_mod('articles_eyebrow') ?: 'مقالات';
        $parts['site']  = get_bloginfo('name');
    }

    return $parts;
}
add_filter('document_title_parts', 'nasim_blog_document_title');


/**
 * Heading of a post listing page: [eyebrow, title, highlighted part, description]
 */
function nasim_archive_heading() {

    if (is_search()) {
        global $wp_query;

        return [
            'جستجو',
            'نتایج برای',
            '«' . get_search_query() . '»',
            $wp_query->found_posts ? nasim_fa_digits($wp_query->found_posts) . ' مطلب پیدا شد.' : '',
        ];
    }

    if (is_category() || is_tag() || is_tax()) {
        $term = get_queried_object();

        return [is_tag() ? 'برچسب' : 'دسته‌بندی', '', $term->name, wp_strip_all_tags(term_description())];
    }

    if (is_author()) {
        $author = get_queried_object();

        return ['نویسنده', 'مطالب', $author->display_name, get_the_author_meta('description', $author->ID)];
    }

    if (is_date()) {
        return ['آرشیو', '', wp_strip_all_tags(get_the_archive_title()), ''];
    }

    return [nasim_mod('articles_eyebrow'), nasim_mod('articles_title'), nasim_mod('articles_title_highlight'), get_bloginfo('description')];
}


/**
 * Render post content once and collect its <h2> headings for the table of contents
 *
 * Headings without an id get a stable "section-N" id so the TOC links work.
 *
 * @return array  ['html' => string, 'toc' => [['id' => string, 'title' => string], ...]]
 */
function nasim_article_content() {

    $html  = apply_filters('the_content', get_the_content()); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals -- core hook.
    $html  = str_replace(']]>', ']]&gt;', $html);
    $toc   = [];
    $index = 0;

    $html = preg_replace_callback('/<h2([^>]*)>(.*?)<\/h2>/is', function ($match) use (&$toc, &$index) {
        $index++;
        $attrs = $match[1];

        if (preg_match('/\sid=["\']([^"\']+)["\']/i', $attrs, $id_match)) {
            $id = $id_match[1];
        } else {
            $id     = 'section-' . $index;
            $attrs .= ' id="' . $id . '"';
        }

        $title = trim(wp_strip_all_tags($match[2]));

        if ('' !== $title) {
            $toc[] = ['id' => $id, 'title' => $title];
        }

        return '<h2' . $attrs . '>' . $match[2] . '</h2>';
    }, $html);

    // Wrap tables for horizontal scroll on mobile without breaking layout.
    $html = preg_replace('/<table(\b[^>]*)>/i', '<div class="table-wrap"><table$1>', $html);
    $html = preg_replace('/<\/table>/i', '</table></div>', $html);

    return ['html' => $html, 'toc' => $toc];
}


/**
 * Posts from the same categories, topped up with the latest posts
 */
function nasim_related_posts($post_id, $count = 4) {

    $related = get_posts([
        'post_type'           => 'post',
        'posts_per_page'      => $count,
        'post__not_in'        => [$post_id],
        'category__in'        => wp_get_post_categories($post_id),
        'ignore_sticky_posts' => true,
    ]);

    if (count($related) < $count) {
        $related = array_merge($related, get_posts([
            'post_type'           => 'post',
            'posts_per_page'      => $count - count($related),
            'post__not_in'        => array_merge([$post_id], wp_list_pluck($related, 'ID')),
            'ignore_sticky_posts' => true,
        ]));
    }

    return $related;
}


/**
 * First real category of a post; the default "Uncategorized" is not worth showing
 *
 * @return WP_Term|null
 */
function nasim_primary_category($post = null) {

    $default = (int) get_option('default_category');

    foreach (get_the_category($post) as $category) {
        if ((int) $category->term_id !== $default) {
            return $category;
        }
    }

    return null;
}


/**
 * "Category • N min" line used on article cards ("·" would read as the Persian zero next to digits)
 */
function nasim_post_card_meta($post = null) {

    $category = nasim_primary_category($post);

    return implode(' • ', array_filter([
        $category ? $category->name : '',
        nasim_fa_digits(nasim_reading_time($post)) . ' دقیقه مطالعه',
    ]));
}


/**
 * Featured image, or the brand mark as a placeholder so card grids stay aligned
 */
function nasim_post_thumbnail($post = null, $size = 'medium_large') {

    if (has_post_thumbnail($post)) {
        echo get_the_post_thumbnail($post, $size, ['loading' => 'lazy', 'alt' => the_title_attribute(['echo' => false, 'post' => $post])]);
        return;
    }

    echo '<span class="thumb-placeholder" aria-hidden="true"><span class="brand-mark">N</span></span>';
}


/**
 * Author name of the current post; imported posts can lack an author, so fall back to the site name
 */
function nasim_post_author_name() {

    return get_the_author() ?: get_bloginfo('name');
}
