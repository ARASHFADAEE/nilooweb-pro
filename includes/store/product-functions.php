<?php

if(!defined('ABSPATH')) exit;



/**
 * Product meta defaults and normalized values
 */
function nasim_get_product_meta($product_id) {

    $type = get_post_meta($product_id, '_nasim_product_type', true);

    return [
        'type'     => in_array($type, ['plugin', 'theme'], true) ? $type : 'plugin',
        'slug'     => (string) get_post_meta($product_id, '_nasim_product_slug', true),
        'version'  => (string) get_post_meta($product_id, '_nasim_product_version', true),
        'file_id'  => (int) get_post_meta($product_id, '_nasim_product_file', true),
        'changelog'=> (string) get_post_meta($product_id, '_nasim_product_changelog', true),
        'requires' => (string) get_post_meta($product_id, '_nasim_product_requires', true),
        'tested'   => (string) get_post_meta($product_id, '_nasim_product_tested', true),
        'homepage' => (string) get_post_meta($product_id, '_nasim_product_homepage', true),
        'demo_url' => (string) get_post_meta($product_id, '_nasim_product_demo_url', true),
        'price'        => (int) get_post_meta($product_id, '_nasim_product_price', true),
        'sale_price'   => (int) get_post_meta($product_id, '_nasim_product_sale_price', true),
        'promo_label'  => (string) get_post_meta($product_id, '_nasim_product_promo_label', true),
        'sales_base'   => (int) get_post_meta($product_id, '_nasim_product_sales_base', true),
        'downloads'    => (int) get_post_meta($product_id, '_nasim_product_downloads', true),
        'satisfaction' => (string) get_post_meta($product_id, '_nasim_product_satisfaction', true),
        'support'      => (string) get_post_meta($product_id, '_nasim_product_support_grade', true),
    ];
}


/**
 * Price block values for cards: regular, final, discount percent (all in Toman)
 */
function nasim_product_pricing($meta) {

    $price = max(0, (int) $meta['price']);
    $sale  = max(0, (int) $meta['sale_price']);
    $final = ($sale > 0 && $sale < $price) ? $sale : $price;

    return [
        'regular'  => $price,
        'final'    => $final,
        'discount' => ($price > 0 && $final < $price) ? (int) round(($price - $final) / $price * 100) : 0,
    ];
}


function nasim_product_sales_count($meta) {

    return max(0, (int) $meta['sales_base']) + max(0, (int) $meta['downloads']);
}


/**
 * Cache sortable numbers so the archive can order by sales and price
 */
function nasim_refresh_product_sort_meta($product_id) {

    $meta    = nasim_get_product_meta($product_id);
    $pricing = nasim_product_pricing($meta);

    update_post_meta($product_id, '_nasim_product_sales_total', nasim_product_sales_count($meta));
    update_post_meta($product_id, '_nasim_product_final_price', $pricing['final']);
}


/**
 * Count each user once per product the first time they download it
 */
function nasim_track_product_download($product_id, $user_id) {

    $seen = (array) get_user_meta($user_id, '_nasim_downloaded_products', true);

    if (in_array((int) $product_id, array_map('intval', $seen), true)) {
        return;
    }

    $seen[] = (int) $product_id;
    update_user_meta($user_id, '_nasim_downloaded_products', array_values(array_filter($seen)));
    update_post_meta($product_id, '_nasim_product_downloads', (int) get_post_meta($product_id, '_nasim_product_downloads', true) + 1);
    nasim_refresh_product_sort_meta($product_id);
}


/**
 * Find a published product by its package slug (used by the update API)
 */
function nasim_get_product_by_slug($slug) {

    $slug = sanitize_title($slug);

    if ('' === $slug) {
        return null;
    }

    $posts = get_posts([
        'post_type'      => 'nasim_product',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_key'       => '_nasim_product_slug',
        'meta_value'     => $slug,
    ]);

    return $posts[0] ?? null;
}


/**
 * Absolute path to the product ZIP, or empty when missing
 */
function nasim_get_product_file_path($product_id) {

    $file_id = (int) get_post_meta($product_id, '_nasim_product_file', true);

    if (!$file_id) {
        return '';
    }

    $path = get_attached_file($file_id);

    return ($path && is_readable($path)) ? $path : '';
}


/**
 * Subscription holders (and product editors) may download
 */
function nasim_user_can_download_product($product_id, $user_id = null) {

    $user_id = $user_id ?? get_current_user_id();

    if (!$user_id) {
        return false;
    }

    return user_can($user_id, 'edit_post', $product_id)
        || nasim_has_active_subscription($user_id);
}


/**
 * Human label for product type
 */
function nasim_product_type_label($type) {

    return 'theme' === $type ? 'قالب' : 'افزونه';
}
