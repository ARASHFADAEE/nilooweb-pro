<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Primary online (non-installment) payment gateway id: zibal | zarinpal
 */
function nasim_payment_gateway_id(): string
{
    $id = (string) nasim_opt('payment_gateway', 'zibal');

    return in_array($id, ['zibal', 'zarinpal'], true) ? $id : 'zibal';
}


/**
 * Instantiate a gateway client by id (defaults to active online gateway).
 *
 * @return Nasim_Zibal_Gateway|Nasim_Zarinpal_Gateway|Nasim_Snapppay_Gateway|Nasim_Digipay_Gateway
 */
function nasim_payment_gateway(string $gateway_id = '')
{
    $id = $gateway_id ?: nasim_payment_gateway_id();

    if ('snapppay' === $id) {
        return Nasim_Snapppay_Gateway::from_settings();
    }
    if ('digipay' === $id) {
        return Nasim_Digipay_Gateway::from_settings();
    }
    if ('zarinpal' === $id) {
        return Nasim_Zarinpal_Gateway::from_settings();
    }

    return Nasim_Zibal_Gateway::from_settings();
}


function nasim_payment_callback_url(array $extra_args = []): string
{
    $base = admin_url('admin-post.php?action=nasim_payment_callback');

    return $extra_args ? add_query_arg($extra_args, $base) : $base;
}


function nasim_payment_gateway_labels(): array
{
    return [
        'zibal'    => 'زیبال',
        'zarinpal' => 'زرین‌پال',
        'snapppay' => 'اسنپ‌پی (اقساطی)',
        'digipay'  => 'دیجی‌پی (اقساطی)',
    ];
}


/**
 * Return all payment methods available to the customer at checkout (primary online + optional installment gateways).
 *
 * @param int $amount_toman Optional plan price in Toman (for installment preview text).
 * @return array<int,array{id:string,label:string,desc:string,badge:string,installment:bool}>
 */
function nasim_available_payment_methods(int $amount_toman = 0): array
{
    $methods = [];

    $primary_id = nasim_payment_gateway_id();
    $primary    = nasim_payment_gateway($primary_id);
    $labels     = nasim_payment_gateway_labels();

    if ($primary->is_configured()) {
        $methods[] = [
            'id'          => $primary_id,
            'label'       => 'پرداخت آنلاین (کارت شتاب)',
            'desc'        => 'درگاه ' . ($labels[$primary_id] ?? $primary_id) . ' — فعال‌سازی آنی',
            'badge'       => 'نقدی',
            'installment' => false,
        ];
    }

    $snapppay = Nasim_Snapppay_Gateway::from_settings();
    if ($snapppay->is_available()) {
        $default_desc = $amount_toman > 0
            ? sprintf('۴ قسط %s ماهانه بدون سود', nasim_format_price((int) ceil($amount_toman / 4)))
            : '۴ قسط ماهانه بدون سود و کارمزد';
        $custom_desc  = trim((string) nasim_opt('snapppay_description', ''));
        $methods[]    = [
            'id'          => 'snapppay',
            'label'       => trim((string) nasim_opt('snapppay_title', '')) ?: 'پرداخت اقساطی اسنپ‌پی',
            'desc'        => $custom_desc ?: $default_desc,
            'badge'       => '۴ قسط',
            'installment' => true,
        ];
    }

    $digipay = Nasim_Digipay_Gateway::from_settings();
    if ($digipay->is_available()) {
        $custom_desc = trim((string) nasim_opt('digipay_description', ''));
        $methods[]   = [
            'id'          => 'digipay',
            'label'       => trim((string) nasim_opt('digipay_title', '')) ?: 'پرداخت اقساطی دیجی‌پی',
            'desc'        => $custom_desc ?: 'پرداخت اعتباری و اقساطی با دیجی‌پی',
            'badge'       => 'اقساطی',
            'installment' => true,
        ];
    }

    return $methods;
}


/**
 * Whether any installment gateway (SnappPay or DigiPay) is active & configured.
 */
function nasim_has_installment_gateways(): bool
{
    return Nasim_Snapppay_Gateway::from_settings()->is_available()
        || Nasim_Digipay_Gateway::from_settings()->is_available();
}


/**
 * Resolve a requested gateway id against currently allowed & configured gateways.
 */
function nasim_resolve_checkout_gateway_id(string $requested = ''): string
{
    $requested = sanitize_key($requested);

    if ('snapppay' === $requested && Nasim_Snapppay_Gateway::from_settings()->is_available()) {
        return 'snapppay';
    }
    if ('digipay' === $requested && Nasim_Digipay_Gateway::from_settings()->is_available()) {
        return 'digipay';
    }

    $primary_id = nasim_payment_gateway_id();
    if (nasim_payment_gateway($primary_id)->is_configured()) {
        return $primary_id;
    }

    // Fallback if online gateway isn't configured yet but an installment gateway is.
    if (Nasim_Snapppay_Gateway::from_settings()->is_available()) {
        return 'snapppay';
    }
    if (Nasim_Digipay_Gateway::from_settings()->is_available()) {
        return 'digipay';
    }

    return $primary_id;
}


/**
 * Start checkout for a plan with the selected (or default active) gateway.
 *
 * @param array<string,mixed> $plan
 * @return string|WP_Error Payment start URL
 */
function nasim_start_plan_payment(int $user_id, array $plan, string $return_url = '', string $requested_gateway = '')
{
    $gateway_id = nasim_resolve_checkout_gateway_id($requested_gateway);
    $gateway    = nasim_payment_gateway($gateway_id);

    if (!$gateway->is_configured()) {
        return new WP_Error('gateway', 'درگاه پرداخت هنوز تنظیم نشده است.');
    }

    $payment_id = nasim_create_payment($user_id, $plan, $gateway_id);

    if (!$payment_id) {
        return new WP_Error('payment', 'ساخت تراکنش ناموفق بود.');
    }

    if ($return_url) {
        set_transient('nasim_pay_return_' . $payment_id, esc_url_raw($return_url), DAY_IN_SECONDS);
    }

    $user        = get_userdata($user_id);
    $mobile      = function_exists('nasim_get_user_mobile') ? (string) nasim_get_user_mobile($user_id) : '';
    $desc        = sprintf(
        '%s %s - %s',
        'bundle' === ($plan['type'] ?? '') ? 'خرید' : (in_array($plan['type'] ?? '', ['course', 'spot_course'], true) ? 'خرید دوره' : 'خرید'),
        $plan['title'],
        $user ? $user->user_login : ('user-' . $user_id)
    );
    $amount_rial = (int) $plan['price'] * 10;
    $item_id     = (int) ($plan['course_id'] ?? $plan['bundle_id'] ?? $plan['id'] ?? $payment_id);

    if ($gateway instanceof Nasim_Snapppay_Gateway) {
        $callback = nasim_payment_callback_url(['gateway' => 'snapppay', 'orderId' => $payment_id]);
        $result   = $gateway->request($amount_rial, $callback, $payment_id, (string) $plan['title'], $mobile, $item_id);

        if (is_wp_error($result)) {
            nasim_mark_payment_failed($payment_id, $result->get_error_message());
            return $result;
        }

        nasim_set_payment_track_id($payment_id, $result['track_id']);
        return $result['start_url'];
    }

    if ($gateway instanceof Nasim_Digipay_Gateway) {
        $callback = nasim_payment_callback_url(['gateway' => 'digipay', 'orderId' => $payment_id]);
        $result   = $gateway->request($amount_rial, $callback, $payment_id, (string) $plan['title'], $mobile, $item_id);

        if (is_wp_error($result)) {
            nasim_mark_payment_failed($payment_id, $result->get_error_message());
            return $result;
        }

        nasim_set_payment_track_id($payment_id, $result['track_id']);
        return $result['start_url'];
    }

    $callback = nasim_payment_callback_url();
    $track_id = $gateway->request($amount_rial, $callback, $payment_id, $desc);

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
    // Store module (license slots) is optional; it is not loaded when the store is disabled.
    if (function_exists('nasim_is_domain_slot_payment') && nasim_is_domain_slot_payment($payment)) {
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

    if ('bundle' === $plan_type) {
        nasim_fulfill_bundle_plan((int) $payment->user_id, $plan_id);
    } elseif (in_array($plan_type, ['course', 'spot_course'], true)) {
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
    $result_url = function_exists('nasim_is_domain_slot_payment') && nasim_is_domain_slot_payment($payment)
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
 * Unified payment callback for Zibal, ZarinPal, SnappPay, and DigiPay.
 */
function nasim_handle_payment_callback()
{
    // phpcs:disable WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing -- gateway callback, validated by server-side verify.
    $track_id = sanitize_text_field(wp_unslash($_GET['trackId'] ?? $_GET['Authority'] ?? ''));
    $order_id = absint($_GET['orderId'] ?? $_POST['providerId'] ?? $_GET['providerId'] ?? 0);
    $success  = sanitize_text_field(wp_unslash($_GET['success'] ?? ''));
    $status   = sanitize_text_field(wp_unslash($_GET['Status'] ?? ''));
    $spp_state = strtoupper(sanitize_text_field(wp_unslash($_POST['state'] ?? $_GET['state'] ?? '')));
    $dp_result = strtoupper(sanitize_text_field(wp_unslash($_POST['result'] ?? $_GET['result'] ?? '')));
    $dp_track  = sanitize_text_field(wp_unslash($_POST['trackingCode'] ?? $_GET['trackingCode'] ?? ''));
    $dp_type   = absint($_POST['type'] ?? $_GET['type'] ?? 0);
    // phpcs:enable

    $payment = $track_id ? nasim_get_payment_by_track_id($track_id) : null;

    if (!$payment && $order_id) {
        $payment = nasim_get_payment($order_id);
    }

    if (!$payment || ($order_id && (int) $payment->id !== $order_id && empty($_GET['Authority']))) {
        wp_safe_redirect(nasim_subscription_url(['nasim_error' => 'payment']));
        exit;
    }

    if ($order_id && empty($_GET['Authority']) && (int) $payment->id !== $order_id) {
        wp_safe_redirect(nasim_subscription_url(['nasim_error' => 'payment']));
        exit;
    }

    if ('pending' !== $payment->status) {
        wp_safe_redirect(nasim_payment_result_url($payment));
        exit;
    }

    $gateway_id = !empty($payment->gateway) ? (string) $payment->gateway : nasim_payment_gateway_id();

    if ('snapppay' === $gateway_id) {
        $payment_token = (string) $payment->track_id;

        if ('OK' !== $spp_state) {
            nasim_mark_payment_failed($payment->id, 'پرداخت اقساطی اسنپ‌پی لغو شد یا ناموفق بود.');
        } elseif ('' === $payment_token) {
            nasim_mark_payment_failed($payment->id, 'توکن پرداخت اسنپ‌پی نامعتبر است.');
        } else {
            $verified = Nasim_Snapppay_Gateway::from_settings()->verify_and_settle($payment_token, (int) $payment->amount);
            if (is_wp_error($verified)) {
                nasim_mark_payment_failed($payment->id, $verified->get_error_message());
            } elseif (nasim_mark_payment_paid($payment->id, $verified['ref_number'], $verified['card_number'])) {
                nasim_fulfill_paid_payment($payment);
                do_action('nasim_payment_completed', (int) $payment->id);
            }
        }
    } elseif ('digipay' === $gateway_id) {
        if ('SUCCESS' !== $dp_result || '' === $dp_track) {
            nasim_mark_payment_failed($payment->id, 'پرداخت اقساطی دیجی‌پی لغو شد یا ناموفق بود.');
        } else {
            $verified = Nasim_Digipay_Gateway::from_settings()->verify($dp_track, (string) $payment->id, $dp_type, (string) $payment->plan_title);
            if (is_wp_error($verified)) {
                nasim_mark_payment_failed($payment->id, $verified->get_error_message());
            } elseif ((int) $verified['amount'] > 0 && (int) $verified['amount'] !== (int) $payment->amount) {
                nasim_mark_payment_failed($payment->id, 'مبلغ پرداختی دیجی‌پی با مبلغ سفارش مغایرت دارد.');
            } elseif (nasim_mark_payment_paid($payment->id, $verified['ref_number'], $verified['card_number'])) {
                nasim_fulfill_paid_payment($payment);
                do_action('nasim_payment_completed', (int) $payment->id);
            }
        }
    } elseif ('zarinpal' === $gateway_id) {
        if ('OK' !== strtoupper($status)) {
            nasim_mark_payment_failed($payment->id, 'پرداخت لغو شد یا ناموفق بود.');
        } else {
            $verified = Nasim_Zarinpal_Gateway::from_settings()->verify($track_id, (int) $payment->amount);
            if (is_wp_error($verified)) {
                nasim_mark_payment_failed($payment->id, $verified->get_error_message());
            } elseif ((int) $verified['amount'] !== (int) $payment->amount) {
                nasim_mark_payment_failed($payment->id, 'مبلغ پرداختی با مبلغ سفارش مغایرت دارد.');
            } elseif (nasim_mark_payment_paid($payment->id, $verified['ref_number'], $verified['card_number'])) {
                nasim_fulfill_paid_payment($payment);
                do_action('nasim_payment_completed', (int) $payment->id);
            }
        }
    } else {
        if ('1' !== $success) {
            nasim_mark_payment_failed($payment->id, 'پرداخت لغو شد یا ناموفق بود.');
        } else {
            $verified = Nasim_Zibal_Gateway::from_settings()->verify($track_id);
            if (is_wp_error($verified)) {
                nasim_mark_payment_failed($payment->id, $verified->get_error_message());
            } elseif ((int) $verified['amount'] !== (int) $payment->amount) {
                nasim_mark_payment_failed($payment->id, 'مبلغ پرداختی با مبلغ سفارش مغایرت دارد.');
            } elseif (nasim_mark_payment_paid($payment->id, $verified['ref_number'], $verified['card_number'])) {
                nasim_fulfill_paid_payment($payment);
                do_action('nasim_payment_completed', (int) $payment->id);
            }
        }
    }

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


/**
 * Re-apply course / bundle access for paid payments (repairs purchases that were paid but never fulfilled).
 * Safe to run repeatedly: course grants are idempotent and subscriptions are skipped.
 *
 * @return int number of payments processed
 */
function nasim_repair_paid_payments(): int
{
    global $wpdb;

    $table = nasim_payments_table();
    $rows  = $wpdb->get_results("SELECT * FROM {$table} WHERE status = 'paid' ORDER BY id ASC"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $count = 0;

    foreach ($rows as $payment) {
        $type = (string) get_post_meta((int) $payment->plan_id, '_nasim_plan_type', true);
        if (!in_array($type, ['course', 'spot_course', 'bundle'], true)) {
            continue;
        }
        nasim_fulfill_paid_payment($payment);
        $count++;
    }

    return $count;
}


function nasim_handle_repair_payments()
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('دسترسی غیرمجاز.', 'niloo-web'), 403);
    }

    check_admin_referer('nasim_repair_payments');

    $count = nasim_repair_paid_payments();

    wp_safe_redirect(add_query_arg(['nasim_repaired' => $count], wp_get_referer() ?: admin_url()));
    exit;
}
add_action('admin_post_nasim_repair_payments', 'nasim_handle_repair_payments');


function nasim_repair_payments_notice()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || false === strpos((string) $screen->id, 'nasim')) {
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if (isset($_GET['nasim_repaired'])) {
        printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html(sprintf('دسترسی %s خرید موفق بررسی و اعمال شد.', nasim_fa_digits(absint($_GET['nasim_repaired']))))); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        update_option('nasim_payments_repaired', 1, false);
        return;
    }

    if (get_option('nasim_payments_repaired')) {
        return;
    }

    $url = wp_nonce_url(admin_url('admin-post.php?action=nasim_repair_payments'), 'nasim_repair_payments');
    printf(
        '<div class="notice notice-warning"><p><strong>%s</strong> %s <a class="button button-primary" href="%s">%s</a></p></div>',
        esc_html('بررسی خریدهای قبلی:'),
        esc_html('در نسخه قبلی قالب، بعد از پرداخت موفق ممکن بود دسترسی دوره به خریدار داده نشود. با یک کلیک دسترسی همه خریدهای موفق دوباره اعمال می‌شود.'),
        esc_url($url),
        esc_html('اعمال دسترسی خریدهای موفق')
    );
}
add_action('admin_notices', 'nasim_repair_payments_notice');
