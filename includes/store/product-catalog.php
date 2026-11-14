<?php

if(!defined('ABSPATH')) exit;



function nasim_product_sort_options() {

    return [
        'newest'     => 'جدیدترین',
        'popular'    => 'پرفروش‌ترین',
        'price_asc'  => 'ارزان‌ترین',
        'price_desc' => 'گران‌ترین',
        'title'      => 'بر اساس نام',
    ];
}


function nasim_product_type_options() {

    return [
        'plugin' => 'افزونه',
        'theme'  => 'قالب',
    ];
}


/**
 * Current filter state read from the query string (and the term page, if any)
 */
function nasim_product_filters() {

    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- public read-only filters.
    $cat  = sanitize_title(wp_unslash($_GET['pcat'] ?? ''));
    $type = sanitize_key(wp_unslash($_GET['ptype'] ?? ''));
    $sort = sanitize_key(wp_unslash($_GET['sort'] ?? ''));
    $q    = sanitize_text_field(wp_unslash($_GET['q'] ?? ''));
    // phpcs:enable

    if (is_tax('nasim_product_cat')) {
        $cat = get_queried_object()->slug;
    }

    return [
        'pcat'  => $cat && term_exists($cat, 'nasim_product_cat') ? $cat : '',
        'ptype' => isset(nasim_product_type_options()[$type]) ? $type : '',
        'sort'  => isset(nasim_product_sort_options()[$sort]) ? $sort : 'newest',
        'q'     => mb_substr($q, 0, 80),
    ];
}


function nasim_is_product_catalog() {

    return is_post_type_archive('nasim_product') || is_tax('nasim_product_cat');
}


/**
 * Archive URL with the given filters merged into the current ones
 */
function nasim_product_filter_url(array $changes = []) {

    $filters = array_merge(nasim_product_filters(), $changes);
    $base    = get_post_type_archive_link('nasim_product');
    $args    = [];

    foreach ($filters as $key => $value) {
        if ('' === $value || ('sort' === $key && 'newest' === $value)) {
            continue;
        }
        $args[$key] = $value;
    }

    return $args ? add_query_arg(array_map('rawurlencode', $args), $base) : $base;
}


/**
 * Apply catalog filters to the main archive query
 */
function nasim_product_catalog_query($query) {

    if (is_admin() || !$query->is_main_query()) {
        return;
    }

    if (!$query->is_post_type_archive('nasim_product') && !$query->is_tax('nasim_product_cat')) {
        return;
    }

    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- public read-only filters.
    $cat  = sanitize_title(wp_unslash($_GET['pcat'] ?? ''));
    $type = sanitize_key(wp_unslash($_GET['ptype'] ?? ''));
    $sort = sanitize_key(wp_unslash($_GET['sort'] ?? 'newest'));
    $q    = sanitize_text_field(wp_unslash($_GET['q'] ?? ''));
    // phpcs:enable

    $query->set('posts_per_page', 12);

    if ($cat && $query->is_post_type_archive('nasim_product')) {
        $query->set('tax_query', [[
            'taxonomy'         => 'nasim_product_cat',
            'field'            => 'slug',
            'terms'            => $cat,
            'include_children' => true,
        ]]);
    }

    $meta_query = [];

    if (isset(nasim_product_type_options()[$type])) {
        $meta_query['type_clause'] = ['key' => '_nasim_product_type', 'value' => $type];
    }

    $sort_keys = [
        'popular'    => ['_nasim_product_sales_total', 'DESC'],
        'price_asc'  => ['_nasim_product_final_price', 'ASC'],
        'price_desc' => ['_nasim_product_final_price', 'DESC'],
    ];

    if (isset($sort_keys[$sort])) {
        [$key, $order] = $sort_keys[$sort];
        $meta_query['sort_clause'] = [
            'relation'   => 'OR',
            'sort_value' => ['key' => $key, 'type' => 'NUMERIC', 'compare' => 'EXISTS'],
            ['key' => $key, 'compare' => 'NOT EXISTS'],
        ];
        $query->set('orderby', ['sort_value' => $order, 'date' => 'DESC']);
    } elseif ('title' === $sort) {
        $query->set('orderby', 'title');
        $query->set('order', 'ASC');
    }

    if ($meta_query) {
        $query->set('meta_query', array_merge(['relation' => 'AND'], $meta_query));
    }

    if ('' !== $q) {
        $query->set('s', mb_substr($q, 0, 80));
    }
}
add_action('pre_get_posts', 'nasim_product_catalog_query');


/**
 * Backfill sort meta for products saved before the card fields existed
 */
function nasim_backfill_product_sort_meta() {

    if (get_option('nasim_product_sort_meta_v1')) {
        return;
    }

    $ids = get_posts(['post_type' => 'nasim_product', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids']);

    foreach ($ids as $id) {
        nasim_refresh_product_sort_meta($id);
    }

    update_option('nasim_product_sort_meta_v1', 1);
}
add_action('init', 'nasim_backfill_product_sort_meta', 20);


/**
 * Card subtitle: excerpt, or the first category name
 */
function nasim_product_card_subtitle($product_id) {

    $excerpt = has_excerpt($product_id) ? get_the_excerpt($product_id) : '';

    if ($excerpt) {
        return wp_strip_all_tags($excerpt);
    }

    $terms = get_the_terms($product_id, 'nasim_product_cat');

    return ($terms && !is_wp_error($terms)) ? $terms[0]->name : nasim_product_type_label(nasim_get_product_meta($product_id)['type']) . ' وردپرس';
}


function nasim_product_number($value) {

    return nasim_fa_digits(number_format((int) $value));
}
