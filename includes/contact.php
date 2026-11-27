<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * فرم تماس با ما: اعتبارسنجی، محدودیت نرخ، ارسال ایمیل به مدیر و ثبت پیام‌ها.
 */

const NASIM_CONTACT_LOG_OPTION = 'nasim_contact_messages';
const NASIM_CONTACT_LOG_MAX    = 200;

/**
 * کلیدهای موضوع فرم => برچسب فارسی
 */
function nasim_contact_subjects(): array
{
    return [
        'consult' => 'مشاوره قبل از خرید دوره',
        'support' => 'پشتیبانی دوره‌ها',
        'site'    => 'سفارش طراحی سایت',
        'collab'  => 'همکاری و پیشنهاد',
        'radio'   => 'پیشنهاد موضوع رادیو نیل',
    ];
}

/**
 * ارقام فارسی/عربی را به لاتین تبدیل می‌کند.
 */
function nasim_contact_normalize_digits(string $value): string
{
    return strtr($value, [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ]);
}

/**
 * آیا ورودی یک ایمیل معتبر یا شماره موبایل ایرانی است؟
 */
function nasim_contact_is_valid_contact(string $value): bool
{
    if (is_email($value)) {
        return true;
    }
    $phone = str_replace([' ', '-'], '', nasim_contact_normalize_digits($value));
    return (bool) preg_match('/^09\d{9}$/', $phone);
}

function nasim_contact_redirect(string $status, string $back): void
{
    wp_safe_redirect(add_query_arg('contact', $status, $back) . '#contact');
    exit;
}

function nasim_handle_contact(): void
{
    $back = wp_get_referer() ?: home_url('/contact/');
    $back = remove_query_arg('contact', $back);

    if (!isset($_POST['nasim_contact_nonce']) || !wp_verify_nonce(sanitize_key(wp_unslash($_POST['nasim_contact_nonce'])), 'nasim_contact')) {
        nasim_contact_redirect('expired', $back);
    }

    // Honeypot: bots fill every field.
    if (!empty($_POST['website'])) {
        nasim_contact_redirect('ok', $back);
    }

    $bucket = 'contact_' . nasim_client_ip();
    if (nasim_rate_exceeded($bucket, 5)) {
        nasim_contact_redirect('limit', $back);
    }

    $name     = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $contact  = sanitize_text_field(wp_unslash($_POST['contact'] ?? ''));
    $subjects = nasim_contact_subjects();
    $subject  = sanitize_key(wp_unslash($_POST['subject'] ?? ''));
    $message  = sanitize_textarea_field(wp_unslash($_POST['msg'] ?? ''));

    if (mb_strlen($name) < 2 || !nasim_contact_is_valid_contact($contact) || !isset($subjects[$subject]) || mb_strlen($message) < 5) {
        nasim_contact_redirect('invalid', $back);
    }

    nasim_rate_hit($bucket, HOUR_IN_SECONDS);

    $subject_label = $subjects[$subject];
    $is_email      = is_email($contact);
    $reply_to      = $is_email ? $contact : '';

    $body = implode("\n", [
        'نام: ' . $name,
        'تماس: ' . $contact,
        'موضوع: ' . $subject_label,
        '',
        'متن پیام:',
        $message,
    ]);

    $headers = [];
    if ($reply_to) {
        $headers[] = 'Reply-To: ' . $name . ' <' . $reply_to . '>';
    }

    $sent = wp_mail(
        get_option('admin_email'),
        '[فرم تماس] ' . $subject_label . ' — ' . $name,
        $body,
        $headers
    );

    // Keep a copy in the admin so messages are not lost if mail delivery fails.
    $log = (array) get_option(NASIM_CONTACT_LOG_OPTION, []);
    array_unshift($log, [
        'time'    => time(),
        'name'    => $name,
        'contact' => $contact,
        'subject' => $subject_label,
        'message' => $message,
        'mailed'  => (bool) $sent,
    ]);
    update_option(NASIM_CONTACT_LOG_OPTION, array_slice($log, 0, NASIM_CONTACT_LOG_MAX), false);

    nasim_contact_redirect('ok', $back);
}
add_action('admin_post_nasim_contact', 'nasim_handle_contact');
add_action('admin_post_nopriv_nasim_contact', 'nasim_handle_contact');

function nasim_contact_admin_menu(): void
{
    add_submenu_page('tools.php', 'پیام‌های فرم تماس', 'پیام‌های تماس', 'manage_options', 'nasim-contact-messages', 'nasim_render_contact_messages_page');
}
add_action('admin_menu', 'nasim_contact_admin_menu');

function nasim_render_contact_messages_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $rows = (array) get_option(NASIM_CONTACT_LOG_OPTION, []);
    ?>
    <div class="wrap">
      <h1>پیام‌های فرم تماس</h1>
      <p><?php echo esc_html(sprintf('%d پیام ثبت‌شده (حداکثر %d)', count($rows), NASIM_CONTACT_LOG_MAX)); ?></p>
      <table class="widefat striped">
        <thead><tr><th>زمان</th><th>نام</th><th>تماس</th><th>موضوع</th><th>پیام</th><th>ایمیل</th></tr></thead>
        <tbody>
        <?php if (!$rows) : ?>
          <tr><td colspan="6">هنوز پیامی ثبت نشده است.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $row) : ?>
          <tr>
            <td><?php echo esc_html(wp_date('Y-m-d H:i', (int) $row['time'])); ?></td>
            <td><?php echo esc_html($row['name']); ?></td>
            <td dir="ltr"><?php echo esc_html($row['contact']); ?></td>
            <td><?php echo esc_html($row['subject']); ?></td>
            <td style="white-space:pre-wrap"><?php echo esc_html($row['message']); ?></td>
            <td><?php echo $row['mailed'] ? 'ارسال شد' : 'ارسال نشد'; ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
}
