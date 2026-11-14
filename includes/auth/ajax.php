<?php

if(!defined('ABSPATH')) exit;



/**
 * Auth AJAX endpoints (guests only): password login, one-time code login, registration
 */
function nasim_auth_json_error($message, $field = '', $extra = [], $status = 400) {

    wp_send_json_error(array_merge(['message' => $message, 'field' => $field], $extra), $status);
}


function nasim_auth_guard() {

    // Already signed in (e.g. in another tab): the guest nonce no longer matches, so just move on.
    if (is_user_logged_in()) {
        wp_send_json_success(['redirect' => nasim_auth_redirect_target(nasim_auth_posted('redirect_to'), wp_get_current_user())]);
    }

    if (!check_ajax_referer('nasim_auth', 'nonce', false)) {
        nasim_auth_json_error('نشست منقضی شده است؛ صفحه را دوباره بارگذاری کن.', '', [], 403);
    }
}


function nasim_auth_add_ajax_action($action, $callback) {

    add_action('wp_ajax_nopriv_' . $action, $callback);
    add_action('wp_ajax_' . $action, $callback);
}


// phpcs:disable WordPress.Security.NonceVerification.Missing -- every handler calls nasim_auth_guard() first.
function nasim_auth_posted($key) {

    return isset($_POST[$key]) ? wp_unslash($_POST[$key]) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by each caller.
}
// phpcs:enable


function nasim_auth_error_from(WP_Error $error) {

    $data = (array) $error->get_error_data();

    nasim_auth_json_error($error->get_error_message(), $data['field'] ?? '', isset($data['wait']) ? ['wait' => (int) $data['wait']] : []);
}


/**
 * Username / mobile / email + password
 */
function nasim_ajax_auth_password() {

    nasim_auth_guard();

    $identifier = trim(sanitize_text_field(nasim_auth_posted('identifier')));
    $password   = (string) nasim_auth_posted('password');
    $bucket     = 'login_fail_' . nasim_client_ip();

    if ('' === $identifier || '' === $password) {
        nasim_auth_json_error('نام کاربری و رمز عبور را وارد کن.', '' === $identifier ? 'identifier' : 'password');
    }

    if (nasim_rate_exceeded($bucket, 10)) {
        nasim_auth_json_error('تلاش‌های ناموفق زیاد بود؛ ۱۵ دقیقه دیگر دوباره امتحان کن یا با کد پیامکی وارد شو.');
    }

    $by_mobile = nasim_get_user_by_mobile($identifier);
    $user      = wp_signon([
        'user_login'    => $by_mobile ? $by_mobile->user_login : $identifier,
        'user_password' => $password,
        'remember'      => !empty(nasim_auth_posted('remember')),
    ], is_ssl());

    if (is_wp_error($user)) {
        nasim_rate_hit($bucket, 15 * MINUTE_IN_SECONDS);
        nasim_auth_json_error('نام کاربری یا رمز عبور اشتباه است.', 'password');
    }

    wp_send_json_success(['redirect' => nasim_auth_redirect_target(nasim_auth_posted('redirect_to'), $user)]);
}
nasim_auth_add_ajax_action('nasim_auth_password', 'nasim_ajax_auth_password');


/**
 * Send a one-time code for login (registered number) or registration (valid form, new number)
 */
function nasim_ajax_auth_send_code() {

    nasim_auth_guard();

    $purpose = 'register' === nasim_auth_posted('purpose') ? 'register' : 'login';

    if ('register' === $purpose) {
        if (!nasim_registration_enabled()) {
            nasim_auth_json_error('ثبت‌نام در حال حاضر غیرفعال است.');
        }

        $fields = nasim_validate_registration(array_map('nasim_auth_posted', ['name' => 'name', 'username' => 'username', 'mobile' => 'mobile', 'password' => 'password']));

        if (is_wp_error($fields)) {
            nasim_auth_error_from($fields);
        }

        $mobile = $fields['mobile'];
    } else {
        if (!nasim_otp_login_enabled()) {
            nasim_auth_json_error('ورود با کد پیامکی در حال حاضر غیرفعال است.');
        }

        $mobile = nasim_normalize_mobile(nasim_auth_posted('mobile'));

        if (!$mobile) {
            nasim_auth_json_error('شماره موبایل معتبر نیست.', 'mobile');
        }

        if (!nasim_get_user_by_mobile($mobile)) {
            nasim_auth_json_error('حسابی با این شماره پیدا نشد.', 'mobile', ['register' => nasim_registration_enabled()]);
        }
    }

    $sent = nasim_otp_send($mobile, $purpose);

    if (is_wp_error($sent)) {
        nasim_auth_error_from($sent);
    }

    wp_send_json_success([
        'message' => sprintf('کد تأیید به شماره %s پیامک شد.', nasim_mask_mobile($mobile)),
        'mobile'  => nasim_mask_mobile($mobile),
        'resend'  => $sent['resend'],
        'length'  => $sent['length'],
    ]);
}
nasim_auth_add_ajax_action('nasim_auth_send_code', 'nasim_ajax_auth_send_code');


function nasim_ajax_auth_otp_login() {

    nasim_auth_guard();

    if (!nasim_otp_login_enabled()) {
        nasim_auth_json_error('ورود با کد پیامکی در حال حاضر غیرفعال است.');
    }

    $mobile = nasim_normalize_mobile(nasim_auth_posted('mobile'));
    $user   = $mobile ? nasim_get_user_by_mobile($mobile) : false;

    if (!$user) {
        nasim_auth_json_error('شماره موبایل معتبر نیست.', 'mobile');
    }

    $verified = nasim_otp_verify($mobile, 'login', nasim_auth_posted('code'));

    if (is_wp_error($verified)) {
        nasim_auth_json_error($verified->get_error_message(), 'code', ['expired' => 'otp_expired' === $verified->get_error_code()]);
    }

    nasim_login_user($user);

    wp_send_json_success(['redirect' => nasim_auth_redirect_target(nasim_auth_posted('redirect_to'), $user)]);
}
nasim_auth_add_ajax_action('nasim_auth_otp_login', 'nasim_ajax_auth_otp_login');


/**
 * The full form is re-submitted with the code and re-validated, so no password is kept server-side between steps
 */
function nasim_ajax_auth_register() {

    nasim_auth_guard();

    if (!nasim_registration_enabled()) {
        nasim_auth_json_error('ثبت‌نام در حال حاضر غیرفعال است.');
    }

    $fields = nasim_validate_registration(array_map('nasim_auth_posted', ['name' => 'name', 'username' => 'username', 'mobile' => 'mobile', 'password' => 'password']));

    if (is_wp_error($fields)) {
        nasim_auth_error_from($fields);
    }

    $verified = nasim_otp_verify($fields['mobile'], 'register', nasim_auth_posted('code'));

    if (is_wp_error($verified)) {
        nasim_auth_json_error($verified->get_error_message(), 'code', ['expired' => 'otp_expired' === $verified->get_error_code()]);
    }

    $user_id = nasim_create_user($fields);

    if (is_wp_error($user_id)) {
        nasim_auth_json_error('ساخت حساب ناموفق بود؛ دوباره تلاش کن.');
    }

    $user = get_userdata($user_id);

    nasim_login_user($user);

    wp_send_json_success(['redirect' => nasim_auth_redirect_target(nasim_auth_posted('redirect_to'), $user)]);
}
nasim_auth_add_ajax_action('nasim_auth_register', 'nasim_ajax_auth_register');
