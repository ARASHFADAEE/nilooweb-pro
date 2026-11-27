<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * User panel (/subscription/) helpers: navigation, learning data, profile settings.
 */


/**
 * Panel navigation items for the current user
 *
 * @return array<string, array{label:string, icon:string, badge?:int}>
 */
function nasim_panel_tabs($user_id)
{
    $tabs = [
        'overview' => ['label' => 'پیشخوان', 'icon' => 'home'],
        'courses'  => ['label' => 'دوره‌های من', 'icon' => 'courses'],
    ];

    if (function_exists('nasim_user_spot_licenses')) {
        $tabs['spot'] = ['label' => 'اسپات‌پلیر', 'icon' => 'products'];
    }

    if (function_exists('nasim_ticket_counts')) {
        $counts          = nasim_ticket_counts($user_id);
        $tabs['tickets'] = ['label' => 'پشتیبانی', 'icon' => 'articles', 'badge' => (int) ($counts['answered'] ?? 0)];
    }

    $tabs['payments'] = ['label' => 'تراکنش‌ها', 'icon' => 'check'];
    $tabs['profile']  = ['label' => 'تنظیمات حساب', 'icon' => 'user'];

    return apply_filters('nasim_panel_tabs', $tabs, $user_id);
}


/**
 * Courses the user is learning: purchased + free courses with progress, most recently active first
 *
 * @return array<int, array{post:WP_Post, progress:array, next:?array, owned:bool}>
 */
function nasim_panel_learning_courses($user_id)
{
    global $wpdb;

    $owned = array_map('intval', nasim_get_course_grants($user_id));

    // Free courses the user has started (progress meta exists).
    $keys = $wpdb->get_col($wpdb->prepare(
        "SELECT meta_key FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE %s",
        $user_id,
        $wpdb->esc_like('_nasim_progress_') . '%'
    ));

    $started = [];
    foreach ($keys as $key) {
        $started[] = (int) substr($key, strlen('_nasim_progress_'));
    }

    $ids = array_values(array_unique(array_filter(array_merge($owned, $started))));

    if (!$ids) {
        return [];
    }

    $posts = get_posts([
        'post_type'      => 'nasim_course',
        'post_status'    => 'publish',
        'post__in'       => $ids,
        'posts_per_page' => -1,
        'orderby'        => 'post__in',
        'no_found_rows'  => true,
    ]);

    $items = [];
    foreach ($posts as $post) {
        $progress = nasim_course_progress($post->ID, $user_id);
        $items[]  = [
            'post'     => $post,
            'progress' => $progress,
            'next'     => nasim_next_lesson_to_watch($post->ID, $user_id),
            'owned'    => in_array((int) $post->ID, $owned, true),
            'active'   => (int) get_user_meta($user_id, '_nasim_last_active_' . $post->ID, true),
        ];
    }

    // In progress first (by last activity), then not started, then completed.
    usort($items, static function ($a, $b) {
        $rank = static function ($item) {
            $p = $item['progress']['percent'];
            return $p >= 100 ? 2 : ($p > 0 ? 0 : 1);
        };

        return [$rank($a), -$a['active']] <=> [$rank($b), -$b['active']];
    });

    return $items;
}


/**
 * Remember when a user last opened a course (powers "continue learning")
 */
function nasim_panel_track_course_activity()
{
    if (!is_user_logged_in() || !function_exists('nasim_is_course_player') || !nasim_is_course_player()) {
        return;
    }

    update_user_meta(get_current_user_id(), '_nasim_last_active_' . get_queried_object_id(), time());
}
add_action('template_redirect', 'nasim_panel_track_course_activity', 20);


/**
 * Payment status → tone class
 */
function nasim_panel_status_tone($status)
{
    return ['paid' => 'ok', 'manual' => 'info', 'pending' => 'warn', 'failed' => 'err'][$status] ?? 'info';
}


/**
 * Guests opening the bare panel go straight to login (checkout links keep their guest page)
 */
function nasim_panel_guest_redirect()
{
    if (!function_exists('nasim_is_subscription_page') || !nasim_is_subscription_page() || is_user_logged_in()) {
        return;
    }

    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- routing only.
    if (!empty($_GET['highlight']) || !empty($_GET['course']) || !empty($_GET['buy']) || !empty($_GET['payment'])) {
        return;
    }
    // phpcs:enable

    wp_safe_redirect(nasim_auth_url('login', nasim_subscription_url()));
    exit;
}
add_action('template_redirect', 'nasim_panel_guest_redirect', 5);


/* -------------------------------------------------------------------------
 * Profile settings
 * ---------------------------------------------------------------------- */

function nasim_handle_profile_update()
{
    if (!is_user_logged_in()) {
        wp_safe_redirect(nasim_auth_url('login', nasim_subscription_url(['tab' => 'profile'])));
        exit;
    }

    check_admin_referer('nasim_profile_update');

    $user_id = get_current_user_id();
    $back    = nasim_subscription_url(['tab' => 'profile']);
    $section = sanitize_key($_POST['section'] ?? 'info');

    if ('password' === $section) {
        $pass    = (string) wp_unslash($_POST['new_password'] ?? '');
        $confirm = (string) wp_unslash($_POST['confirm_password'] ?? '');

        if (strlen($pass) < 8) {
            wp_safe_redirect(add_query_arg('profile', 'pass_short', $back));
            exit;
        }

        if ($pass !== $confirm) {
            wp_safe_redirect(add_query_arg('profile', 'pass_mismatch', $back));
            exit;
        }

        wp_set_password($pass, $user_id);
        // wp_set_password() drops the session; sign the user straight back in.
        nasim_login_user(get_userdata($user_id));

        wp_safe_redirect(add_query_arg('profile', 'pass_ok', $back));
        exit;
    }

    $name  = trim(sanitize_text_field(wp_unslash($_POST['display_name'] ?? '')));
    $email = trim(sanitize_email(wp_unslash($_POST['email'] ?? '')));

    if (mb_strlen($name) < 2 || mb_strlen($name) > 60) {
        wp_safe_redirect(add_query_arg('profile', 'name', $back));
        exit;
    }

    if ('' !== $email) {
        $owner = email_exists($email);
        if (!is_email($email) || ($owner && (int) $owner !== $user_id)) {
            wp_safe_redirect(add_query_arg('profile', 'email', $back));
            exit;
        }
    }

    $update = [
        'ID'           => $user_id,
        'display_name' => $name,
        'nickname'     => $name,
        'first_name'   => $name,
    ];

    // Accounts created by mobile have no email; WordPress needs a unique one, keep the placeholder empty.
    if ('' !== $email) {
        $update['user_email'] = $email;
    }

    $result = wp_update_user($update);

    wp_safe_redirect(add_query_arg('profile', is_wp_error($result) ? 'error' : 'ok', $back));
    exit;
}
add_action('admin_post_nasim_profile_update', 'nasim_handle_profile_update');


/**
 * Messages shown after a profile update
 *
 * @return array<string, array{0:string,1:string}>
 */
function nasim_panel_profile_messages()
{
    return [
        'ok'            => ['ok', 'اطلاعات حسابت ذخیره شد.'],
        'pass_ok'       => ['ok', 'رمز عبور تازه ذخیره شد؛ از این به بعد با موبایل و همین رمز هم می‌توانی وارد شوی.'],
        'name'          => ['err', 'نام باید بین ۲ تا ۶۰ حرف باشد.'],
        'email'         => ['err', 'ایمیل نامعتبر است یا برای حساب دیگری ثبت شده.'],
        'pass_short'    => ['err', 'رمز عبور باید دست‌کم ۸ کاراکتر باشد.'],
        'pass_mismatch' => ['err', 'تکرار رمز عبور با خود رمز یکی نیست.'],
        'error'         => ['err', 'ذخیره ناموفق بود؛ دوباره تلاش کن.'],
    ];
}
