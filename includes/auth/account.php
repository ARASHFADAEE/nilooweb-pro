<?php

if(!defined('ABSPATH')) exit;



/**
 * Login and register pages: /login/ and /register/ (one template, two tabs)
 */
function nasim_register_auth_routes() {

    add_rewrite_rule('^login/?$', 'index.php?nasim_page=login', 'top');
    add_rewrite_rule('^register/?$', 'index.php?nasim_page=register', 'top');
}
add_action('init', 'nasim_register_auth_routes');


function nasim_is_auth_page() {

    return in_array(get_query_var('nasim_page'), ['login', 'register'], true);
}


function nasim_auth_url($tab = 'login', $redirect = '') {

    $tab = 'register' === $tab ? 'register' : 'login';
    $url = get_option('permalink_structure') ? home_url("/{$tab}/") : add_query_arg('nasim_page', $tab, home_url('/'));

    return $redirect ? add_query_arg('redirect_to', rawurlencode($redirect), $url) : $url;
}


function nasim_registration_enabled() {

    return (bool) nasim_setting('auth_registration') && nasim_otp_available();
}


function nasim_otp_login_enabled() {

    return (bool) nasim_setting('auth_otp_login') && nasim_otp_available();
}


/**
 * Where to send a user after login: a safe redirect_to, else the dashboard (staff) or user panel
 */
function nasim_auth_redirect_target($requested, $user = null) {

    $requested = $requested ? wp_validate_redirect(wp_sanitize_redirect($requested), '') : '';

    if ($requested) {
        return $requested;
    }

    return $user && user_can($user, 'edit_posts') ? admin_url() : nasim_subscription_url();
}


/**
 * Front-end login links point to the theme page; wp-admin keeps wp-login.php
 *
 * Re-auth requests stay on core: they must clear stale cookies, which the theme page does not do.
 */
function nasim_filter_login_url($login_url, $redirect, $force_reauth) {

    if ($force_reauth || (is_admin() && !wp_doing_ajax())) {
        return $login_url;
    }

    return nasim_auth_url('login', $redirect);
}
add_filter('login_url', 'nasim_filter_login_url', 10, 3);


function nasim_filter_register_url($url) {

    return nasim_registration_enabled() ? nasim_auth_url('register') : $url;
}
add_filter('register_url', 'nasim_filter_register_url');


function nasim_auth_template_redirect() {

    if (!nasim_is_auth_page()) {
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- redirect target only, validated below.
    $requested = isset($_GET['redirect_to']) ? wp_unslash($_GET['redirect_to']) : '';

    if (is_user_logged_in()) {
        wp_safe_redirect(nasim_auth_redirect_target($requested, wp_get_current_user()));
        exit;
    }

    nocache_headers();
}
add_action('template_redirect', 'nasim_auth_template_redirect');


function nasim_auth_template($template) {

    if (nasim_is_auth_page()) {
        return locate_template('templates/auth.php') ?: $template;
    }

    return $template;
}
add_filter('template_include', 'nasim_auth_template');


function nasim_auth_document_title($parts) {

    if (nasim_is_auth_page()) {
        $parts = ['title' => 'register' === get_query_var('nasim_page') ? 'ثبت‌نام' : 'ورود', 'site' => get_bloginfo('name')];
    }

    return $parts;
}
add_filter('document_title_parts', 'nasim_auth_document_title');


function nasim_auth_robots($robots) {

    if (nasim_is_auth_page()) {
        $robots['noindex'] = true;
        $robots['follow']  = true;
    }

    return $robots;
}
add_filter('wp_robots', 'nasim_auth_robots');


/**
 * Members use the theme's user panel; the admin toolbar is for staff only
 */
function nasim_member_admin_bar($show) {

    return $show && current_user_can('edit_posts');
}
add_filter('show_admin_bar', 'nasim_member_admin_bar');


/**
 * Validate the registration form; shared by "send code" and "create account"
 *
 * @return array|WP_Error  Sanitized fields; WP_Error data carries the offending field name
 */
function nasim_validate_registration(array $raw) {

    $name     = sanitize_text_field($raw['name'] ?? '');
    $username = trim(nasim_latin_digits((string) ($raw['username'] ?? '')));
    $mobile   = nasim_normalize_mobile($raw['mobile'] ?? '');
    $password = (string) ($raw['password'] ?? '');

    if (mb_strlen($name) < 2 || mb_strlen($name) > 60) {
        return new WP_Error('name', 'نام و نام خانوادگی را وارد کن.', ['field' => 'name']);
    }

    if (!preg_match('/^[A-Za-z0-9_.\-]{3,30}$/', $username)) {
        return new WP_Error('username', 'نام کاربری باید ۳ تا ۳۰ حرف انگلیسی، عدد یا _ . - باشد.', ['field' => 'username']);
    }

    $illegal = array_map('strtolower', (array) apply_filters('illegal_user_logins', []));

    if (!validate_username($username) || in_array(strtolower($username), $illegal, true) || username_exists($username)) {
        return new WP_Error('username', 'این نام کاربری قبلاً گرفته شده است.', ['field' => 'username']);
    }

    if (!$mobile) {
        return new WP_Error('mobile', 'شماره موبایل معتبر نیست.', ['field' => 'mobile']);
    }

    if (nasim_get_user_by_mobile($mobile)) {
        return new WP_Error('mobile', 'با این شماره قبلاً ثبت‌نام شده است؛ وارد شو.', ['field' => 'mobile']);
    }

    if (strlen($password) < 8 || strlen($password) > 128) {
        return new WP_Error('password', 'رمز عبور باید دست‌کم ۸ کاراکتر باشد.', ['field' => 'password']);
    }

    return compact('name', 'username', 'mobile', 'password');
}


/**
 * @return int|WP_Error  New user ID
 */
function nasim_create_user(array $fields) {

    $user_id = wp_insert_user([
        'user_login'   => $fields['username'],
        'user_pass'    => $fields['password'],
        'display_name' => $fields['name'],
        'nickname'     => $fields['name'],
        'first_name'   => $fields['name'],
        'role'         => 'subscriber',
    ]);

    if (is_wp_error($user_id)) {
        return $user_id;
    }

    update_user_meta($user_id, 'nasim_mobile', $fields['mobile']);

    do_action('nasim_user_registered', $user_id);

    return $user_id;
}


function nasim_login_user(WP_User $user, $remember = true) {

    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, $remember, is_ssl());

    do_action('wp_login', $user->user_login, $user);
}


/**
 * Mobile field on the profile screen; only user managers may change it (it is a login credential)
 */
function nasim_user_mobile_field($user) {

    $mobile = nasim_get_user_mobile($user->ID);
    ?>
    <h2><?php esc_html_e('ورود با موبایل', 'niloo-web'); ?></h2>
    <table class="form-table" role="presentation">
        <tr>
            <th><label for="nasim_mobile"><?php esc_html_e('شماره موبایل', 'niloo-web'); ?></label></th>
            <td>
                <?php if (current_user_can('edit_users')) : ?>
                <input type="tel" id="nasim_mobile" name="nasim_mobile" value="<?php echo esc_attr($mobile); ?>" class="regular-text" dir="ltr" placeholder="09123456789">
                <p class="description"><?php esc_html_e('برای ورود با کد پیامکی استفاده می‌شود و باید یکتا باشد.', 'niloo-web'); ?></p>
                <?php else : ?>
                <code dir="ltr"><?php echo esc_html($mobile ?: '—'); ?></code>
                <?php endif; ?>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'nasim_user_mobile_field');
add_action('edit_user_profile', 'nasim_user_mobile_field');


function nasim_validate_user_mobile($errors, $update, $user) {

    if (!current_user_can('edit_users') || !isset($_POST['nasim_mobile'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- core verified the profile nonce.
        return;
    }

    $raw    = trim(wp_unslash($_POST['nasim_mobile'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- normalized below.
    $mobile = nasim_normalize_mobile($raw);

    if ('' === $raw) {
        return;
    }

    if (!$mobile) {
        $errors->add('nasim_mobile', __('شماره موبایل معتبر نیست.', 'niloo-web'));
        return;
    }

    $owner = nasim_get_user_by_mobile($mobile);

    if ($owner && (empty($user->ID) || (int) $owner->ID !== (int) $user->ID)) {
        $errors->add('nasim_mobile', __('این شماره موبایل برای کاربر دیگری ثبت شده است.', 'niloo-web'));
    }
}
add_action('user_profile_update_errors', 'nasim_validate_user_mobile', 10, 3);


function nasim_save_user_mobile($user_id) {

    if (!current_user_can('edit_users') || !isset($_POST['nasim_mobile'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- core verified the profile nonce.
        return;
    }

    $raw    = trim(wp_unslash($_POST['nasim_mobile'])); // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- normalized below.
    $mobile = nasim_normalize_mobile($raw);
    $owner  = $mobile ? nasim_get_user_by_mobile($mobile) : false;

    // Runs before core validation; invalid input is reported by nasim_validate_user_mobile() and must not wipe the saved number.
    if ('' === $raw) {
        delete_user_meta($user_id, 'nasim_mobile');
    } elseif (!$mobile) {
        return;
    } elseif (!$owner || (int) $owner->ID === (int) $user_id) {
        update_user_meta($user_id, 'nasim_mobile', $mobile);
    }
}
add_action('personal_options_update', 'nasim_save_user_mobile');
add_action('edit_user_profile_update', 'nasim_save_user_mobile');


function nasim_users_mobile_column($columns) {

    $columns['nasim_mobile'] = __('موبایل', 'niloo-web');

    return $columns;
}
add_filter('manage_users_columns', 'nasim_users_mobile_column');


function nasim_users_mobile_column_content($output, $column, $user_id) {

    if ('nasim_mobile' !== $column) {
        return $output;
    }

    $mobile = nasim_get_user_mobile($user_id);

    return $mobile ? '<span dir="ltr">' . esc_html($mobile) . '</span>' : '—';
}
add_filter('manage_users_custom_column', 'nasim_users_mobile_column_content', 10, 3);
