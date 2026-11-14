<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Active payment gateway id: zibal | zarinpal
 */
function nasim_payment_gateway_id(): string
{
    $id = (string) nasim_opt('payment_gateway', 'zibal');

    return in_array($id, ['zibal', 'zarinpal'], true) ? $id : 'zibal';
}


/**
 * @return Nasim_Zibal_Gateway|Nasim_Zarinpal_Gateway
 */
function nasim_payment_gateway()
{
    return 'zarinpal' === nasim_payment_gateway_id()
        ? Nasim_Zarinpal_Gateway::from_settings()
        : Nasim_Zibal_Gateway::from_settings();
}


function nasim_payment_callback_url(): string
{
    return admin_url('admin-post.php?action=nasim_payment_callback');
}


function nasim_payment_gateway_labels(): array
{
    return [
        'zibal'    => 'زیبال',
        'zarinpal' => 'زرین‌پال',
    ];
}


/**
 * Start checkout for a plan with the active gateway.
 *
 * @param array<string,mixed> $plan
 * @return string|WP_Error Payment start URL
 */
function nasim_start_plan_payment(int $user_id, array $plan, string $return_url = '')
{
    $gateway = nasim_payment_gateway();

    if (!$gateway->is_configured()) {
        return new WP_Error('gateway', 'درگاه پرداخت هنوز تنظیم نشده است.');
    }

    $payment_id = nasim_create_payment($user_id, $plan, nasim_payment_gateway_id());

    if (!$payment_id) {
        return new WP_Error('payment', 'ساخت تراکنش ناموفق بود.');
    }

    if ($return_url) {
        set_transient('nasim_pay_return_' . $payment_id, esc_url_raw($return_url), DAY_IN_SECONDS);
    }

    $user = get_userdata($user_id);
    $desc = sprintf(
        '%s %s - %s',
        in_array($plan['type'] ?? '', ['course', 'spot_course'], true) ? 'خرید دوره' : 'خرید',
        $plan['title'],
        $user ? $user->user_login : ('user-' . $user_id)
    );

    $amount_rial = (int) $plan['price'] * 10;
    $callback    = nasim_payment_callback_url();

    if ($gateway instanceof Nasim_Zarinpal_Gateway) {
        $track_id = $gateway->request($amount_rial, $callback, $payment_id, $desc);
    } else {
        $track_id = $gateway->request($amount_rial, $callback, $payment_id, $desc);
    }

    if (is_wp_error($track_id)) {
        nasim_mark_payment_failed($payment_id, $track_id->get_error_message());

        return $track_id;
    }

    nasim_set_payment_track_id($payment_id, $track_id);

    return $gateway->start_url($track_id);
}


/**
 * Apply access after a successful payment (shared by all gateways).
 */
function nasim_fulfill_paid_payment(object $payment): void
{
    if (nasim_is_domain_slot_payment($payment)) {
        $slot_slug = nasim_domain_slot_payment_slug($payment);
        if ($slot_slug) {
            nasim_add_product_domain_slot((int) $payment->user_id, $slot_slug, 1);
        } else {
            nasim_add_license_activation_slot((int) $payment->user_id, 1);
        }

        return;
    }

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


/**
 * Redirect URL after payment success/failure.
 */
function nasim_payment_result_url(object $payment): string
{
    $result_url = nasim_is_domain_slot_payment($payment)
        ? nasim_subscription_url(['payment' => $payment->id, 'domain_slot' => 1, 'tab' => 'license'])
        : nasim_subscription_url(['payment' => $payment->id]);

    if (function_exists('nasim_express_payment_return_url') && 'paid' === $payment->status) {
        $custom = nasim_express_payment_return_url($payment);
        if ($custom) {
            $result_url = add_query_arg('payment', (int) $payment->id, $custom);
        }
    }

    return $result_url;
}


/**
 * Unified payment callback for Zibal + ZarinPal.
 */
function nasim_handle_payment_callback()
{
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- gateway callback, validated by server-side verify.
    $track_id = sanitize_text_field(wp_unslash($_GET['trackId'] ?? $_GET['Authority'] ?? ''));
    $order_id = absint($_GET['orderId'] ?? 0);
    $success  = sanitize_text_field(wp_unslash($_GET['success'] ?? ''));
    $status   = sanitize_text_field(wp_unslash($_GET['Status'] ?? ''));
    // phpcs:enable

    $payment = $track_id ? nasim_get_payment_by_track_id($track_id) : null;

    if (!$payment && $order_id) {
        $payment = nasim_get_payment($order_id);
    }

    if (!$payment || ($order_id && (int) $payment->id !== $order_id && empty($_GET['Authority']))) {
        wp_safe_redirect(nasim_subscription_url(['nasim_error' => 'payment']));
        exit;
    }

    // ZarinPal sends Authority without orderId; match by track_id only.
    if ($order_id && empty($_GET['Authority']) && (int) $payment->id !== $order_id) {
        wp_safe_redirect(nasim_subscription_url(['nasim_error' => 'payment']));
        exit;
    }

    $result_url = nasim_subscription_url(['payment' => $payment->id]);

    if ('pending' !== $payment->status) {
        wp_safe_redirect(nasim_payment_result_url($payment));
        exit;
    }

    $gateway_id = !empty($payment->gateway) ? (string) $payment->gateway : nasim_payment_gateway_id();
    $failed     = false;

    if ('zarinpal' === $gateway_id) {
        if ('OK' !== strtoupper($status)) {
            $failed = true;
            nasim_mark_payment_failed($payment->id, 'پرداخت لغو شد یا ناموفق بود.');
        } else {
            $verified = Nasim_Zarinpal_Gateway::from_settings()->verify($track_id, (int) $payment->amount);
            if (is_wp_error($verified)) {
                $failed = true;
                nasim_mark_payment_failed($payment->id, $verified->get_error_message());
            } elseif ((int) $verified['amount'] !== (int) $payment->amount) {
                $failed = true;
                nasim_mark_payment_failed($payment->id, 'مبلغ پرداختی با مبلغ سفارش مغایرت دارد.');
            } elseif (nasim_mark_payment_paid($payment->id, $verified['ref_number'], $verified['card_number'])) {
                nasim_fulfill_paid_payment($payment);
                do_action('nasim_payment_completed', (int) $payment->id);
            }
        }
    } else {
        if ('1' !== $success) {
            $failed = true;
            nasim_mark_payment_failed($payment->id, 'پرداخت لغو شد یا ناموفق بود.');
        } else {
            $verified = Nasim_Zibal_Gateway::from_settings()->verify($track_id);
            if (is_wp_error($verified)) {
                $failed = true;
                nasim_mark_payment_failed($payment->id, $verified->get_error_message());
            } elseif ((int) $verified['amount'] !== (int) $payment->amount) {
                $failed = true;
                nasim_mark_payment_failed($payment->id, 'مبلغ پرداختی با مبلغ سفارش مغایرت دارد.');
            } elseif (nasim_mark_payment_paid($payment->id, $verified['ref_number'], $verified['card_number'])) {
                nasim_fulfill_paid_payment($payment);
                do_action('nasim_payment_completed', (int) $payment->id);
            }
        }
    }

    unset($failed);

    $payment = nasim_get_payment((int) $payment->id) ?: $payment;

    if (!is_user_logged_in() && !empty($payment->user_id) && function_exists('nasim_login_user')) {
        $buyer = get_userdata((int) $payment->user_id);
        if ($buyer) {
            nasim_login_user($buyer);
        }
    }

    wp_safe_redirect(nasim_payment_result_url($payment));
    exit;
}
add_action('admin_post_nasim_payment_callback', 'nasim_handle_payment_callback');
add_action('admin_post_nopriv_nasim_payment_callback', 'nasim_handle_payment_callback');

// Backward-compatible Zibal callback URL.
add_action('admin_post_nasim_zibal_callback', 'nasim_handle_payment_callback');
add_action('admin_post_nopriv_nasim_zibal_callback', 'nasim_handle_payment_callback');
