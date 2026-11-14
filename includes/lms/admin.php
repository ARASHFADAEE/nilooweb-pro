<?php

if(!defined('ABSPATH')) exit;



/**
 * Payments list and gateway settings pages under the courses menu
 */
function nasim_lms_admin_menu() {

    add_submenu_page('edit.php?post_type=nasim_course', __('تراکنش‌ها', 'nasim-fitness'), __('تراکنش‌ها', 'nasim-fitness'), 'manage_options', 'nasim-payments', 'nasim_render_payments_page');
    add_submenu_page('edit.php?post_type=nasim_course', __('تنظیمات پرداخت', 'nasim-fitness'), __('تنظیمات پرداخت', 'nasim-fitness'), 'manage_options', 'nasim-payment-settings', 'nasim_render_payment_settings_page');
}
add_action('admin_menu', 'nasim_lms_admin_menu');


function nasim_register_payment_settings() {

    register_setting('nasim_payment_settings', 'nasim_zibal_merchant', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '',
    ]);

    register_setting('nasim_payment_settings', 'nasim_zibal_sandbox', [
        'type'              => 'boolean',
        'sanitize_callback' => function ($value) {
            return $value ? 1 : 0;
        },
        'default'           => 0,
    ]);
}
add_action('admin_init', 'nasim_register_payment_settings');


function nasim_render_payment_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('تنظیمات درگاه زیبال', 'nasim-fitness'); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('nasim_payment_settings'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="nasim_zibal_merchant"><?php esc_html_e('کد مرچنت زیبال', 'nasim-fitness'); ?></label></th>
                    <td><input type="text" id="nasim_zibal_merchant" name="nasim_zibal_merchant" value="<?php echo esc_attr(get_option('nasim_zibal_merchant')); ?>" class="regular-text" dir="ltr"></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('حالت آزمایشی', 'nasim-fitness'); ?></th>
                    <td><label><input type="checkbox" name="nasim_zibal_sandbox" value="1" <?php checked(get_option('nasim_zibal_sandbox')); ?>> <?php esc_html_e('استفاده از مرچنت تست زیبال (پرداخت واقعی انجام نمی‌شود)', 'nasim-fitness'); ?></label></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('آدرس بازگشت', 'nasim-fitness'); ?></th>
                    <td><code dir="ltr"><?php echo esc_html(admin_url('admin-post.php?action=nasim_zibal_callback')); ?></code>
                        <p class="description"><?php esc_html_e('دامنه این آدرس باید با دامنه ثبت‌شده در پنل زیبال یکی باشد.', 'nasim-fitness'); ?></p></td>
                </tr>
                <?php do_action('nasim_payment_settings_after_gateway'); ?>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}


function nasim_render_payments_page() {

    $statuses = nasim_payment_statuses();
    // phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only filters.
    $status   = isset($_GET['status'], $statuses[$_GET['status']]) ? sanitize_key($_GET['status']) : '';
    $page     = max(1, absint($_GET['paged'] ?? 1));
    // phpcs:enable
    $per_page = 30;
    $result   = nasim_get_payments($page, $per_page, $status);
    $base_url = admin_url('edit.php?post_type=nasim_course&page=nasim-payments');
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('تراکنش‌ها', 'nasim-fitness'); ?></h1>

        <ul class="subsubsub">
            <li><a href="<?php echo esc_url($base_url); ?>" class="<?php echo $status ? '' : 'current'; ?>"><?php esc_html_e('همه', 'nasim-fitness'); ?></a> | </li>
            <?php foreach ($statuses as $key => $label) : ?>
            <li><a href="<?php echo esc_url(add_query_arg('status', $key, $base_url)); ?>" class="<?php echo $status === $key ? 'current' : ''; ?>"><?php echo esc_html($label); ?></a><?php echo 'failed' !== $key ? ' | ' : ''; ?></li>
            <?php endforeach; ?>
        </ul>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:60px">#</th>
                    <th><?php esc_html_e('کاربر', 'nasim-fitness'); ?></th>
                    <th><?php esc_html_e('پلن', 'nasim-fitness'); ?></th>
                    <th><?php esc_html_e('مبلغ', 'nasim-fitness'); ?></th>
                    <th><?php esc_html_e('وضعیت', 'nasim-fitness'); ?></th>
                    <th><?php esc_html_e('کد پیگیری', 'nasim-fitness'); ?></th>
                    <th><?php esc_html_e('تاریخ', 'nasim-fitness'); ?></th>
                    <th><?php esc_html_e('توضیح', 'nasim-fitness'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$result['items']) : ?>
                <tr><td colspan="8"><?php esc_html_e('تراکنشی ثبت نشده است.', 'nasim-fitness'); ?></td></tr>
            <?php endif; ?>
            <?php foreach ($result['items'] as $payment) :
                $user = get_userdata($payment->user_id);
                ?>
                <tr>
                    <td><?php echo esc_html($payment->id); ?></td>
                    <td><?php if ($user) : ?><a href="<?php echo esc_url(get_edit_user_link($user->ID)); ?>"><?php echo esc_html($user->display_name); ?></a><?php else : ?>—<?php endif; ?></td>
                    <td><?php echo esc_html($payment->plan_title); ?></td>
                    <td><?php echo esc_html(nasim_format_price((int) $payment->amount / 10)); ?></td>
                    <td><?php echo esc_html($statuses[$payment->status] ?? $payment->status); ?></td>
                    <td dir="ltr"><?php echo esc_html($payment->ref_number ?: '—'); ?></td>
                    <td><?php echo esc_html(nasim_jdate(nasim_payment_time($payment->created_at), true)); ?></td>
                    <td><?php echo esc_html($payment->note); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        $pagination = paginate_links([
            'base'    => add_query_arg('paged', '%#%', $status ? add_query_arg('status', $status, $base_url) : $base_url),
            'format'  => '',
            'current' => $page,
            'total'   => (int) ceil($result['total'] / $per_page),
        ]);

        if ($pagination) {
            echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post($pagination) . '</div></div>';
        }
        ?>
    </div>
    <?php
}
