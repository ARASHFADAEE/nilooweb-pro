<?php

if(!defined('ABSPATH')) exit;



/**
 * Normalize an Iranian mobile number to 09xxxxxxxxx; empty string when invalid
 *
 * Accepts Persian/Arabic digits, spaces/dashes and the +98 / 0098 / 98 prefixes.
 */
function nasim_normalize_mobile($value) {

    $digits = preg_replace('/\D+/', '', nasim_latin_digits((string) $value));

    if ('0098' === substr($digits, 0, 4)) {
        $digits = substr($digits, 4);
    } elseif ('98' === substr($digits, 0, 2) && 12 === strlen($digits)) {
        $digits = substr($digits, 2);
    }

    if (10 === strlen($digits) && '9' === $digits[0]) {
        $digits = '0' . $digits;
    }

    return preg_match('/^09\d{9}$/', $digits) ? $digits : '';
}


function nasim_mask_mobile($mobile) {

    return substr($mobile, 0, 4) . '***' . substr($mobile, -4);
}


function nasim_get_user_mobile($user_id) {

    return (string) get_user_meta($user_id, 'nasim_mobile', true);
}


/**
 * @return WP_User|false
 */
function nasim_get_user_by_mobile($mobile) {

    $mobile = nasim_normalize_mobile($mobile);

    if (!$mobile) {
        return false;
    }

    $users = get_users([
        'meta_key'    => 'nasim_mobile',
        'meta_value'  => $mobile,
        'number'      => 1,
        'count_total' => false,
    ]);

    return $users ? $users[0] : false;
}


/**
 * SMS delivery is possible (or simulated through NASIM_OTP_DEBUG on local installs)
 */
function nasim_otp_available() {

    if (defined('NASIM_OTP_DEBUG') && NASIM_OTP_DEBUG) {
        return true;
    }

    return nasim_sms_configured() && nasim_sms_template('login');
}


/**
 * Fixed-window counters for throttling (per mobile / per IP)
 */
function nasim_rate_key($bucket) {

    return 'nasim_rl_' . md5($bucket);
}


function nasim_rate_exceeded($bucket, $max) {

    $hits = get_transient(nasim_rate_key($bucket));

    return is_array($hits) && $hits['count'] >= $max;
}


function nasim_rate_hit($bucket, $window) {

    $key  = nasim_rate_key($bucket);
    $hits = get_transient($key);

    if (!is_array($hits) || $hits['until'] <= time()) {
        $hits = ['count' => 0, 'until' => time() + $window];
    }

    $hits['count']++;
    set_transient($key, $hits, max(1, $hits['until'] - time()));
}


/**
 * Client IP from the socket only; forwarded headers are spoofable
 */
function nasim_client_ip() {

    return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
}


function nasim_otp_key($mobile, $purpose) {

    return 'nasim_otp_' . md5($purpose . '|' . $mobile);
}


function nasim_otp_hash($mobile, $purpose, $code) {

    return hash_hmac('sha256', $purpose . '|' . $mobile . '|' . $code, wp_salt('nonce'));
}


/**
 * Generate and send a one-time code
 *
 * @return array|WP_Error  ['resend' => seconds until a new code may be requested, 'length' => digits]
 */
function nasim_otp_send($mobile, $purpose) {

    $resend = (int) nasim_setting('otp_resend');
    $ttl    = (int) nasim_setting('otp_ttl');
    $length = (int) nasim_setting('otp_length');
    $state  = get_transient(nasim_otp_key($mobile, $purpose));

    if (is_array($state) && time() - $state['sent'] < $resend) {
        $wait = $resend - (time() - $state['sent']);

        return new WP_Error('otp_wait', sprintf('برای ارسال دوباره %s ثانیه صبر کن.', nasim_fa_digits($wait)), ['wait' => $wait]);
    }

    if (nasim_rate_exceeded('otp_mobile_' . $mobile, 5) || nasim_rate_exceeded('otp_ip_' . nasim_client_ip(), 15)) {
        return new WP_Error('otp_limit', 'تعداد درخواست‌های کد بیش از حد مجاز است؛ یک ساعت دیگر دوباره تلاش کن.');
    }

    nasim_rate_hit('otp_mobile_' . $mobile, HOUR_IN_SECONDS);
    nasim_rate_hit('otp_ip_' . nasim_client_ip(), HOUR_IN_SECONDS);

    $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);

    if (defined('NASIM_OTP_DEBUG') && NASIM_OTP_DEBUG) {
        error_log(sprintf('[nasim-otp] %s code for %s: %s', $purpose, $mobile, $code)); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
    } else {
        $sent = nasim_sms_send_code($mobile, $purpose, $code);

        if (is_wp_error($sent)) {
            error_log(sprintf('[nasim-otp] %s: %s', nasim_sms_provider(), $sent->get_error_message())); // phpcs:ignore WordPress.PHP.DevelopmentFunctions

            return new WP_Error('otp_sms', 'ارسال پیامک ناموفق بود؛ چند دقیقه دیگر دوباره تلاش کن.');
        }
    }

    set_transient(nasim_otp_key($mobile, $purpose), [
        'hash'     => nasim_otp_hash($mobile, $purpose, $code),
        'sent'     => time(),
        'expires'  => time() + $ttl,
        'attempts' => 0,
    ], max($ttl, $resend) + MINUTE_IN_SECONDS);

    return ['resend' => $resend, 'length' => $length];
}


/**
 * Check a code; a correct code is consumed, wrong guesses are capped per code and per IP
 *
 * @return true|WP_Error
 */
function nasim_otp_verify($mobile, $purpose, $code) {

    $key   = nasim_otp_key($mobile, $purpose);
    $state = get_transient($key);
    $code  = preg_replace('/\D+/', '', nasim_latin_digits((string) $code));

    if (nasim_rate_exceeded('otp_verify_ip_' . nasim_client_ip(), 30)) {
        return new WP_Error('otp_limit', 'تلاش‌های ناموفق زیاد بود؛ کمی بعد دوباره امتحان کن.');
    }

    if (!is_array($state) || time() > $state['expires']) {
        return new WP_Error('otp_expired', 'کد منقضی شده است؛ کد جدید دریافت کن.');
    }

    if ($state['attempts'] >= 5) {
        delete_transient($key);

        return new WP_Error('otp_expired', 'تعداد تلاش‌ها بیش از حد مجاز بود؛ کد جدید دریافت کن.');
    }

    if ('' === $code || !hash_equals($state['hash'], nasim_otp_hash($mobile, $purpose, $code))) {
        $state['attempts']++;
        set_transient($key, $state, max(1, $state['expires'] - time()) + MINUTE_IN_SECONDS);
        nasim_rate_hit('otp_verify_ip_' . nasim_client_ip(), 15 * MINUTE_IN_SECONDS);

        return new WP_Error('otp_invalid', 'کد وارد شده درست نیست.');
    }

    delete_transient($key);

    return true;
}
