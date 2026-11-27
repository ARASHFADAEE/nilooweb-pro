<?php

if(!defined('ABSPATH')) exit;



function nasim_payments_table() {

    global $wpdb;

    return $wpdb->prefix . 'nasim_payments';
}


/**
 * Payments table: one row per checkout attempt, amounts in Rial (the gateway unit)
 */
function nasim_install_payments_table() {

    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $table   = nasim_payments_table();
    $charset = $wpdb->get_charset_collate();

    dbDelta("CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        plan_id bigint(20) unsigned NOT NULL,
        plan_title varchar(191) NOT NULL DEFAULT '',
        duration_days smallint(5) unsigned NOT NULL,
        amount bigint(20) unsigned NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        gateway varchar(32) NOT NULL DEFAULT 'zibal',
        track_id varchar(191) DEFAULT NULL,
        ref_number varchar(64) DEFAULT NULL,
        card_number varchar(32) DEFAULT NULL,
        note varchar(255) NOT NULL DEFAULT '',
        created_at datetime NOT NULL,
        paid_at datetime DEFAULT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY track_id (track_id),
        KEY user_id (user_id),
        KEY status (status)
    ) {$charset};");

    update_option('nasim_db_version', NASIM_LMS_DB_VERSION);
}


function nasim_maybe_install_payments_table() {

    if (get_option('nasim_db_version') !== NASIM_LMS_DB_VERSION) {
        nasim_install_payments_table();
    }
}
add_action('admin_init', 'nasim_maybe_install_payments_table');


function nasim_payment_statuses() {

    return [
        'pending' => 'در انتظار پرداخت',
        'paid'    => 'موفق',
        'failed'  => 'ناموفق',
        'manual'  => 'دستی (ادمین)',
    ];
}


/**
 * Record an access change made by an admin (grant or revoke), so the transactions list is a full audit trail
 */
function nasim_log_manual_access($user_id, $title, $days = 0, $note = '', $plan_id = 0) {

    global $wpdb;

    $admin = wp_get_current_user();
    $note  = trim('توسط ' . $admin->display_name . ($note ? ' — ' . $note : ''));
    $now   = current_time('mysql', true);

    $wpdb->insert(nasim_payments_table(), [
        'user_id'       => (int) $user_id,
        'plan_id'       => (int) $plan_id,
        'plan_title'    => mb_substr($title, 0, 191),
        'duration_days' => (int) $days,
        'amount'        => 0,
        'status'        => 'manual',
        'note'          => mb_substr($note, 0, 255),
        'created_at'    => $now,
        'paid_at'       => $now,
    ], ['%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s']);
}


/**
 * Create a pending payment for a plan and return its id
 *
 * @param array<string,mixed> $plan
 */
function nasim_create_payment($user_id, $plan, $gateway = '') {

    global $wpdb;

    $gateway = $gateway ?: (function_exists('nasim_payment_gateway_id') ? nasim_payment_gateway_id() : 'zibal');

    $wpdb->insert(nasim_payments_table(), [
        'user_id'       => (int) $user_id,
        'plan_id'       => (int) $plan['id'],
        'plan_title'    => $plan['title'],
        'duration_days' => (int) $plan['days'],
        'amount'        => (int) $plan['price'] * 10,
        'status'        => 'pending',
        'gateway'       => sanitize_key($gateway),
        'created_at'    => current_time('mysql', true),
    ], ['%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s']);

    return (int) $wpdb->insert_id;
}


function nasim_get_payment($payment_id) {

    global $wpdb;

    $table = nasim_payments_table();

    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $payment_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}


function nasim_get_payment_by_track_id($track_id) {

    global $wpdb;

    $table = nasim_payments_table();

    return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE track_id = %s", $track_id)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}


function nasim_set_payment_track_id($payment_id, $track_id) {

    global $wpdb;

    $wpdb->update(nasim_payments_table(), ['track_id' => $track_id], ['id' => (int) $payment_id], ['%s'], ['%d']);
}


function nasim_set_payment_gateway($payment_id, $gateway) {

    global $wpdb;

    $wpdb->update(
        nasim_payments_table(),
        ['gateway' => sanitize_key($gateway)],
        ['id' => (int) $payment_id],
        ['%s'],
        ['%d']
    );
}


/**
 * Mark a pending payment as failed
 */
function nasim_mark_payment_failed($payment_id, $note = '') {

    global $wpdb;

    $wpdb->update(
        nasim_payments_table(),
        ['status' => 'failed', 'note' => mb_substr($note, 0, 255)],
        ['id' => (int) $payment_id, 'status' => 'pending'],
        ['%s', '%s'],
        ['%d', '%s']
    );
}


/**
 * Mark a pending payment as paid; returns false if it was already processed,
 * so a repeated callback can never extend the subscription twice
 */
function nasim_mark_payment_paid($payment_id, $ref_number, $card_number) {

    global $wpdb;

    $updated = $wpdb->update(
        nasim_payments_table(),
        [
            'status'      => 'paid',
            'ref_number'  => $ref_number,
            'card_number' => $card_number,
            'paid_at'     => current_time('mysql', true),
            'note'        => '',
        ],
        ['id' => (int) $payment_id, 'status' => 'pending'],
        ['%s', '%s', '%s', '%s', '%s'],
        ['%d', '%s']
    );

    return 1 === $updated;
}


function nasim_get_user_payments($user_id, $limit = 10) {

    global $wpdb;

    $table = nasim_payments_table();

    return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d ORDER BY id DESC LIMIT %d", $user_id, $limit)); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}


/**
 * Paginated payments for the admin list, optionally filtered by status
 */
function nasim_get_payments($page = 1, $per_page = 30, $status = '') {

    global $wpdb;

    $table  = nasim_payments_table();
    $where  = $status ? $wpdb->prepare('WHERE status = %s', $status) : '';
    $offset = max(0, ($page - 1) * $per_page);

    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $items = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} {$where} ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, $offset));
    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} {$where}");
    // phpcs:enable

    return ['items' => $items, 'total' => $total];
}


/**
 * Unix timestamp of a UTC datetime column
 */
function nasim_payment_time($datetime) {

    return $datetime ? (int) strtotime($datetime . ' UTC') : 0;
}
