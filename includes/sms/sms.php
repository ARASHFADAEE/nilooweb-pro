<?php

if(!defined('ABSPATH')) exit;



include_once THEME_DIR . '/includes/sms/class-nasim-smsir.php';
include_once THEME_DIR . '/includes/sms/class-nasim-msgway.php';


/**
 * SMS providers selectable in the theme settings
 */
function nasim_sms_providers() {

    return [
        'smsir'  => 'sms.ir',
        'msgway' => 'راه پیام (msgway.com)',
    ];
}


function nasim_sms_provider() {

    $provider = (string) nasim_setting('sms_provider');

    return isset(nasim_sms_providers()[$provider]) ? $provider : 'smsir';
}


/**
 * @return Nasim_SmsIr|Nasim_Msgway
 */
function nasim_sms_client() {

    return 'msgway' === nasim_sms_provider() ? Nasim_Msgway::from_settings() : Nasim_SmsIr::from_settings();
}


function nasim_sms_configured() {

    return nasim_sms_client()->is_configured();
}


/**
 * Template ID of the active provider for a purpose: login, register (falls back to login) or subscription
 */
function nasim_sms_template($purpose) {

    $prefix = nasim_sms_provider() . '_';

    if ('subscription' === $purpose) {
        return absint(nasim_setting($prefix . 'subscription_template'));
    }

    $template = 'register' === $purpose ? nasim_setting($prefix . 'register_template') : '';

    return absint($template ?: nasim_setting($prefix . 'login_template'));
}


/**
 * Send a one-time code with the login/register template
 *
 * @return true|WP_Error
 */
function nasim_sms_send_code($mobile, $purpose, $code) {

    $template = nasim_sms_template($purpose);

    $result = 'msgway' === nasim_sms_provider()
        ? Nasim_Msgway::from_settings()->send($mobile, $template, [], $code)
        : Nasim_SmsIr::from_settings()->send_verify($mobile, $template, [nasim_setting('smsir_code_param') => $code]);

    return is_wp_error($result) ? $result : true;
}


/**
 * Send a template message with named values; sms.ir uses the names as template variables, Rah Payam uses their order
 *
 * @return true|WP_Error
 */
function nasim_sms_send_values($mobile, $template, array $values) {

    $result = 'msgway' === nasim_sms_provider()
        ? Nasim_Msgway::from_settings()->send($mobile, $template, array_values($values))
        : Nasim_SmsIr::from_settings()->send_verify($mobile, $template, $values);

    return is_wp_error($result) ? $result : true;
}


/**
 * Variables available in the subscription activation message
 */
function nasim_subscription_sms_variables() {

    return [
        'NAME'   => __('نام کاربر', 'niloo-web'),
        'PLAN'   => __('نام پلن', 'niloo-web'),
        'EXPIRE' => __('تاریخ پایان اشتراک', 'niloo-web'),
        'DAYS'   => __('روزهای باقی‌مانده', 'niloo-web'),
    ];
}


/**
 * Variable names used by the activation template, in order (Rah Payam fills its params by this order)
 */
function nasim_subscription_sms_params() {

    $params = array_intersect(nasim_split_list(strtoupper((string) nasim_setting('sms_subscription_params'))), array_keys(nasim_subscription_sms_variables()));

    return $params ? array_values(array_unique($params)) : ['NAME', 'PLAN', 'EXPIRE'];
}


/**
 * Values of the activation message for a user
 */
function nasim_subscription_sms_values($user_id, $expires_at, $plan_id = 0) {

    $user = get_userdata($user_id);
    $plan = $plan_id ? get_the_title($plan_id) : '';

    $all = [
        'NAME'   => $user ? ($user->first_name ?: $user->display_name) : '',
        'PLAN'   => $plan ?: __('اشتراک', 'niloo-web'),
        'EXPIRE' => nasim_jdate($expires_at),
        'DAYS'   => nasim_fa_digits(max(0, (int) ceil(($expires_at - time()) / DAY_IN_SECONDS))),
    ];

    $values = [];

    foreach (nasim_subscription_sms_params() as $name) {
        $values[$name] = $all[$name];
    }

    return $values;
}


function nasim_subscription_sms_enabled() {

    return nasim_setting('sms_subscription') && nasim_sms_configured() && nasim_sms_template('subscription');
}


/**
 * Notify the user by SMS when a subscription is activated or extended (payment or manual grant)
 */
function nasim_send_subscription_sms($user_id, $expires_at, $plan_id) {

    if (!nasim_subscription_sms_enabled()) {
        return;
    }

    $mobile = nasim_get_user_mobile($user_id);

    if (!$mobile) {
        return;
    }

    $sent = nasim_sms_send_values($mobile, nasim_sms_template('subscription'), nasim_subscription_sms_values($user_id, $expires_at, $plan_id));

    if (is_wp_error($sent)) {
        error_log(sprintf('[nasim-sms] %s subscription notice for user %d: %s', nasim_sms_provider(), $user_id, $sent->get_error_message())); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
    }
}
add_action('nasim_subscription_extended', 'nasim_send_subscription_sms', 10, 3);
