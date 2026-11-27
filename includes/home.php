<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Homepage helpers: live academy stats, section heading, newsletter sign-up.
 */


/**
 * Live numbers for the hero trust row (cached for an hour, flushed when courses change)
 *
 * @return array{courses:int, lessons:int, hours:int}
 */
function nasim_home_stats()
{
    $cached = get_transient('nasim_home_stats');

    if (is_array($cached)) {
        return $cached;
    }

    $ids = get_posts([
        'post_type'      => 'nasim_course',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ]);

    $lessons = 0;
    $seconds = 0;

    foreach ($ids as $id) {
        $lessons += count(nasim_get_course_lessons($id));
        $seconds += nasim_course_total_seconds($id);
    }

    $stats = [
        'courses' => count($ids),
        'lessons' => $lessons,
        'hours'   => (int) round($seconds / HOUR_IN_SECONDS),
    ];

    set_transient('nasim_home_stats', $stats, HOUR_IN_SECONDS);

    return $stats;
}


function nasim_flush_home_stats($post_id)
{
    if (in_array(get_post_type($post_id), ['nasim_course', 'nasim_bundle'], true)) {
        delete_transient('nasim_home_stats');
    }
}
add_action('save_post', 'nasim_flush_home_stats');
add_action('deleted_post', 'nasim_flush_home_stats');


/**
 * Minimal section heading used by the redesigned homepage
 *
 * @param string $prefix  customizer prefix ({prefix}_eyebrow, _title, _title_highlight)
 * @param string $link    optional "see all" url
 * @param string $link_label
 */
function nasim_home_heading($prefix, $link = '', $link_label = '', $description = '')
{
    $eyebrow   = (string) nasim_mod($prefix . '_eyebrow');
    $title     = (string) nasim_mod($prefix . '_title');
    $highlight = (string) nasim_mod($prefix . '_title_highlight');
    ?>
    <header class="nh-head">
      <div class="nh-head-copy">
        <?php if ($eyebrow) : ?><span class="nh-eyebrow"><?php echo esc_html($eyebrow); ?></span><?php endif; ?>
        <h2 class="nh-title"><?php echo esc_html($title); ?><?php if ($highlight) : ?> <em><?php echo esc_html($highlight); ?></em><?php endif; ?></h2>
        <?php if ($description) : ?><p class="nh-desc"><?php echo esc_html($description); ?></p><?php endif; ?>
      </div>
      <?php if ($link) : ?>
      <a class="nh-more" href="<?php echo esc_url($link); ?>"><?php echo esc_html($link_label ?: 'مشاهده همه'); ?> <span aria-hidden="true">←</span></a>
      <?php endif; ?>
    </header>
    <?php
}


/**
 * Inline arrow / play icons used by the homepage
 */
function nasim_home_icon($name)
{
    $icons = [
        'arrow'  => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'play'   => '<path d="M8 5.5v13l11-6.5z"/>',
        'clock'  => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'lesson' => '<rect x="3.5" y="5" width="17" height="14" rx="3"/><path d="M10.5 9.5v5l4-2.5z"/>',
        'stack'  => '<path d="M12 3.8 3.5 8.4 12 13l8.5-4.6z"/><path d="M3.5 12.4 12 17l8.5-4.6"/><path d="M3.5 16 12 20.6l8.5-4.6"/>',
        'quote'  => '<path d="M9.5 7C6.5 8 5 10.4 5 13.5V17h4.5v-4.5H7.3c0-2 .9-3.3 2.7-4.2zm9 0c-3 1-4.5 3.4-4.5 6.5V17h4.5v-4.5h-2.2c0-2 .9-3.3 2.7-4.2z"/>',
        'mail'   => '<rect x="3.5" y="5.5" width="17" height="13" rx="2.5"/><path d="m4.5 7 7.5 6 7.5-6"/>',
        'shield' => '<path d="M12 3.5 5 6.2v5.3c0 4.3 2.9 7.6 7 9 4.1-1.4 7-4.7 7-9V6.2z"/><path d="m9 12 2.2 2.2L15.5 10"/>',
    ];

    $fill = in_array($name, ['play', 'quote'], true);

    return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" ' . ($fill ? 'fill="currentColor"' : 'fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"') . '>' . ($icons[$name] ?? '') . '</svg>';
}


/* -------------------------------------------------------------------------
 * Newsletter (stored locally, exportable as CSV)
 * ---------------------------------------------------------------------- */

function nasim_newsletter_subscribers()
{
    $list = get_option('nasim_newsletter', []);

    return is_array($list) ? $list : [];
}


function nasim_handle_newsletter()
{
    $back = wp_get_referer() ?: home_url('/');
    $back = remove_query_arg('newsletter', $back);

    if (!isset($_POST['nasim_newsletter_nonce']) || !wp_verify_nonce(sanitize_key($_POST['nasim_newsletter_nonce']), 'nasim_newsletter')) {
        wp_safe_redirect(add_query_arg('newsletter', 'expired', $back) . '#newsletter');
        exit;
    }

    // Honeypot: bots fill every field.
    if (!empty($_POST['website'])) {
        wp_safe_redirect(add_query_arg('newsletter', 'ok', $back) . '#newsletter');
        exit;
    }

    $email = sanitize_email(wp_unslash($_POST['nasim_newsletter_email'] ?? ''));

    if (!is_email($email)) {
        wp_safe_redirect(add_query_arg('newsletter', 'invalid', $back) . '#newsletter');
        exit;
    }

    $bucket = 'newsletter_' . nasim_client_ip();
    if (nasim_rate_exceeded($bucket, 5)) {
        wp_safe_redirect(add_query_arg('newsletter', 'limit', $back) . '#newsletter');
        exit;
    }
    nasim_rate_hit($bucket, HOUR_IN_SECONDS);

    $list = nasim_newsletter_subscribers();
    $key  = strtolower($email);

    if (!isset($list[$key])) {
        $list[$key] = ['email' => $email, 'time' => time()];
        update_option('nasim_newsletter', $list, false);
        do_action('nasim_newsletter_subscribed', $email);
    }

    wp_safe_redirect(add_query_arg('newsletter', 'ok', $back) . '#newsletter');
    exit;
}
add_action('admin_post_nasim_newsletter', 'nasim_handle_newsletter');
add_action('admin_post_nopriv_nasim_newsletter', 'nasim_handle_newsletter');


function nasim_newsletter_admin_menu()
{
    add_submenu_page('tools.php', __('خبرنامه', 'niloo-web'), __('مشترکان خبرنامه', 'niloo-web'), 'manage_options', 'nasim-newsletter', 'nasim_render_newsletter_page');
}
add_action('admin_menu', 'nasim_newsletter_admin_menu');


function nasim_render_newsletter_page()
{
    $list = array_reverse(nasim_newsletter_subscribers());
    ?>
    <div class="wrap">
      <h1><?php esc_html_e('مشترکان خبرنامه', 'niloo-web'); ?>
        <a class="page-title-action" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=nasim_newsletter_export'), 'nasim_newsletter_export')); ?>"><?php esc_html_e('دریافت CSV', 'niloo-web'); ?></a>
      </h1>
      <p><?php echo esc_html(sprintf('%s مشترک', nasim_fa_digits(count($list)))); ?></p>
      <table class="widefat striped" style="max-width:640px">
        <thead><tr><th><?php esc_html_e('ایمیل', 'niloo-web'); ?></th><th><?php esc_html_e('تاریخ عضویت', 'niloo-web'); ?></th></tr></thead>
        <tbody>
        <?php if (!$list) : ?><tr><td colspan="2"><?php esc_html_e('هنوز مشترکی ندارید.', 'niloo-web'); ?></td></tr><?php endif; ?>
        <?php foreach ($list as $row) : ?>
          <tr><td dir="ltr"><?php echo esc_html($row['email']); ?></td><td><?php echo esc_html(nasim_jdate((int) $row['time'], true)); ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
}


function nasim_newsletter_export()
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('دسترسی غیرمجاز.', 'niloo-web'), 403);
    }

    check_admin_referer('nasim_newsletter_export');

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=newsletter-' . gmdate('Y-m-d') . '.csv');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
    fputcsv($out, ['email', 'subscribed_at']);
    foreach (nasim_newsletter_subscribers() as $row) {
        fputcsv($out, [$row['email'], gmdate('Y-m-d H:i', (int) $row['time'])]);
    }
    fclose($out);
    exit;
}
add_action('admin_post_nasim_newsletter_export', 'nasim_newsletter_export');
