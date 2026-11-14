<?php

if(!defined('ABSPATH')) exit;



/**
 * Start a subscription purchase: create a pending payment and send the user to Zibal
 */
function nasim_handle_subscribe() {

    check_admin_referer('nasim_subscribe');

    $plan = nasim_get_plan(absint($_POST['plan_id'] ?? 0));

    if (!$plan || $plan['price'] <= 0) {
        wp_safe_redirect(nasim_subscription_url(['nasim_error' => 'plan']));
        exit;
    }

    $gateway = Nasim_Zibal_Gateway::from_settings();

    if (!$gateway->is_configured()) {
        wp_safe_redirect(nasim_subscription_url(['nasim_error' => 'gateway']));
        exit;
    }

    $payment_id = nasim_create_payment(get_current_user_id(), $plan);

    if (!$payment_id) {
        wp_safe_redirect(nasim_subscription_url(['nasim_error' => 'payment']));
        exit;
    }

    $track_id = $gateway->request(
        $plan['price'] * 10,
        admin_url('admin-post.php?action=nasim_zibal_callback'),
        $payment_id,
        sprintf(
            '%s %s - %s',
            in_array($plan['type'] ?? '', ['course', 'spot_course'], true) ? 'خرید دوره' : 'اشتراک',
            $plan['title'],
            wp_get_current_user()->user_login
        )
    );

    if (is_wp_error($track_id)) {
        nasim_mark_payment_failed($payment_id, $track_id->get_error_message());
        wp_safe_redirect(nasim_subscription_url(['payment' => $payment_id]));
        exit;
    }

    nasim_set_payment_track_id($payment_id, $track_id);

    // External gateway URL, so wp_safe_redirect() does not apply.
    wp_redirect($gateway->start_url($track_id)); // phpcs:ignore WordPress.Security.SafeRedirect
    exit;
}
add_action('admin_post_nasim_subscribe', 'nasim_handle_subscribe');


function nasim_handle_subscribe_guest() {

    wp_safe_redirect(nasim_auth_url('login', nasim_subscription_url()));
    exit;
}
add_action('admin_post_nopriv_nasim_subscribe', 'nasim_handle_subscribe_guest');


/**
 * Zibal callback: verify the payment server-side and activate the subscription
 *
 * Does not rely on the user session; the payment row identifies the buyer.
 */
function nasim_handle_zibal_callback() {

    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- gateway callback, validated by server-side verify.
    $track_id = sanitize_text_field(wp_unslash($_GET['trackId'] ?? ''));
    $order_id = absint($_GET['orderId'] ?? 0);
    $success  = sanitize_text_field(wp_unslash($_GET['success'] ?? ''));
    // phpcs:enable

    $payment = $track_id ? nasim_get_payment_by_track_id($track_id) : null;

    if (!$payment || ($order_id && (int) $payment->id !== $order_id)) {
        wp_safe_redirect(nasim_subscription_url(['nasim_error' => 'payment']));
        exit;
    }

    $result_url = nasim_subscription_url(['payment' => $payment->id]);

    if ('pending' !== $payment->status) {
        wp_safe_redirect($result_url);
        exit;
    }

    if ('1' !== $success) {
        nasim_mark_payment_failed($payment->id, 'پرداخت لغو شد یا ناموفق بود.');
        wp_safe_redirect($result_url);
        exit;
    }

    $verified = Nasim_Zibal_Gateway::from_settings()->verify($track_id);

    if (is_wp_error($verified)) {
        nasim_mark_payment_failed($payment->id, $verified->get_error_message());
        wp_safe_redirect($result_url);
        exit;
    }

    if ($verified['amount'] !== (int) $payment->amount) {
        nasim_mark_payment_failed($payment->id, 'مبلغ پرداختی با مبلغ سفارش مغایرت دارد.');
        wp_safe_redirect($result_url);
        exit;
    }

    if (nasim_mark_payment_paid($payment->id, $verified['ref_number'], $verified['card_number'])) {
        if (nasim_is_domain_slot_payment($payment)) {
            $slot_slug = nasim_domain_slot_payment_slug($payment);
            if ($slot_slug) {
                nasim_add_product_domain_slot((int) $payment->user_id, $slot_slug, 1);
            } else {
                nasim_add_license_activation_slot((int) $payment->user_id, 1);
            }
        } else {
            $plan_id   = (int) $payment->plan_id;
            $plan_type = (string) get_post_meta($plan_id, '_nasim_plan_type', true);
            $course_id = (int) get_post_meta($plan_id, '_nasim_plan_course_id', true);

            if (in_array($plan_type, ['course', 'spot_course'], true)) {
                if ($course_id) {
                    nasim_grant_course_access((int) $payment->user_id, $course_id);
                    update_post_meta($course_id, '_nasim_course_access_mode', 'purchase');
                }
            } elseif ((int) $payment->duration_days > 0) {
                nasim_extend_subscription((int) $payment->user_id, (int) $payment->duration_days, $plan_id);
            }
        }
        do_action('nasim_payment_completed', (int) $payment->id);
    }

    $result_url = nasim_is_domain_slot_payment($payment)
        ? nasim_subscription_url(['payment' => $payment->id, 'domain_slot' => 1, 'tab' => 'license'])
        : nasim_subscription_url(['payment' => $payment->id]);

    wp_safe_redirect($result_url);
    exit;
}
add_action('admin_post_nasim_zibal_callback', 'nasim_handle_zibal_callback');
add_action('admin_post_nopriv_nasim_zibal_callback', 'nasim_handle_zibal_callback');
