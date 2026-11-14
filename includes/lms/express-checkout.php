<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Campaign express checkout: sticky buy → name/mobile → OTP → Zibal → account + access.
 */

/**
 * Resolve payable plan for a course (purchase plan or featured subscription).
 *
 * @return array<string,mixed>|null
 */
function nasim_express_plan_for_course(int $course_id): ?array
{
    $mode = function_exists('nasim_course_access_mode') ? nasim_course_access_mode($course_id) : 'subscription';

    if ('free' === $mode) {
        return null;
    }

    if ('purchase' === $mode) {
        $plan_id = (int) get_post_meta($course_id, '_nasim_linked_plan_id', true);
        $plan    = $plan_id ? nasim_get_plan($plan_id, false) : null;
        return ($plan && $plan['price'] > 0) ? $plan : null;
    }

    $plans = nasim_get_plans('subscription');
    if (!$plans) {
        return null;
    }

    $featured = null;
    foreach ($plans as $p) {
        if (!empty($p['featured'])) {
            $featured = $p;
            break;
        }
    }

    $plan = $featured ?: $plans[0];

    return ($plan && $plan['price'] > 0) ? $plan : null;
}

/**
 * Find or create subscriber from checkout OTP identity.
 *
 * @return int|WP_Error user id
 */
function nasim_ensure_checkout_user(string $name, string $mobile)
{
    $mobile = nasim_normalize_mobile($mobile);
    $name   = sanitize_text_field($name);

    if (!$mobile) {
        return new WP_Error('mobile', 'شماره موبایل معتبر نیست.', ['field' => 'mobile']);
    }

    if (mb_strlen($name) < 2 || mb_strlen($name) > 60) {
        return new WP_Error('name', 'نام و نام خانوادگی را وارد کن.', ['field' => 'name']);
    }

    $existing = nasim_get_user_by_mobile($mobile);
    if ($existing) {
        if ($name && $name !== $existing->display_name) {
            wp_update_user([
                'ID'           => $existing->ID,
                'display_name' => $name,
                'nickname'     => $name,
                'first_name'   => $name,
            ]);
        }

        return (int) $existing->ID;
    }

    $base = 'm' . substr($mobile, 1); // m9xxxxxxxxx
    $username = $base;
    $i = 0;
    while (username_exists($username)) {
        $i++;
        $username = $base . $i;
    }

    return nasim_create_user([
        'name'     => $name,
        'username' => $username,
        'mobile'   => $mobile,
        'password' => wp_generate_password(20, true, true),
    ]);
}

/**
 * Start Zibal for a plan; returns payment URL or WP_Error.
 *
 * @param array<string,mixed> $plan
 * @return string|WP_Error
 */
function nasim_express_start_payment(int $user_id, array $plan, string $return_url = '')
{
    $gateway = Nasim_Zibal_Gateway::from_settings();

    if (!$gateway->is_configured()) {
        return new WP_Error('gateway', 'درگاه پرداخت هنوز تنظیم نشده است.');
    }

    $payment_id = nasim_create_payment($user_id, $plan);
    if (!$payment_id) {
        return new WP_Error('payment', 'ساخت تراکنش ناموفق بود.');
    }

    if ($return_url) {
        set_transient('nasim_pay_return_' . $payment_id, esc_url_raw($return_url), DAY_IN_SECONDS);
    }

    $user = get_userdata($user_id);
    $desc = sprintf(
        '%s %s - %s',
        in_array($plan['type'] ?? '', ['course', 'spot_course'], true) ? 'خرید دوره' : 'اشتراک',
        $plan['title'],
        $user ? $user->user_login : ('user-' . $user_id)
    );

    $track_id = $gateway->request(
        $plan['price'] * 10,
        admin_url('admin-post.php?action=nasim_zibal_callback'),
        $payment_id,
        $desc
    );

    if (is_wp_error($track_id)) {
        nasim_mark_payment_failed($payment_id, $track_id->get_error_message());

        return $track_id;
    }

    nasim_set_payment_track_id($payment_id, $track_id);

    return $gateway->start_url($track_id);
}

/**
 * Preferred return URL after paid checkout.
 */
function nasim_express_payment_return_url(object $payment): string
{
    $stored = get_transient('nasim_pay_return_' . (int) $payment->id);
    if ($stored) {
        delete_transient('nasim_pay_return_' . (int) $payment->id);

        return (string) $stored;
    }

    $plan_type = (string) get_post_meta((int) $payment->plan_id, '_nasim_plan_type', true);
    $course_id = (int) get_post_meta((int) $payment->plan_id, '_nasim_plan_course_id', true);

    if (in_array($plan_type, ['course', 'spot_course'], true) && $course_id) {
        return (string) get_permalink($course_id);
    }

    return nasim_subscription_url(['payment' => (int) $payment->id, 'tab' => 'overview']);
}

function nasim_express_json_error(string $message, string $field = '', array $extra = [], int $status = 400): void
{
    wp_send_json_error(array_merge(['message' => $message, 'field' => $field], $extra), $status);
}

function nasim_express_ajax_guard(): void
{
    if (!check_ajax_referer('nasim_express_checkout', 'nonce', false)) {
        nasim_express_json_error('نشست منقضی شده؛ صفحه را رفرش کن.', '', [], 403);
    }
}

/**
 * Step 1: send OTP for guest checkout.
 */
function nasim_ajax_express_send_otp(): void
{
    nasim_express_ajax_guard();

    $name   = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $mobile = nasim_normalize_mobile(wp_unslash($_POST['mobile'] ?? ''));
    $plan_id = absint($_POST['plan_id'] ?? 0);
    $plan    = $plan_id ? nasim_get_plan($plan_id, false) : null;

    if (!$plan || $plan['price'] <= 0) {
        nasim_express_json_error('پلن خرید معتبر نیست.');
    }

    if (mb_strlen($name) < 2) {
        nasim_express_json_error('نام و نام خانوادگی را وارد کن.', 'name');
    }

    if (!$mobile) {
        nasim_express_json_error('شماره موبایل معتبر (۰۹…) را وارد کن.', 'mobile');
    }

    if (!nasim_otp_available()) {
        nasim_express_json_error('ارسال پیامک فعلاً در دسترس نیست. تنظیمات SMS را بررسی کن.');
    }

    $sent = nasim_otp_send($mobile, 'checkout');
    if (is_wp_error($sent)) {
        $data = (array) $sent->get_error_data();
        nasim_express_json_error($sent->get_error_message(), $data['field'] ?? 'mobile', isset($data['wait']) ? ['wait' => (int) $data['wait']] : []);
    }

    // Keep name for verify step (short TTL).
    set_transient('nasim_checkout_id_' . md5($mobile), [
        'name'    => $name,
        'plan_id' => $plan_id,
        'mobile'  => $mobile,
    ], 20 * MINUTE_IN_SECONDS);

    wp_send_json_success([
        'message' => sprintf('کد تأیید به %s پیامک شد.', nasim_mask_mobile($mobile)),
        'mobile'  => nasim_mask_mobile($mobile),
        'resend'  => (int) $sent['resend'],
        'length'  => (int) $sent['length'],
    ]);
}
add_action('wp_ajax_nasim_express_send_otp', 'nasim_ajax_express_send_otp');
add_action('wp_ajax_nopriv_nasim_express_send_otp', 'nasim_ajax_express_send_otp');

/**
 * Step 2: verify OTP (or skip if logged in), create user, start gateway.
 */
function nasim_ajax_express_pay(): void
{
    nasim_express_ajax_guard();

    $plan_id    = absint($_POST['plan_id'] ?? 0);
    $course_id  = absint($_POST['course_id'] ?? 0);
    $return_url = esc_url_raw(wp_unslash($_POST['return_url'] ?? ''));
    $plan       = $plan_id ? nasim_get_plan($plan_id, false) : null;

    if (!$plan || $plan['price'] <= 0) {
        nasim_express_json_error('پلن خرید معتبر نیست.');
    }

    if ($course_id && 'nasim_course' === get_post_type($course_id)) {
        $return_url = $return_url ?: (string) get_permalink($course_id);
    }

    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
    } else {
        $name   = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $mobile = nasim_normalize_mobile(wp_unslash($_POST['mobile'] ?? ''));
        $code   = (string) wp_unslash($_POST['code'] ?? '');

        if (!$mobile) {
            nasim_express_json_error('شماره موبایل معتبر نیست.', 'mobile');
        }

        $pending = get_transient('nasim_checkout_id_' . md5($mobile));
        if (is_array($pending) && !empty($pending['name'])) {
            $name = (string) $pending['name'];
        }

        $verified = nasim_otp_verify($mobile, 'checkout', $code);
        if (is_wp_error($verified)) {
            nasim_express_json_error(
                $verified->get_error_message(),
                'code',
                ['expired' => 'otp_expired' === $verified->get_error_code()]
            );
        }

        $user_id = nasim_ensure_checkout_user($name, $mobile);
        if (is_wp_error($user_id)) {
            $data = (array) $user_id->get_error_data();
            nasim_express_json_error($user_id->get_error_message(), $data['field'] ?? '');
        }

        $user = get_userdata((int) $user_id);
        if ($user) {
            nasim_login_user($user);
        }

        delete_transient('nasim_checkout_id_' . md5($mobile));
    }

    $pay_url = nasim_express_start_payment((int) $user_id, $plan, $return_url);
    if (is_wp_error($pay_url)) {
        nasim_express_json_error($pay_url->get_error_message());
    }

    wp_send_json_success([
        'redirect' => $pay_url,
        'message'  => 'در حال انتقال به درگاه پرداخت…',
    ]);
}
add_action('wp_ajax_nasim_express_pay', 'nasim_ajax_express_pay');
add_action('wp_ajax_nopriv_nasim_express_pay', 'nasim_ajax_express_pay');
