<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Theme settings defaults (SMS / auth). Values live in Mino Option (niloo_theme_settings).
 */
function nasim_settings_defaults()
{
    return [
        'sms_provider'                 => 'smsir',
        'smsir_api_key'                => '',
        'smsir_login_template'         => '',
        'smsir_register_template'      => '',
        'smsir_subscription_template'  => '',
        'smsir_code_param'             => 'CODE',
        'msgway_api_key'               => '',
        'msgway_login_template'        => '',
        'msgway_register_template'     => '',
        'msgway_subscription_template' => '',
        'msgway_line'                  => 0,
        'sms_subscription'             => 0,
        'sms_subscription_params'      => 'NAME,PLAN,EXPIRE',
        'otp_length'                   => 5,
        'otp_ttl'                      => 120,
        'otp_resend'                   => 90,
        'auth_otp_login'               => 1,
        'auth_registration'            => 1,
        'payment_gateway'              => 'zibal',
        'zibal_merchant'               => '',
        'zibal_sandbox'                => false,
        'zarinpal_merchant'            => '',
        'zarinpal_sandbox'             => false,
    ];
}


/**
 * Read a setting (Mino Option with legacy fallback).
 *
 * @return mixed
 */
function nasim_setting($key)
{
    if (function_exists('nasim_opt')) {
        $value = nasim_opt($key, null);
        if (null !== $value) {
            // Keep 0/1 for historical checkbox consumers.
            if (is_bool($value) && in_array($key, ['auth_otp_login', 'auth_registration', 'sms_subscription', 'zibal_sandbox', 'zarinpal_sandbox', 'spot_test'], true)) {
                return $value ? 1 : 0;
            }

            return $value;
        }
    }

    $defaults = nasim_settings_defaults();
    $settings = (array) get_option('nasim_theme_settings', []);

    return $settings[$key] ?? ($defaults[$key] ?? null);
}


/**
 * Cached sms.ir credit for admin tools.
 */
function nasim_smsir_cached_credit()
{
    $credit = get_transient('nasim_smsir_credit');

    if (false === $credit) {
        $credit = Nasim_SmsIr::from_settings()->credit();
        set_transient('nasim_smsir_credit', $credit, 5 * MINUTE_IN_SECONDS);
    }

    return $credit;
}


function nasim_flush_smsir_credit()
{
    delete_transient('nasim_smsir_credit');
}
add_action('update_option_nasim_theme_settings', 'nasim_flush_smsir_credit');
add_action('update_option_niloo_theme_settings', 'nasim_flush_smsir_credit');


/**
 * SMS test form handler (Tools tab in Mino settings).
 */
function nasim_handle_sms_test()
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('دسترسی غیرمجاز.', 'niloo-web'), 403);
    }

    check_admin_referer('nasim_sms_test');

    $mobile = nasim_normalize_mobile(wp_unslash($_POST['mobile'] ?? ''));
    $type   = 'subscription' === ($_POST['type'] ?? '') ? 'subscription' : 'code';

    if (!$mobile) {
        $result = new WP_Error('mobile', __('شماره موبایل معتبر نیست.', 'niloo-web'));
    } elseif ('subscription' === $type) {
        $values = nasim_subscription_sms_values(get_current_user_id(), time() + 30 * DAY_IN_SECONDS);
        $result = nasim_sms_send_values($mobile, nasim_sms_template('subscription'), $values);
    } else {
        $result = nasim_sms_send_code($mobile, 'login', '12345');
    }

    set_transient('nasim_sms_test_' . get_current_user_id(), [
        'status'  => is_wp_error($result) ? 'error' : 'ok',
        'message' => is_wp_error($result)
            ? sprintf(__('ارسال ناموفق: %s', 'niloo-web'), $result->get_error_message())
            : sprintf(__('پیامک آزمایشی به %s ارسال شد.', 'niloo-web'), $mobile),
    ], MINUTE_IN_SECONDS);

    nasim_flush_smsir_credit();

    $redirect = admin_url('admin.php?page=niloo_theme_settings&tab=tools');
    wp_safe_redirect($redirect);
    exit;
}
add_action('admin_post_nasim_sms_test', 'nasim_handle_sms_test');
