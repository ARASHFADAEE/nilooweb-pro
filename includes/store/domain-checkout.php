<?php

if(!defined('ABSPATH')) exit;



/**
 * Create a pending payment for one extra domain slot on a specific product
 */
function nasim_create_domain_slot_payment($user_id, $slug) {

    global $wpdb;

    $price = nasim_domain_slot_price();
    $slug  = sanitize_title($slug);
    $product = nasim_get_product_by_slug($slug);

    if ($price <= 0 || !$product) {
        return 0;
    }

    $title = nasim_domain_slot_payment_title() . ': ' . get_the_title($product);

    $wpdb->insert(nasim_payments_table(), [
        'user_id'       => (int) $user_id,
        'plan_id'       => 0,
        'plan_title'    => mb_substr($title, 0, 191),
        'duration_days' => 0,
        'amount'        => $price * 10,
        'status'        => 'pending',
        'note'          => 'domain_slot:' . $slug,
        'created_at'    => current_time('mysql', true),
    ], ['%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s']);

    return (int) $wpdb->insert_id;
}


/**
 * Start Zibal checkout to buy one extra domain slot for a product
 */
function nasim_handle_buy_domain_slot() {

    check_admin_referer('nasim_buy_domain_slot');

    if (!is_user_logged_in()) {
        wp_safe_redirect(nasim_auth_url('login', nasim_subscription_url(['tab' => 'license'])));
        exit;
    }

    if (!nasim_has_active_subscription()) {
        wp_safe_redirect(nasim_subscription_url(['nasim_error' => 'subscription', 'tab' => 'license']));
        exit;
    }

    $slug    = sanitize_title(wp_unslash($_POST['product_slug'] ?? ''));
    $product = $slug ? nasim_get_product_by_slug($slug) : null;
    $price   = nasim_domain_slot_price();

    if (!$product) {
        wp_safe_redirect(nasim_subscription_url(['nasim_error' => 'domain', 'tab' => 'license']));
        exit;
    }

    if ($price <= 0) {
        wp_safe_redirect(nasim_subscription_url(['nasim_error' => 'domain_price', 'tab' => 'license']));
        exit;
    }

    $gateway = Nasim_Zibal_Gateway::from_settings();

    if (!$gateway->is_configured()) {
        wp_safe_redirect(nasim_subscription_url(['nasim_error' => 'gateway', 'tab' => 'license']));
        exit;
    }

    nasim_ensure_user_license(get_current_user_id());

    $payment_id = nasim_create_domain_slot_payment(get_current_user_id(), $slug);

    if (!$payment_id) {
        wp_safe_redirect(nasim_subscription_url(['nasim_error' => 'payment', 'tab' => 'license']));
        exit;
    }

    $track_id = $gateway->request(
        $price * 10,
        admin_url('admin-post.php?action=nasim_zibal_callback'),
        $payment_id,
        sprintf('دامنه اضافه %s - %s', $slug, wp_get_current_user()->user_login)
    );

    if (is_wp_error($track_id)) {
        nasim_mark_payment_failed($payment_id, $track_id->get_error_message());
        wp_safe_redirect(nasim_subscription_url(['payment' => $payment_id, 'tab' => 'license']));
        exit;
    }

    nasim_set_payment_track_id($payment_id, $track_id);

    wp_redirect($gateway->start_url($track_id)); // phpcs:ignore WordPress.Security.SafeRedirect
    exit;
}
add_action('admin_post_nasim_buy_domain_slot', 'nasim_handle_buy_domain_slot');


function nasim_handle_buy_domain_slot_guest() {

    wp_safe_redirect(nasim_auth_url('login', nasim_subscription_url(['tab' => 'license'])));
    exit;
}
add_action('admin_post_nopriv_nasim_buy_domain_slot', 'nasim_handle_buy_domain_slot_guest');


/**
 * User releases a product↔domain activation
 */
function nasim_handle_release_domain() {

    check_admin_referer('nasim_release_domain');

    if (!is_user_logged_in()) {
        wp_safe_redirect(nasim_auth_url('login', nasim_subscription_url(['tab' => 'license'])));
        exit;
    }

    $domain  = nasim_normalize_domain(wp_unslash($_POST['domain'] ?? ''));
    $slug    = sanitize_title(wp_unslash($_POST['product_slug'] ?? ''));
    $license = nasim_get_user_license(get_current_user_id());

    if (!$license || !$domain || !$slug) {
        wp_safe_redirect(nasim_subscription_url(['nasim_error' => 'domain', 'tab' => 'license']));
        exit;
    }

    nasim_deactivate_license($license->license_key, $domain, $slug);

    wp_safe_redirect(nasim_subscription_url(['domain_released' => 1, 'tab' => 'license']));
    exit;
}
add_action('admin_post_nasim_release_domain', 'nasim_handle_release_domain');
