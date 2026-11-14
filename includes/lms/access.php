<?php

if(!defined('ABSPATH')) exit;



/**
 * Courses granted to a user permanently by an admin (independent of the subscription)
 */
function nasim_get_course_grants($user_id) {

    $grants = get_user_meta($user_id, '_nasim_course_access', true);

    return is_array($grants) ? array_values(array_map('intval', $grants)) : [];
}


function nasim_grant_course_access($user_id, $course_id) {

    $grants = nasim_get_course_grants($user_id);

    if (in_array((int) $course_id, $grants, true)) {
        return false;
    }

    $grants[] = (int) $course_id;
    update_user_meta($user_id, '_nasim_course_access', $grants);

    return true;
}


function nasim_revoke_course_access($user_id, $course_id) {

    $grants = array_values(array_diff(nasim_get_course_grants($user_id), [(int) $course_id]));

    // Delete instead of saving an empty array so "has any grant" meta queries stay accurate.
    if ($grants) {
        update_user_meta($user_id, '_nasim_course_access', $grants);
    } else {
        delete_user_meta($user_id, '_nasim_course_access');
    }
}


/**
 * Grant or extend a subscription by an admin and log it
 */
function nasim_admin_grant_subscription($user_id, $days, $plan_id = 0, $note = '') {

    $plan  = $plan_id ? nasim_get_plan($plan_id) : null;
    $title = $plan ? 'اشتراک دستی: ' . $plan['title'] : 'اشتراک دستی';

    $expires_at = nasim_extend_subscription($user_id, $days, $plan_id);
    nasim_log_manual_access($user_id, $title, $days, $note, $plan_id);

    return $expires_at;
}


function nasim_admin_revoke_subscription($user_id, $note = '') {

    update_user_meta($user_id, '_nasim_subscription_expires', time());
    nasim_log_manual_access($user_id, 'لغو اشتراک', 0, $note);
}


/**
 * Find a user by ID, email or username
 */
function nasim_find_user($identifier) {

    $identifier = trim((string) $identifier);

    if ('' === $identifier) {
        return false;
    }

    $by_mobile = nasim_get_user_by_mobile($identifier);

    if ($by_mobile) {
        return $by_mobile;
    }

    if (ctype_digit(nasim_latin_digits($identifier))) {
        return get_user_by('id', (int) nasim_latin_digits($identifier));
    }

    if (is_email($identifier)) {
        return get_user_by('email', $identifier);
    }

    return get_user_by('login', $identifier);
}


function nasim_access_page_url($args = []) {

    return add_query_arg($args, admin_url('edit.php?post_type=nasim_course&page=nasim-access'));
}


function nasim_access_admin_menu() {

    add_submenu_page('edit.php?post_type=nasim_course', __('مدیریت دسترسی', 'nasim-fitness'), __('مدیریت دسترسی', 'nasim-fitness'), 'edit_users', 'nasim-access', 'nasim_render_access_page');
}
add_action('admin_menu', 'nasim_access_admin_menu');


function nasim_render_access_page() {

    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only screen state after redirects.
    $notice      = sanitize_key($_GET['nasim_notice'] ?? '');
    $prefill     = isset($_GET['user_id']) ? get_userdata(absint($_GET['user_id'])) : false;
    $search      = sanitize_text_field(wp_unslash($_GET['s'] ?? ''));
    $page        = max(1, absint($_GET['paged'] ?? 1));
    // phpcs:enable
    $per_page    = 30;
    $plans       = nasim_get_plans();
    $courses     = get_posts(['post_type' => 'nasim_course', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'title', 'order' => 'ASC']);
    $course_list = wp_list_pluck($courses, 'post_title', 'ID');

    $user_query = new WP_User_Query([
        'number'         => $per_page,
        'paged'          => $page,
        'search'         => $search ? '*' . $search . '*' : '',
        'search_columns' => ['user_login', 'user_email', 'display_name'],
        'meta_query'     => [
            'relation' => 'OR',
            ['key' => '_nasim_subscription_expires', 'value' => time(), 'compare' => '>', 'type' => 'NUMERIC'],
            ['key' => '_nasim_course_access', 'compare' => 'EXISTS'],
        ],
    ]);

    $notices = [
        'granted' => ['success', 'دسترسی با موفقیت اضافه شد.'],
        'revoked' => ['success', 'دسترسی لغو شد.'],
        'user'    => ['error', 'کاربری با این مشخصات پیدا نشد.'],
        'days'    => ['error', 'مدت اشتراک را انتخاب یا وارد کنید.'],
        'courses' => ['error', 'حداقل یک دوره را انتخاب کنید.'],
    ];
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('مدیریت دسترسی', 'nasim-fitness'); ?></h1>

        <?php if (isset($notices[$notice])) : ?>
        <div class="notice notice-<?php echo esc_attr($notices[$notice][0]); ?> is-dismissible"><p><?php echo esc_html($notices[$notice][1]); ?></p></div>
        <?php endif; ?>

        <div class="card" style="max-width:760px">
            <h2><?php esc_html_e('افزودن دسترسی دستی', 'nasim-fitness'); ?></h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="nasim_grant_access">
                <?php wp_nonce_field('nasim_grant_access'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th><label for="nasim_user"><?php esc_html_e('کاربر', 'nasim-fitness'); ?></label></th>
                        <td><input type="text" id="nasim_user" name="nasim_user" class="regular-text" dir="ltr" required value="<?php echo esc_attr($prefill ? $prefill->user_login : ''); ?>" placeholder="email / username / ID">
                            <p class="description"><?php esc_html_e('ایمیل، نام کاربری یا شناسه عددی کاربر.', 'nasim-fitness'); ?></p></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('نوع دسترسی', 'nasim-fitness'); ?></th>
                        <td>
                            <label><input type="radio" name="nasim_access_type" value="subscription" checked> <?php esc_html_e('اشتراک (همه دوره‌ها، برای مدت مشخص)', 'nasim-fitness'); ?></label><br>
                            <label><input type="radio" name="nasim_access_type" value="courses"> <?php esc_html_e('دوره‌های مشخص (دائمی، بدون نیاز به اشتراک)', 'nasim-fitness'); ?></label>
                        </td>
                    </tr>
                    <tr class="nasim-access-subscription">
                        <th><label for="nasim_plan_id"><?php esc_html_e('مدت اشتراک', 'nasim-fitness'); ?></label></th>
                        <td>
                            <select id="nasim_plan_id" name="nasim_plan_id">
                                <?php foreach ($plans as $plan) : ?>
                                <option value="<?php echo esc_attr($plan['id']); ?>"><?php echo esc_html($plan['title'] . ' (' . nasim_fa_digits($plan['days']) . ' روز)'); ?></option>
                                <?php endforeach; ?>
                                <option value="0"><?php esc_html_e('تعداد روز دلخواه', 'nasim-fitness'); ?></option>
                            </select>
                            <input type="number" min="1" max="3650" name="nasim_days" class="small-text" dir="ltr" placeholder="30">
                            <p class="description"><?php esc_html_e('اگر کاربر اشتراک فعال داشته باشد، مدت به انتهای آن اضافه می‌شود.', 'nasim-fitness'); ?></p>
                        </td>
                    </tr>
                    <tr class="nasim-access-courses">
                        <th><?php esc_html_e('دوره‌ها', 'nasim-fitness'); ?></th>
                        <td>
                            <?php if (!$course_list) : ?>
                                <em><?php esc_html_e('هنوز دوره‌ای منتشر نشده است.', 'nasim-fitness'); ?></em>
                            <?php endif; ?>
                            <?php foreach ($course_list as $course_id => $course_title) : ?>
                            <label style="display:block;margin-bottom:4px"><input type="checkbox" name="nasim_courses[]" value="<?php echo esc_attr($course_id); ?>"> <?php echo esc_html($course_title); ?></label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="nasim_note"><?php esc_html_e('توضیح (اختیاری)', 'nasim-fitness'); ?></label></th>
                        <td><input type="text" id="nasim_note" name="nasim_note" class="regular-text" placeholder="<?php esc_attr_e('مثلاً: پرداخت کارت‌به‌کارت، هدیه، جبران اختلال', 'nasim-fitness'); ?>"></td>
                    </tr>
                </table>
                <?php submit_button(__('افزودن دسترسی', 'nasim-fitness')); ?>
            </form>
        </div>

        <h2 style="margin-top:30px"><?php esc_html_e('کاربران دارای دسترسی', 'nasim-fitness'); ?></h2>

        <form method="get" style="margin-bottom:10px">
            <input type="hidden" name="post_type" value="nasim_course">
            <input type="hidden" name="page" value="nasim-access">
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('جستجوی کاربر...', 'nasim-fitness'); ?>">
            <?php submit_button(__('جستجو', 'nasim-fitness'), 'secondary', '', false); ?>
        </form>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('کاربر', 'nasim-fitness'); ?></th>
                    <th><?php esc_html_e('اشتراک', 'nasim-fitness'); ?></th>
                    <th><?php esc_html_e('دوره‌های اختصاصی', 'nasim-fitness'); ?></th>
                    <th style="width:180px"><?php esc_html_e('عملیات', 'nasim-fitness'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$user_query->get_results()) : ?>
                <tr><td colspan="4"><?php esc_html_e('کاربری با دسترسی فعال پیدا نشد.', 'nasim-fitness'); ?></td></tr>
            <?php endif; ?>
            <?php foreach ($user_query->get_results() as $user) :
                $grants = nasim_get_course_grants($user->ID);
                ?>
                <tr>
                    <td>
                        <strong><a href="<?php echo esc_url(get_edit_user_link($user->ID)); ?>"><?php echo esc_html($user->display_name); ?></a></strong><br>
                        <small dir="ltr"><?php echo esc_html($user->user_email); ?></small>
                    </td>
                    <td>
                        <?php if (nasim_has_active_subscription($user->ID)) : ?>
                            <?php echo esc_html(sprintf('تا %s (%s روز)', nasim_jdate(nasim_subscription_expires($user->ID)), nasim_fa_digits(nasim_subscription_days_left($user->ID)))); ?><br>
                            <a class="submitdelete" href="<?php echo esc_url(nasim_revoke_access_url($user->ID, 'subscription')); ?>" onclick="return confirm('<?php echo esc_js('اشتراک این کاربر لغو شود؟'); ?>')"><?php esc_html_e('لغو اشتراک', 'nasim-fitness'); ?></a>
                        <?php else : ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$grants) : ?>—<?php endif; ?>
                        <?php foreach ($grants as $course_id) : ?>
                            <div>
                                <?php echo esc_html(get_the_title($course_id) ?: '#' . $course_id); ?>
                                <a class="submitdelete" href="<?php echo esc_url(nasim_revoke_access_url($user->ID, 'course', $course_id)); ?>" onclick="return confirm('<?php echo esc_js('دسترسی به این دوره لغو شود؟'); ?>')" aria-label="<?php esc_attr_e('لغو دسترسی دوره', 'nasim-fitness'); ?>">✕</a>
                            </div>
                        <?php endforeach; ?>
                    </td>
                    <td><a class="button button-small" href="<?php echo esc_url(nasim_access_page_url(['user_id' => $user->ID])); ?>"><?php esc_html_e('افزودن دسترسی', 'nasim-fitness'); ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        $pagination = paginate_links([
            'base'    => add_query_arg('paged', '%#%', nasim_access_page_url($search ? ['s' => $search] : [])),
            'format'  => '',
            'current' => $page,
            'total'   => (int) ceil($user_query->get_total() / $per_page),
        ]);

        if ($pagination) {
            echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post($pagination) . '</div></div>';
        }
        ?>
    </div>

    <script>
    (() => {
        const radios = document.querySelectorAll('[name="nasim_access_type"]');
        const toggle = () => {
            const type = document.querySelector('[name="nasim_access_type"]:checked').value;
            document.querySelector('.nasim-access-subscription').hidden = type !== 'subscription';
            document.querySelector('.nasim-access-courses').hidden = type !== 'courses';
        };
        radios.forEach(radio => radio.addEventListener('change', toggle));
        toggle();

        const planSelect = document.getElementById('nasim_plan_id');
        const daysInput = document.querySelector('[name="nasim_days"]');
        const toggleDays = () => { daysInput.hidden = planSelect.value !== '0'; };
        planSelect.addEventListener('change', toggleDays);
        toggleDays();
    })();
    </script>
    <?php
}


function nasim_revoke_access_url($user_id, $type, $course_id = 0) {

    return wp_nonce_url(add_query_arg([
        'action'    => 'nasim_revoke_access',
        'user_id'   => (int) $user_id,
        'type'      => $type,
        'course_id' => (int) $course_id,
    ], admin_url('admin-post.php')), 'nasim_revoke_access_' . (int) $user_id);
}


function nasim_handle_grant_access() {

    if (!current_user_can('edit_users')) {
        wp_die(esc_html__('دسترسی غیرمجاز.', 'nasim-fitness'), 403);
    }

    check_admin_referer('nasim_grant_access');

    $user = nasim_find_user(sanitize_text_field(wp_unslash($_POST['nasim_user'] ?? '')));
    $note = sanitize_text_field(wp_unslash($_POST['nasim_note'] ?? ''));
    $type = sanitize_key($_POST['nasim_access_type'] ?? 'subscription');

    if (!$user) {
        wp_safe_redirect(nasim_access_page_url(['nasim_notice' => 'user']));
        exit;
    }

    $back = ['user_id' => $user->ID];

    if ('courses' === $type) {
        $course_ids = array_filter(array_map('absint', (array) ($_POST['nasim_courses'] ?? [])), function ($course_id) {
            return 'nasim_course' === get_post_type($course_id);
        });

        if (!$course_ids) {
            wp_safe_redirect(nasim_access_page_url($back + ['nasim_notice' => 'courses']));
            exit;
        }

        foreach ($course_ids as $course_id) {
            if (nasim_grant_course_access($user->ID, $course_id)) {
                nasim_log_manual_access($user->ID, 'دسترسی به دوره: ' . get_the_title($course_id), 0, $note);
            }
        }

        wp_safe_redirect(nasim_access_page_url($back + ['nasim_notice' => 'granted']));
        exit;
    }

    $plan = nasim_get_plan(absint($_POST['nasim_plan_id'] ?? 0));
    $days = $plan ? $plan['days'] : absint(nasim_latin_digits(wp_unslash($_POST['nasim_days'] ?? 0)));

    if ($days < 1) {
        wp_safe_redirect(nasim_access_page_url($back + ['nasim_notice' => 'days']));
        exit;
    }

    nasim_admin_grant_subscription($user->ID, min($days, 3650), $plan ? $plan['id'] : 0, $note);

    wp_safe_redirect(nasim_access_page_url($back + ['nasim_notice' => 'granted']));
    exit;
}
add_action('admin_post_nasim_grant_access', 'nasim_handle_grant_access');


function nasim_handle_revoke_access() {

    $user_id = absint($_GET['user_id'] ?? 0);

    if (!current_user_can('edit_users')) {
        wp_die(esc_html__('دسترسی غیرمجاز.', 'nasim-fitness'), 403);
    }

    check_admin_referer('nasim_revoke_access_' . $user_id);

    if (!get_userdata($user_id)) {
        wp_safe_redirect(nasim_access_page_url(['nasim_notice' => 'user']));
        exit;
    }

    $type = sanitize_key($_GET['type'] ?? '');

    if ('subscription' === $type) {
        nasim_admin_revoke_subscription($user_id);
    }

    if ('course' === $type) {
        $course_id = absint($_GET['course_id'] ?? 0);
        nasim_revoke_course_access($user_id, $course_id);
        nasim_log_manual_access($user_id, 'لغو دسترسی دوره: ' . get_the_title($course_id));
    }

    $back = remove_query_arg(['nasim_notice', 'user_id'], wp_get_referer() ?: nasim_access_page_url());

    wp_safe_redirect(add_query_arg('nasim_notice', 'revoked', $back));
    exit;
}
add_action('admin_post_nasim_revoke_access', 'nasim_handle_revoke_access');


/**
 * "Access" column and row action in the users list
 */
function nasim_users_access_column($columns) {

    $columns['nasim_access'] = __('دسترسی دوره‌ها', 'nasim-fitness');

    return $columns;
}
add_filter('manage_users_columns', 'nasim_users_access_column');


function nasim_users_access_column_content($output, $column, $user_id) {

    if ('nasim_access' !== $column) {
        return $output;
    }

    $parts = [];

    if (nasim_has_active_subscription($user_id)) {
        $parts[] = 'اشتراک تا ' . nasim_jdate(nasim_subscription_expires($user_id));
    }

    $grants = count(nasim_get_course_grants($user_id));

    if ($grants) {
        $parts[] = nasim_fa_digits($grants) . ' دوره اختصاصی';
    }

    return esc_html($parts ? implode(' · ', $parts) : '—');
}
add_filter('manage_users_custom_column', 'nasim_users_access_column_content', 10, 3);


function nasim_users_access_row_action($actions, $user) {

    if (current_user_can('edit_users')) {
        $actions['nasim_access'] = '<a href="' . esc_url(nasim_access_page_url(['user_id' => $user->ID])) . '">' . esc_html__('افزودن دسترسی', 'nasim-fitness') . '</a>';
    }

    return $actions;
}
add_filter('user_row_actions', 'nasim_users_access_row_action', 10, 2);


/**
 * Access summary and quick actions on the user profile (admins only)
 */
function nasim_user_access_fields($user) {

    if (!current_user_can('edit_users')) {
        return;
    }

    $expires = nasim_subscription_expires($user->ID);
    $grants  = nasim_get_course_grants($user->ID);
    ?>
    <h2><?php esc_html_e('دسترسی دوره‌ها', 'nasim-fitness'); ?></h2>
    <table class="form-table" role="presentation">
        <tr>
            <th><?php esc_html_e('اشتراک', 'nasim-fitness'); ?></th>
            <td>
                <?php if ($expires > time()) : ?>
                    <?php echo esc_html(sprintf('فعال تا %s (%s روز باقی‌مانده)', nasim_jdate($expires), nasim_fa_digits(nasim_subscription_days_left($user->ID)))); ?>
                <?php elseif ($expires) : ?>
                    <?php echo esc_html(sprintf('منقضی شده در %s', nasim_jdate($expires))); ?>
                <?php else : ?>
                    <?php esc_html_e('بدون اشتراک', 'nasim-fitness'); ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th><?php esc_html_e('دوره‌های اختصاصی', 'nasim-fitness'); ?></th>
            <td><?php echo esc_html($grants ? implode('، ', array_map('get_the_title', $grants)) : '—'); ?></td>
        </tr>
        <tr>
            <th><label for="nasim_add_days"><?php esc_html_e('افزودن روز اشتراک', 'nasim-fitness'); ?></label></th>
            <td><input type="number" min="1" max="3650" id="nasim_add_days" name="nasim_add_days" class="small-text" dir="ltr">
                <?php if ($expires > time()) : ?>
                <label style="margin-inline-start:16px"><input type="checkbox" name="nasim_cancel_subscription" value="1"> <?php esc_html_e('لغو اشتراک', 'nasim-fitness'); ?></label>
                <?php endif; ?>
                <p class="description">
                    <a href="<?php echo esc_url(nasim_access_page_url(['user_id' => $user->ID])); ?>"><?php esc_html_e('مدیریت کامل دسترسی (اشتراک با پلن یا دسترسی به دوره‌های مشخص) ←', 'nasim-fitness'); ?></a>
                </p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'nasim_user_access_fields');
add_action('edit_user_profile', 'nasim_user_access_fields');


/**
 * Core already verified the "update-user_{id}" nonce before these hooks run
 */
function nasim_save_user_access_fields($user_id) {

    if (!current_user_can('edit_users')) {
        return;
    }

    if (!empty($_POST['nasim_cancel_subscription'])) {
        nasim_admin_revoke_subscription($user_id);
        return;
    }

    $days = isset($_POST['nasim_add_days']) ? absint(nasim_latin_digits(wp_unslash($_POST['nasim_add_days']))) : 0;

    if ($days > 0) {
        nasim_admin_grant_subscription($user_id, min($days, 3650));
    }
}
add_action('personal_options_update', 'nasim_save_user_access_fields');
add_action('edit_user_profile_update', 'nasim_save_user_access_fields');
