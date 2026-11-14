<?php

if(!defined('ABSPATH')) exit;



/**
 * Signed download URL for a product ZIP (logged-in subscribers)
 */
function nasim_product_download_url($product_id) {

    return wp_nonce_url(
        admin_url('admin-post.php?action=nasim_download_product&product_id=' . (int) $product_id),
        'nasim_download_product_' . (int) $product_id
    );
}


/**
 * Serve the ZIP only to users with access; never expose a public media URL
 */
function nasim_handle_download_product() {

    $product_id = absint($_GET['product_id'] ?? 0);

    check_admin_referer('nasim_download_product_' . $product_id);

    if (!$product_id || 'nasim_product' !== get_post_type($product_id) || 'publish' !== get_post_status($product_id)) {
        wp_die('محصول پیدا نشد.', 'خطا', ['response' => 404]);
    }

    if (!is_user_logged_in()) {
        wp_safe_redirect(nasim_auth_url('login', get_permalink($product_id)));
        exit;
    }

    if (!nasim_user_can_download_product($product_id)) {
        wp_safe_redirect(nasim_subscription_url());
        exit;
    }

    $path = nasim_get_product_file_path($product_id);

    if (!$path) {
        wp_die('فایل محصول هنوز آماده نیست.', 'خطا', ['response' => 404]);
    }

    $filename = basename($path);
    $meta     = nasim_get_product_meta($product_id);

    if ($meta['slug']) {
        $filename = $meta['slug'] . ($meta['version'] ? '-' . $meta['version'] : '') . '.zip';
    }

    nasim_track_product_download($product_id, get_current_user_id());

    nocache_headers();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '"');
    header('Content-Length: ' . (string) filesize($path));
    header('X-Robots-Tag: noindex');

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- binary download stream.
    readfile($path);
    exit;
}
add_action('admin_post_nasim_download_product', 'nasim_handle_download_product');


function nasim_handle_download_product_guest() {

    $product_id = absint($_GET['product_id'] ?? 0);
    $redirect   = $product_id ? get_permalink($product_id) : home_url('/');

    wp_safe_redirect(nasim_auth_url('login', $redirect ?: home_url('/')));
    exit;
}
add_action('admin_post_nopriv_nasim_download_product', 'nasim_handle_download_product_guest');


/**
 * Time-limited package URL for the update API (license already validated)
 */
function nasim_product_update_package_url($product_id, $license_key) {

    $token = wp_hash($product_id . '|' . strtoupper($license_key) . '|' . gmdate('Y-m-d'));

    return add_query_arg([
        'nasim_package' => 1,
        'product_id'    => (int) $product_id,
        'license'       => strtoupper($license_key),
        'token'         => $token,
    ], home_url('/'));
}


/**
 * Public package download used by WordPress auto-update (token + valid license)
 */
function nasim_handle_package_download() {

    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- signed token + license validation.
    if (empty($_GET['nasim_package'])) {
        return;
    }

    $product_id  = absint($_GET['product_id'] ?? 0);
    $license_key = strtoupper(sanitize_text_field(wp_unslash($_GET['license'] ?? '')));
    $token       = sanitize_text_field(wp_unslash($_GET['token'] ?? ''));
    // phpcs:enable

    $expected = wp_hash($product_id . '|' . $license_key . '|' . gmdate('Y-m-d'));

    // Allow yesterday's token so midnight UTC does not break in-flight updates.
    $yesterday = wp_hash($product_id . '|' . $license_key . '|' . gmdate('Y-m-d', time() - DAY_IN_SECONDS));

    if (!hash_equals($expected, $token) && !hash_equals($yesterday, $token)) {
        status_header(403);
        exit('Invalid token');
    }

    $license = nasim_get_license_by_key($license_key);

    if (!$license || !nasim_license_is_valid($license)) {
        status_header(403);
        exit('Invalid license');
    }

    if (!$product_id || 'publish' !== get_post_status($product_id)) {
        status_header(404);
        exit('Not found');
    }

    $path = nasim_get_product_file_path($product_id);

    if (!$path) {
        status_header(404);
        exit('File missing');
    }

    $meta     = nasim_get_product_meta($product_id);
    $filename = ($meta['slug'] ?: 'product') . ($meta['version'] ? '-' . $meta['version'] : '') . '.zip';

    nocache_headers();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . sanitize_file_name($filename) . '"');
    header('Content-Length: ' . (string) filesize($path));

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- binary download stream.
    readfile($path);
    exit;
}
add_action('template_redirect', 'nasim_handle_package_download', 1);
