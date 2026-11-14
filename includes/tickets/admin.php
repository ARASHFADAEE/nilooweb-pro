<?php

if(!defined('ABSPATH')) exit;



function nasim_tickets_admin_menu() {

    $waiting = nasim_ticket_counts()['open'];
    $badge   = $waiting ? ' <span class="awaiting-mod"><span class="pending-count">' . (int) $waiting . '</span></span>' : '';

    add_menu_page(
        __('تیکت‌های پشتیبانی', 'nasim-fitness'),
        __('پشتیبانی', 'nasim-fitness') . $badge,
        'manage_options',
        'nasim-tickets',
        'nasim_render_tickets_admin_page',
        'dashicons-sos',
        7
    );
}
add_action('admin_menu', 'nasim_tickets_admin_menu');


function nasim_register_ticket_settings() {

    register_setting('nasim_ticket_settings', 'nasim_ticket_notify_email', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_email',
        'default'           => '',
    ]);
}
add_action('admin_init', 'nasim_register_ticket_settings');


function nasim_tickets_admin_styles() {

    $screen = get_current_screen();

    if (!$screen || 'toplevel_page_nasim-tickets' !== $screen->id) {
        return;
    }
    ?>
    <style>
        .nasim-tk-badge{display:inline-block;padding:2px 10px;border-radius:999px;font-size:11px;font-weight:600;background:#f0f0f1;color:#50575e}
        .nasim-tk-badge.is-open{background:#fcf0e3;color:#9a5b00}
        .nasim-tk-badge.is-answered{background:#e7f6ec;color:#19834a}
        .nasim-tk-badge.is-closed{background:#f0f0f1;color:#787c82}
        .nasim-tk-badge.is-high{background:#fde8e8;color:#b42318}
        .nasim-tk-layout{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:20px;margin-top:16px;align-items:start}
        .nasim-tk-thread{display:grid;gap:14px}
        .nasim-tk-msg{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:14px 16px}
        .nasim-tk-msg.is-staff{background:#f6f2ff;border-color:#d9ccff}
        .nasim-tk-msg header{display:flex;justify-content:space-between;gap:10px;margin-bottom:8px;font-size:12px;color:#646970}
        .nasim-tk-msg header strong{color:#1d2327;font-size:13px}
        .nasim-tk-body{white-space:pre-wrap;line-height:1.9;font-size:13px}
        .nasim-tk-file{display:inline-flex;gap:6px;align-items:center;margin-top:10px;padding:6px 10px;border-radius:6px;background:#f0f0f1;text-decoration:none}
        .nasim-tk-file img{display:block;max-width:220px;max-height:160px;border-radius:6px}
        .nasim-tk-side .card{margin:0 0 16px;max-width:none}
        .nasim-tk-side dl{display:grid;grid-template-columns:auto 1fr;gap:8px 12px;margin:0;font-size:13px}
        .nasim-tk-side dt{color:#646970}
        .nasim-tk-side dd{margin:0;font-weight:600;word-break:break-word}
        .nasim-tk-reply textarea{width:100%;min-height:160px}
        .nasim-tk-reply .row{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:10px}
        .nasim-tk-filters{display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin:14px 0}
        @media(max-width:960px){.nasim-tk-layout{grid-template-columns:1fr}}
    </style>
    <?php
}
add_action('admin_head', 'nasim_tickets_admin_styles');


function nasim_handle_admin_ticket_reply() {

    check_admin_referer('nasim_admin_ticket_reply');

    if (!current_user_can('manage_options')) {
        wp_die('', '', ['response' => 403]);
    }

    $ticket_id = absint($_POST['ticket_id'] ?? 0);
    $status    = sanitize_key(wp_unslash($_POST['status'] ?? 'answered'));
    $message   = wp_unslash($_POST['message'] ?? '');
    $args      = [];

    $attachment = nasim_ticket_handle_upload('attachment');

    if (is_wp_error($attachment)) {
        $args['tk_error'] = rawurlencode($attachment->get_error_message());
    } else {
        $reply = nasim_add_ticket_reply($ticket_id, get_current_user_id(), $message, true, $attachment, $status);
        $args  = is_wp_error($reply) ? ['tk_error' => rawurlencode($reply->get_error_message())] : ['tk_notice' => 'replied'];
    }

    wp_safe_redirect(add_query_arg($args, nasim_ticket_admin_url($ticket_id)));
    exit;
}
add_action('admin_post_nasim_admin_ticket_reply', 'nasim_handle_admin_ticket_reply');


function nasim_handle_admin_ticket_status() {

    check_admin_referer('nasim_admin_ticket_status');

    if (!current_user_can('manage_options')) {
        wp_die('', '', ['response' => 403]);
    }

    $ticket_id = absint($_POST['ticket_id'] ?? 0);

    nasim_set_ticket_status($ticket_id, sanitize_key(wp_unslash($_POST['status'] ?? '')));

    wp_safe_redirect(add_query_arg('tk_notice', 'status', nasim_ticket_admin_url($ticket_id)));
    exit;
}
add_action('admin_post_nasim_admin_ticket_status', 'nasim_handle_admin_ticket_status');


function nasim_render_tickets_admin_page() {

    $ticket_id = absint($_GET['ticket'] ?? 0); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    if ($ticket_id) {
        nasim_render_ticket_admin_view($ticket_id);
        return;
    }

    nasim_render_tickets_admin_list();
}


function nasim_render_tickets_admin_list() {

    // phpcs:disable WordPress.Security.NonceVerification.Recommended
    $page       = max(1, absint($_GET['paged'] ?? 1));
    $status     = sanitize_key(wp_unslash($_GET['status'] ?? ''));
    $department = sanitize_key(wp_unslash($_GET['department'] ?? ''));
    $search     = sanitize_text_field(wp_unslash($_GET['s'] ?? ''));
    // phpcs:enable

    $statuses    = nasim_ticket_statuses();
    $departments = nasim_ticket_departments();
    $priorities  = nasim_ticket_priorities();
    $counts      = nasim_ticket_counts();
    $result      = nasim_get_tickets_admin($page, 30, $status, $search, $department);
    $base        = nasim_ticket_admin_url();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e('تیکت‌های پشتیبانی', 'nasim-fitness'); ?></h1>
        <hr class="wp-header-end">

        <ul class="subsubsub">
            <li><a href="<?php echo esc_url($base); ?>" class="<?php echo $status ? '' : 'current'; ?>"><?php esc_html_e('همه', 'nasim-fitness'); ?> <span class="count">(<?php echo (int) array_sum($counts); ?>)</span></a> |</li>
            <?php $i = 0; foreach ($statuses as $key => $label) : $i++; ?>
            <li><a href="<?php echo esc_url(add_query_arg('status', $key, $base)); ?>" class="<?php echo $status === $key ? 'current' : ''; ?>"><?php echo esc_html($label); ?> <span class="count">(<?php echo (int) $counts[$key]; ?>)</span></a><?php echo $i < count($statuses) ? ' |' : ''; ?></li>
            <?php endforeach; ?>
        </ul>

        <form method="get" class="nasim-tk-filters" style="clear:both">
            <input type="hidden" name="page" value="nasim-tickets">
            <?php if ($status) : ?><input type="hidden" name="status" value="<?php echo esc_attr($status); ?>"><?php endif; ?>
            <select name="department">
                <option value=""><?php esc_html_e('همه دپارتمان‌ها', 'nasim-fitness'); ?></option>
                <?php foreach ($departments as $key => $label) : ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($department, $key); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('شماره، عنوان، کاربر یا دامنه…', 'nasim-fitness'); ?>">
            <?php submit_button(__('فیلتر', 'nasim-fitness'), 'secondary', '', false); ?>
        </form>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:60px">#</th>
                    <th><?php esc_html_e('عنوان', 'nasim-fitness'); ?></th>
                    <th><?php esc_html_e('کاربر', 'nasim-fitness'); ?></th>
                    <th><?php esc_html_e('دپارتمان', 'nasim-fitness'); ?></th>
                    <th style="width:80px"><?php esc_html_e('اولویت', 'nasim-fitness'); ?></th>
                    <th style="width:120px"><?php esc_html_e('وضعیت', 'nasim-fitness'); ?></th>
                    <th style="width:150px"><?php esc_html_e('آخرین به‌روزرسانی', 'nasim-fitness'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$result['items']) : ?>
                <tr><td colspan="7"><?php esc_html_e('تیکتی پیدا نشد.', 'nasim-fitness'); ?></td></tr>
            <?php endif; ?>
            <?php foreach ($result['items'] as $ticket) :
                $user = get_userdata($ticket->user_id);
                ?>
                <tr>
                    <td><?php echo esc_html((string) $ticket->id); ?></td>
                    <td>
                        <strong><a class="row-title" href="<?php echo esc_url(nasim_ticket_admin_url($ticket->id)); ?>"><?php echo esc_html($ticket->subject); ?></a></strong>
                        <?php if ($ticket->product_id) : ?><br><small><?php echo esc_html(get_the_title($ticket->product_id)); ?></small><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($user) : ?>
                            <?php echo esc_html($user->display_name); ?><br><small dir="ltr"><?php echo esc_html($user->user_login); ?></small>
                        <?php else : ?>—<?php endif; ?>
                    </td>
                    <td><?php echo esc_html(nasim_ticket_label($departments, $ticket->department)); ?></td>
                    <td><span class="nasim-tk-badge<?php echo 'high' === $ticket->priority ? ' is-high' : ''; ?>"><?php echo esc_html(nasim_ticket_label($priorities, $ticket->priority)); ?></span></td>
                    <td><span class="nasim-tk-badge is-<?php echo esc_attr($ticket->status); ?>"><?php echo esc_html(nasim_ticket_label($statuses, $ticket->status)); ?></span></td>
                    <td><?php echo esc_html(nasim_jdate(nasim_payment_time($ticket->updated_at), true)); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        $total_pages = (int) ceil($result['total'] / 30);
        if ($total_pages > 1) {
            echo '<div class="tablenav"><div class="tablenav-pages">';
            echo wp_kses_post(paginate_links([
                'base'      => add_query_arg('paged', '%#%', remove_query_arg('paged')),
                'format'    => '',
                'current'   => $page,
                'total'     => $total_pages,
                'prev_text' => '‹',
                'next_text' => '›',
            ]));
            echo '</div></div>';
        }
        ?>

        <form method="post" action="options.php" class="card" style="max-width:640px;margin-top:24px;padding:12px 20px">
            <?php settings_fields('nasim_ticket_settings'); ?>
            <h2><?php esc_html_e('اعلان ایمیلی', 'nasim-fitness'); ?></h2>
            <p class="description"><?php esc_html_e('تیکت جدید و پاسخ کاربران به این ایمیل اطلاع داده می‌شود. خالی = ایمیل مدیر سایت.', 'nasim-fitness'); ?></p>
            <p>
                <input type="email" name="nasim_ticket_notify_email" value="<?php echo esc_attr((string) get_option('nasim_ticket_notify_email', '')); ?>" class="regular-text" dir="ltr" placeholder="<?php echo esc_attr((string) get_option('admin_email')); ?>">
                <?php submit_button(__('ذخیره', 'nasim-fitness'), 'secondary', 'submit', false); ?>
            </p>
        </form>
    </div>
    <?php
}


function nasim_render_ticket_admin_view($ticket_id) {

    $ticket = nasim_get_ticket($ticket_id);

    if (!$ticket) {
        echo '<div class="wrap"><h1>' . esc_html__('تیکت پیدا نشد', 'nasim-fitness') . '</h1></div>';
        return;
    }

    $replies     = nasim_get_ticket_replies($ticket_id);
    $user        = get_userdata($ticket->user_id);
    $statuses    = nasim_ticket_statuses();
    $license     = function_exists('nasim_get_user_license') ? nasim_get_user_license($ticket->user_id) : null;
    $sub_active  = function_exists('nasim_has_active_subscription') && nasim_has_active_subscription((int) $ticket->user_id);
    $expires     = function_exists('nasim_subscription_expires') ? nasim_subscription_expires((int) $ticket->user_id) : 0;
    // phpcs:disable WordPress.Security.NonceVerification.Recommended
    $notice      = sanitize_key(wp_unslash($_GET['tk_notice'] ?? ''));
    $error       = sanitize_text_field(rawurldecode(wp_unslash($_GET['tk_error'] ?? '')));
    // phpcs:enable
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo esc_html(sprintf('#%d — %s', $ticket->id, $ticket->subject)); ?></h1>
        <a href="<?php echo esc_url(nasim_ticket_admin_url()); ?>" class="page-title-action"><?php esc_html_e('← همه تیکت‌ها', 'nasim-fitness'); ?></a>
        <hr class="wp-header-end">

        <?php if ('replied' === $notice) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('پاسخ ارسال شد.', 'nasim-fitness'); ?></p></div>
        <?php elseif ('status' === $notice) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('وضعیت تیکت به‌روزرسانی شد.', 'nasim-fitness'); ?></p></div>
        <?php endif; ?>
        <?php if ($error) : ?>
        <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
        <?php endif; ?>

        <div class="nasim-tk-layout">
            <div>
                <div class="nasim-tk-thread">
                    <?php foreach ($replies as $reply) : ?>
                    <article class="nasim-tk-msg<?php echo $reply->is_staff ? ' is-staff' : ''; ?>">
                        <header>
                            <strong><?php echo esc_html(nasim_ticket_author_name($reply)); ?></strong>
                            <span><?php echo esc_html(nasim_jdate(nasim_payment_time($reply->created_at), true)); ?></span>
                        </header>
                        <div class="nasim-tk-body"><?php echo esc_html($reply->message); ?></div>
                        <?php if ($reply->attachment) : ?>
                        <a class="nasim-tk-file" href="<?php echo esc_url(nasim_ticket_file_url($reply->id)); ?>">
                            <?php if (nasim_ticket_is_image($reply->attachment)) : ?>
                                <img src="<?php echo esc_url(add_query_arg('inline', 1, nasim_ticket_file_url($reply->id))); ?>" alt="">
                            <?php else : ?>
                                <span class="dashicons dashicons-paperclip"></span> <?php echo esc_html($reply->attachment_name); ?>
                            <?php endif; ?>
                        </a>
                        <?php endif; ?>
                    </article>
                    <?php endforeach; ?>
                </div>

                <form class="card nasim-tk-reply" style="max-width:none;margin-top:18px" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                    <h2><?php esc_html_e('ارسال پاسخ', 'nasim-fitness'); ?></h2>
                    <input type="hidden" name="action" value="nasim_admin_ticket_reply">
                    <input type="hidden" name="ticket_id" value="<?php echo esc_attr($ticket->id); ?>">
                    <?php wp_nonce_field('nasim_admin_ticket_reply'); ?>
                    <textarea name="message" required placeholder="<?php esc_attr_e('پاسخ به کاربر…', 'nasim-fitness'); ?>"></textarea>
                    <div class="row">
                        <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.zip,.txt,.log">
                        <label><?php esc_html_e('وضعیت بعد از ارسال:', 'nasim-fitness'); ?>
                            <select name="status">
                                <option value="answered"><?php esc_html_e('پاسخ داده شد', 'nasim-fitness'); ?></option>
                                <option value="closed"><?php esc_html_e('بستن تیکت', 'nasim-fitness'); ?></option>
                                <option value="open"><?php esc_html_e('باز (در حال بررسی)', 'nasim-fitness'); ?></option>
                            </select>
                        </label>
                        <?php submit_button(__('ارسال پاسخ', 'nasim-fitness'), 'primary', '', false); ?>
                    </div>
                </form>
            </div>

            <aside class="nasim-tk-side">
                <div class="card">
                    <h2><?php esc_html_e('جزئیات تیکت', 'nasim-fitness'); ?></h2>
                    <dl>
                        <dt><?php esc_html_e('وضعیت', 'nasim-fitness'); ?></dt>
                        <dd><span class="nasim-tk-badge is-<?php echo esc_attr($ticket->status); ?>"><?php echo esc_html(nasim_ticket_label($statuses, $ticket->status)); ?></span></dd>
                        <dt><?php esc_html_e('دپارتمان', 'nasim-fitness'); ?></dt>
                        <dd><?php echo esc_html(nasim_ticket_label(nasim_ticket_departments(), $ticket->department)); ?></dd>
                        <dt><?php esc_html_e('اولویت', 'nasim-fitness'); ?></dt>
                        <dd><?php echo esc_html(nasim_ticket_label(nasim_ticket_priorities(), $ticket->priority)); ?></dd>
                        <?php if ($ticket->product_id) : ?>
                        <dt><?php esc_html_e('محصول', 'nasim-fitness'); ?></dt>
                        <dd><a href="<?php echo esc_url(get_edit_post_link($ticket->product_id)); ?>"><?php echo esc_html(get_the_title($ticket->product_id)); ?></a></dd>
                        <?php endif; ?>
                        <?php if ($ticket->site_url) : ?>
                        <dt><?php esc_html_e('سایت', 'nasim-fitness'); ?></dt>
                        <dd dir="ltr"><a href="<?php echo esc_url($ticket->site_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($ticket->site_url); ?></a></dd>
                        <?php endif; ?>
                        <dt><?php esc_html_e('ایجاد', 'nasim-fitness'); ?></dt>
                        <dd><?php echo esc_html(nasim_jdate(nasim_payment_time($ticket->created_at), true)); ?></dd>
                    </dl>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:14px;display:flex;gap:6px">
                        <input type="hidden" name="action" value="nasim_admin_ticket_status">
                        <input type="hidden" name="ticket_id" value="<?php echo esc_attr($ticket->id); ?>">
                        <?php wp_nonce_field('nasim_admin_ticket_status'); ?>
                        <select name="status">
                            <?php foreach ($statuses as $key => $label) : ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($ticket->status, $key); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php submit_button(__('تغییر وضعیت', 'nasim-fitness'), 'secondary', '', false); ?>
                    </form>
                </div>

                <div class="card">
                    <h2><?php esc_html_e('مشتری', 'nasim-fitness'); ?></h2>
                    <?php if ($user) : ?>
                    <dl>
                        <dt><?php esc_html_e('نام', 'nasim-fitness'); ?></dt>
                        <dd><a href="<?php echo esc_url(get_edit_user_link($user->ID)); ?>"><?php echo esc_html($user->display_name); ?></a></dd>
                        <dt><?php esc_html_e('نام کاربری', 'nasim-fitness'); ?></dt>
                        <dd dir="ltr"><?php echo esc_html($user->user_login); ?></dd>
                        <?php if (function_exists('nasim_get_user_mobile') && nasim_get_user_mobile($user->ID)) : ?>
                        <dt><?php esc_html_e('موبایل', 'nasim-fitness'); ?></dt>
                        <dd dir="ltr"><?php echo esc_html(nasim_get_user_mobile($user->ID)); ?></dd>
                        <?php endif; ?>
                        <?php if (is_email($user->user_email)) : ?>
                        <dt><?php esc_html_e('ایمیل', 'nasim-fitness'); ?></dt>
                        <dd dir="ltr"><?php echo esc_html($user->user_email); ?></dd>
                        <?php endif; ?>
                        <dt><?php esc_html_e('اشتراک', 'nasim-fitness'); ?></dt>
                        <dd><?php echo $sub_active ? '<span style="color:#19834a">' . esc_html__('فعال', 'nasim-fitness') . '</span>' . ($expires ? ' · ' . esc_html(nasim_jdate($expires)) : '') : '<span style="color:#b42318">' . esc_html__('ندارد / منقضی', 'nasim-fitness') . '</span>'; ?></dd>
                        <?php if ($license) : ?>
                        <dt><?php esc_html_e('لایسنس', 'nasim-fitness'); ?></dt>
                        <dd dir="ltr"><code><?php echo esc_html($license->license_key); ?></code></dd>
                        <dt><?php esc_html_e('دامنه‌ها', 'nasim-fitness'); ?></dt>
                        <dd dir="ltr">
                            <?php
                            $domains = array_map(function ($row) {
                                return ($row['domain'] ?? '') . (!empty($row['slug']) ? ' (' . $row['slug'] . ')' : '');
                            }, nasim_license_activations($license));
                            echo $domains ? esc_html(implode('، ', $domains)) : '—';
                            ?>
                        </dd>
                        <?php endif; ?>
                    </dl>
                    <?php else : ?>
                    <p><?php esc_html_e('کاربر حذف شده است.', 'nasim-fitness'); ?></p>
                    <?php endif; ?>
                </div>
            </aside>
        </div>
    </div>
    <?php
}
