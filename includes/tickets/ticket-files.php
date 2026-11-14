<?php

if(!defined('ABSPATH')) exit;



function nasim_ticket_allowed_mimes() {

    return apply_filters('nasim_ticket_allowed_mimes', [
        'jpg|jpeg' => 'image/jpeg',
        'png'      => 'image/png',
        'webp'     => 'image/webp',
        'gif'      => 'image/gif',
        'pdf'      => 'application/pdf',
        'zip'      => 'application/zip',
        'txt'      => 'text/plain',
        'log'      => 'text/plain',
    ]);
}


function nasim_ticket_max_upload_bytes() {

    return (int) apply_filters('nasim_ticket_max_upload_bytes', 5 * MB_IN_BYTES);
}


/**
 * Private folder inside uploads; direct web access is denied
 */
function nasim_ticket_upload_dir() {

    $uploads = wp_upload_dir(null, false);
    $dir     = trailingslashit($uploads['basedir']) . 'nasim-tickets';

    if (!is_dir($dir)) {
        wp_mkdir_p($dir);
    }

    if (!file_exists($dir . '/.htaccess')) {
        @file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n"); // phpcs:ignore
    }

    if (!file_exists($dir . '/index.php')) {
        @file_put_contents($dir . '/index.php', "<?php\n// Silence is golden.\n"); // phpcs:ignore
    }

    return $dir;
}


/**
 * Validate and store one uploaded file from $_FILES
 *
 * @return array|WP_Error ['file' => stored name, 'name' => original name], empty array when nothing uploaded
 */
function nasim_ticket_handle_upload($field = 'attachment') {

    if (empty($_FILES[$field]) || empty($_FILES[$field]['name'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked by caller.
        return [];
    }

    $file = $_FILES[$field]; // phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput

    if (!empty($file['error'])) {
        return new WP_Error('upload', UPLOAD_ERR_INI_SIZE === (int) $file['error'] || UPLOAD_ERR_FORM_SIZE === (int) $file['error']
            ? 'حجم فایل بیشتر از حد مجاز است.'
            : 'آپلود فایل ناموفق بود.');
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        return new WP_Error('upload', 'فایل نامعتبر است.');
    }

    if ((int) $file['size'] > nasim_ticket_max_upload_bytes()) {
        return new WP_Error('upload', sprintf('حداکثر حجم فایل %s مگابایت است.', nasim_fa_digits((string) round(nasim_ticket_max_upload_bytes() / MB_IN_BYTES))));
    }

    $original = sanitize_file_name(wp_unslash($file['name']));
    $check    = wp_check_filetype_and_ext($file['tmp_name'], $original, nasim_ticket_allowed_mimes());

    if (empty($check['ext']) || empty($check['type'])) {
        return new WP_Error('upload', 'فرمت فایل مجاز نیست (تصویر، PDF، ZIP یا TXT).');
    }

    $dir    = nasim_ticket_upload_dir();
    $stored = wp_generate_password(20, false) . '.' . $check['ext'];

    if (!@move_uploaded_file($file['tmp_name'], $dir . '/' . $stored)) { // phpcs:ignore
        return new WP_Error('upload', 'ذخیره فایل ناموفق بود.');
    }

    return ['file' => $stored, 'name' => $original];
}


function nasim_ticket_file_url($reply_id) {

    return wp_nonce_url(
        add_query_arg(['action' => 'nasim_ticket_file', 'reply' => (int) $reply_id], admin_url('admin-post.php')),
        'nasim_ticket_file_' . (int) $reply_id
    );
}


function nasim_ticket_is_image($filename) {

    return (bool) preg_match('/\.(jpe?g|png|webp|gif)$/i', (string) $filename);
}


/**
 * Stream an attachment to the ticket owner or an admin
 */
function nasim_handle_ticket_file() {

    $reply_id = absint($_GET['reply'] ?? 0);

    check_admin_referer('nasim_ticket_file_' . $reply_id);

    $reply  = $reply_id ? nasim_get_ticket_reply($reply_id) : null;
    $ticket = $reply ? nasim_get_ticket($reply->ticket_id) : null;

    if (!$reply || !$reply->attachment || !nasim_user_can_view_ticket($ticket)) {
        wp_die('دسترسی به این فایل مجاز نیست.', '', ['response' => 403]);
    }

    $path = nasim_ticket_upload_dir() . '/' . basename($reply->attachment);

    if (!is_readable($path)) {
        wp_die('فایل پیدا نشد.', '', ['response' => 404]);
    }

    $type   = wp_check_filetype($path, nasim_ticket_allowed_mimes());
    $inline = nasim_ticket_is_image($path) && !empty($_GET['inline']);
    $name   = $reply->attachment_name ?: basename($path);

    nocache_headers();
    header('Content-Type: ' . ($type['type'] ?: 'application/octet-stream'));
    header('Content-Length: ' . filesize($path));
    header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . rawurlencode($name) . '"; filename*=UTF-8\'\'' . rawurlencode($name));

    readfile($path); // phpcs:ignore WordPress.WP.AlternativeFunctions
    exit;
}
add_action('admin_post_nasim_ticket_file', 'nasim_handle_ticket_file');
