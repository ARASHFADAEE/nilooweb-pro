<?php

if(!defined('ABSPATH')) exit;



function nasim_tickets_table() {

    global $wpdb;

    return $wpdb->prefix . 'nasim_tickets';
}


function nasim_ticket_replies_table() {

    global $wpdb;

    return $wpdb->prefix . 'nasim_ticket_replies';
}


function nasim_ticket_departments() {

    return apply_filters('nasim_ticket_departments', [
        'technical' => 'پشتیبانی فنی افزونه/قالب',
        'license'   => 'لایسنس و دامنه',
        'billing'   => 'مالی و اشتراک',
        'presale'   => 'سؤال پیش از خرید',
        'custom'    => 'سفارش توسعه اختصاصی',
    ]);
}


function nasim_ticket_priorities() {

    return [
        'low'    => 'کم',
        'normal' => 'معمولی',
        'high'   => 'فوری',
    ];
}


/**
 * open = waiting for staff, answered = waiting for customer
 */
function nasim_ticket_statuses() {

    return [
        'open'     => 'در انتظار پاسخ',
        'answered' => 'پاسخ داده شد',
        'closed'   => 'بسته شده',
    ];
}


function nasim_ticket_label($map, $key) {

    return $map[$key] ?? $key;
}


/**
 * Max length rules shared by the panel form and the handlers
 */
function nasim_ticket_limits() {

    return [
        'subject_min' => 5,
        'subject_max' => 150,
        'message_min' => 10,
        'message_max' => 10000,
        'open_max'    => 5,
        'flood_secs'  => 20,
    ];
}


function nasim_get_ticket($ticket_id) {

    global $wpdb;

    $table = nasim_tickets_table();

    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", (int) $ticket_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}


function nasim_get_user_tickets($user_id, $status = '') {

    global $wpdb;

    $table = nasim_tickets_table();

    if ($status && isset(nasim_ticket_statuses()[$status])) {
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d AND status = %s ORDER BY updated_at DESC LIMIT 100", (int) $user_id, $status)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d ORDER BY FIELD(status, 'answered', 'open', 'closed'), updated_at DESC LIMIT 100", (int) $user_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}


/**
 * Ticket counts per status for one user, or for everyone when $user_id is 0
 */
function nasim_ticket_counts($user_id = 0) {

    global $wpdb;

    $table  = nasim_tickets_table();
    $counts = array_fill_keys(array_keys(nasim_ticket_statuses()), 0);

    $rows = $user_id
        ? $wpdb->get_results($wpdb->prepare("SELECT status, COUNT(*) AS total FROM {$table} WHERE user_id = %d GROUP BY status", (int) $user_id)) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        : $wpdb->get_results("SELECT status, COUNT(*) AS total FROM {$table} GROUP BY status"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

    foreach ((array) $rows as $row) {
        $counts[$row->status] = (int) $row->total;
    }

    return $counts;
}


function nasim_get_ticket_replies($ticket_id) {

    global $wpdb;

    $table = nasim_ticket_replies_table();

    return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE ticket_id = %d ORDER BY id ASC", (int) $ticket_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}


function nasim_get_ticket_reply($reply_id) {

    global $wpdb;

    $table = nasim_ticket_replies_table();

    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", (int) $reply_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}


function nasim_user_can_view_ticket($ticket, $user_id = 0) {

    $user_id = $user_id ?: get_current_user_id();

    if (!$ticket || !$user_id) {
        return false;
    }

    return (int) $ticket->user_id === (int) $user_id || user_can($user_id, 'manage_options');
}


function nasim_user_open_ticket_count($user_id) {

    global $wpdb;

    $table = nasim_tickets_table();

    return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND status <> 'closed'", (int) $user_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}


/**
 * Plain-text message cleanup: strip tags, keep line breaks
 */
function nasim_ticket_clean_message($message) {

    $message = sanitize_textarea_field((string) $message);

    return trim(preg_replace("/\n{3,}/", "\n\n", $message));
}


/**
 * Create a ticket with its first message
 *
 * @return int|WP_Error
 */
function nasim_create_ticket($user_id, array $data, array $attachment = []) {

    global $wpdb;

    $limits     = nasim_ticket_limits();
    $subject    = sanitize_text_field($data['subject'] ?? '');
    $message    = nasim_ticket_clean_message($data['message'] ?? '');
    $department = sanitize_key($data['department'] ?? '');
    $priority   = sanitize_key($data['priority'] ?? 'normal');
    $product_id = absint($data['product_id'] ?? 0);
    $site_url   = esc_url_raw($data['site_url'] ?? '');

    if (mb_strlen($subject) < $limits['subject_min'] || mb_strlen($subject) > $limits['subject_max']) {
        return new WP_Error('subject', 'عنوان تیکت باید بین ۵ تا ۱۵۰ کاراکتر باشد.');
    }

    if (mb_strlen($message) < $limits['message_min'] || mb_strlen($message) > $limits['message_max']) {
        return new WP_Error('message', 'متن پیام خیلی کوتاه یا خیلی طولانی است.');
    }

    if (!isset(nasim_ticket_departments()[$department])) {
        return new WP_Error('department', 'دپارتمان انتخاب‌شده معتبر نیست.');
    }

    if (!isset(nasim_ticket_priorities()[$priority])) {
        $priority = 'normal';
    }

    if ($product_id && 'nasim_product' !== get_post_type($product_id)) {
        $product_id = 0;
    }

    if (nasim_user_open_ticket_count($user_id) >= $limits['open_max']) {
        return new WP_Error('open_max', 'تعداد تیکت‌های باز شما به سقف رسیده؛ لطفاً تا پاسخ تیکت‌های قبلی صبر کنید.');
    }

    $now = current_time('mysql', true);

    $inserted = $wpdb->insert(nasim_tickets_table(), [
        'user_id'    => (int) $user_id,
        'subject'    => mb_substr($subject, 0, 191),
        'department' => $department,
        'priority'   => $priority,
        'status'     => 'open',
        'product_id' => $product_id,
        'site_url'   => mb_substr($site_url, 0, 255),
        'created_at' => $now,
        'updated_at' => $now,
    ], ['%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s']);

    if (!$inserted) {
        return new WP_Error('db', 'ثبت تیکت ناموفق بود؛ دوباره تلاش کن.');
    }

    $ticket_id = (int) $wpdb->insert_id;

    nasim_insert_ticket_reply($ticket_id, $user_id, $message, false, $attachment);

    do_action('nasim_ticket_created', $ticket_id);

    return $ticket_id;
}


function nasim_insert_ticket_reply($ticket_id, $user_id, $message, $is_staff, array $attachment = []) {

    global $wpdb;

    $wpdb->insert(nasim_ticket_replies_table(), [
        'ticket_id'       => (int) $ticket_id,
        'user_id'         => (int) $user_id,
        'is_staff'        => $is_staff ? 1 : 0,
        'message'         => $message,
        'attachment'      => $attachment['file'] ?? '',
        'attachment_name' => mb_substr($attachment['name'] ?? '', 0, 191),
        'created_at'      => current_time('mysql', true),
    ], ['%d', '%d', '%d', '%s', '%s', '%s', '%s']);

    return (int) $wpdb->insert_id;
}


/**
 * Add a reply and move the ticket to the matching status
 *
 * @return int|WP_Error reply id
 */
function nasim_add_ticket_reply($ticket_id, $user_id, $message, $is_staff, array $attachment = [], $status = '') {

    $ticket  = nasim_get_ticket($ticket_id);
    $limits  = nasim_ticket_limits();
    $message = nasim_ticket_clean_message($message);

    if (!$ticket) {
        return new WP_Error('ticket', 'تیکت پیدا نشد.');
    }

    if (mb_strlen($message) < 2 || mb_strlen($message) > $limits['message_max']) {
        return new WP_Error('message', 'متن پاسخ خیلی کوتاه یا خیلی طولانی است.');
    }

    $reply_id = nasim_insert_ticket_reply($ticket_id, $user_id, $message, $is_staff, $attachment);

    if (!$status || !isset(nasim_ticket_statuses()[$status])) {
        $status = $is_staff ? 'answered' : 'open';
    }

    nasim_set_ticket_status($ticket_id, $status);

    do_action('nasim_ticket_replied', $ticket_id, $reply_id, (bool) $is_staff);

    return $reply_id;
}


function nasim_set_ticket_status($ticket_id, $status) {

    global $wpdb;

    if (!isset(nasim_ticket_statuses()[$status])) {
        return false;
    }

    return false !== $wpdb->update(
        nasim_tickets_table(),
        ['status' => $status, 'updated_at' => current_time('mysql', true)],
        ['id' => (int) $ticket_id],
        ['%s', '%s'],
        ['%d']
    );
}


/**
 * Paged list for the admin screen
 */
function nasim_get_tickets_admin($page = 1, $per_page = 30, $status = '', $search = '', $department = '') {

    global $wpdb;

    $table  = nasim_tickets_table();
    $where  = ['1=1'];
    $params = [];

    if ($status && isset(nasim_ticket_statuses()[$status])) {
        $where[]  = 'status = %s';
        $params[] = $status;
    }

    if ($department && isset(nasim_ticket_departments()[$department])) {
        $where[]  = 'department = %s';
        $params[] = $department;
    }

    if ($search) {
        $user_ids = get_users(['search' => '*' . $search . '*', 'fields' => 'ID', 'number' => 50]);
        $like     = '%' . $wpdb->esc_like($search) . '%';
        $clause   = '(subject LIKE %s OR site_url LIKE %s';
        $params[] = $like;
        $params[] = $like;

        if (ctype_digit($search)) {
            $clause  .= ' OR id = %d';
            $params[] = (int) $search;
        }

        if ($user_ids) {
            $clause .= ' OR user_id IN (' . implode(',', array_map('absint', $user_ids)) . ')';
        }

        $where[] = $clause . ')';
    }

    $where_sql = implode(' AND ', $where);
    $offset    = (max(1, $page) - 1) * $per_page;

    $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
    $list_sql  = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY FIELD(status, 'open', 'answered', 'closed'), FIELD(priority, 'high', 'normal', 'low'), updated_at DESC LIMIT %d OFFSET %d";

    $total = $params ? (int) $wpdb->get_var($wpdb->prepare($count_sql, $params)) : (int) $wpdb->get_var($count_sql); // phpcs:ignore WordPress.DB.PreparedSQL
    $items = $wpdb->get_results($wpdb->prepare($list_sql, array_merge($params, [$per_page, $offset]))); // phpcs:ignore WordPress.DB.PreparedSQL

    return ['items' => $items, 'total' => $total];
}


function nasim_ticket_url($ticket_id) {

    return nasim_subscription_url(['tab' => 'tickets', 'ticket' => (int) $ticket_id]);
}


function nasim_ticket_new_url($product_id = 0) {

    $args = ['tab' => 'tickets', 'ticket' => 'new'];

    if ($product_id) {
        $args['product'] = (int) $product_id;
    }

    return nasim_subscription_url($args);
}


function nasim_ticket_admin_url($ticket_id = 0) {

    $args = ['page' => 'nasim-tickets'];

    if ($ticket_id) {
        $args['ticket'] = (int) $ticket_id;
    }

    return add_query_arg($args, admin_url('admin.php'));
}


function nasim_ticket_number($ticket_id) {

    return nasim_fa_digits('#' . (int) $ticket_id);
}


/**
 * Human-friendly relative time for a GMT datetime
 */
function nasim_ticket_time_ago($datetime) {

    $time = nasim_payment_time($datetime);

    if (!$time) {
        return '';
    }

    if (time() - $time > WEEK_IN_SECONDS) {
        return nasim_jdate($time, true);
    }

    return nasim_fa_digits(human_time_diff($time, time())) . ' پیش';
}


function nasim_ticket_author_name($reply) {

    if ($reply->is_staff) {
        return 'پشتیبانی ' . get_bloginfo('name');
    }

    $user = get_userdata($reply->user_id);

    return $user ? $user->display_name : 'کاربر';
}
