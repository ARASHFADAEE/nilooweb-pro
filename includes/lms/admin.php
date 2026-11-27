<?php

if(!defined('ABSPATH')) exit;



/**
 * Payments list and gateway settings pages under the courses menu
 */
function nasim_lms_admin_menu() {

    add_submenu_page('edit.php?post_type=nasim_course', __('تراکنش‌ها', 'niloo-web'), __('تراکنش‌ها', 'niloo-web'), 'manage_options', 'nasim-payments', 'nasim_render_payments_page');
    add_submenu_page(
        'edit.php?post_type=nasim_course',
        __('تنظیمات پرداخت', 'niloo-web'),
        __('تنظیمات پرداخت', 'niloo-web'),
        'manage_options',
        'nasim-payment-settings',
        static function () {
            wp_safe_redirect(admin_url('admin.php?page=niloo_theme_settings&mino_tab=payment'));
            exit;
        }
    );
}
add_action('admin_menu', 'nasim_lms_admin_menu');


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
        <h1><?php esc_html_e('تراکنش‌ها', 'niloo-web'); ?></h1>

        <ul class="subsubsub">
            <li><a href="<?php echo esc_url($base_url); ?>" class="<?php echo $status ? '' : 'current'; ?>"><?php esc_html_e('همه', 'niloo-web'); ?></a> | </li>
            <?php foreach ($statuses as $key => $label) : ?>
            <li><a href="<?php echo esc_url(add_query_arg('status', $key, $base_url)); ?>" class="<?php echo $status === $key ? 'current' : ''; ?>"><?php echo esc_html($label); ?></a><?php echo 'failed' !== $key ? ' | ' : ''; ?></li>
            <?php endforeach; ?>
        </ul>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:60px">#</th>
                    <th><?php esc_html_e('کاربر', 'niloo-web'); ?></th>
                    <th><?php esc_html_e('پلن', 'niloo-web'); ?></th>
                    <th><?php esc_html_e('مبلغ', 'niloo-web'); ?></th>
                    <th><?php esc_html_e('وضعیت', 'niloo-web'); ?></th>
                    <th><?php esc_html_e('درگاه', 'niloo-web'); ?></th>
                    <th><?php esc_html_e('کد پیگیری', 'niloo-web'); ?></th>
                    <th><?php esc_html_e('تاریخ', 'niloo-web'); ?></th>
                    <th><?php esc_html_e('توضیح', 'niloo-web'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$result['items']) : ?>
                <tr><td colspan="9"><?php esc_html_e('تراکنشی ثبت نشده است.', 'niloo-web'); ?></td></tr>
            <?php endif; ?>
            <?php
            $gw_labels = function_exists('nasim_payment_gateway_labels') ? nasim_payment_gateway_labels() : [];
            foreach ($result['items'] as $payment) :
                $user = get_userdata($payment->user_id);
                $gw   = !empty($payment->gateway) ? (string) $payment->gateway : 'zibal';
                ?>
                <tr>
                    <td><?php echo esc_html($payment->id); ?></td>
                    <td><?php if ($user) : ?><a href="<?php echo esc_url(get_edit_user_link($user->ID)); ?>"><?php echo esc_html($user->display_name); ?></a><?php else : ?>—<?php endif; ?></td>
                    <td><?php echo esc_html($payment->plan_title); ?></td>
                    <td><?php echo esc_html(nasim_format_price((int) $payment->amount / 10)); ?></td>
                    <td><?php echo esc_html($statuses[$payment->status] ?? $payment->status); ?></td>
                    <td><?php echo esc_html($gw_labels[$gw] ?? $gw); ?></td>
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
