<?php

if(!defined('ABSPATH')) exit;



/**
 * Bump when the tickets tables schema changes
 */
define('NASIM_TICKETS_DB_VERSION', '1');


include_once THEME_DIR . '/includes/tickets/ticket-functions.php';
include_once THEME_DIR . '/includes/tickets/ticket-files.php';
include_once THEME_DIR . '/includes/tickets/ticket-notifications.php';
include_once THEME_DIR . '/includes/tickets/ticket-handlers.php';
include_once THEME_DIR . '/includes/tickets/admin.php';


/**
 * Tickets and their message thread
 */
function nasim_install_tickets_tables() {

    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $tickets = nasim_tickets_table();
    $replies = nasim_ticket_replies_table();
    $charset = $wpdb->get_charset_collate();

    dbDelta("CREATE TABLE {$tickets} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        subject varchar(191) NOT NULL,
        department varchar(32) NOT NULL DEFAULT 'technical',
        priority varchar(16) NOT NULL DEFAULT 'normal',
        status varchar(20) NOT NULL DEFAULT 'open',
        product_id bigint(20) unsigned NOT NULL DEFAULT 0,
        site_url varchar(255) NOT NULL DEFAULT '',
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY status (status),
        KEY updated_at (updated_at)
    ) {$charset};");

    dbDelta("CREATE TABLE {$replies} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        ticket_id bigint(20) unsigned NOT NULL,
        user_id bigint(20) unsigned NOT NULL,
        is_staff tinyint(1) unsigned NOT NULL DEFAULT 0,
        message longtext NOT NULL,
        attachment varchar(255) NOT NULL DEFAULT '',
        attachment_name varchar(191) NOT NULL DEFAULT '',
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY ticket_id (ticket_id)
    ) {$charset};");

    update_option('nasim_tickets_db_version', NASIM_TICKETS_DB_VERSION);
}


function nasim_maybe_install_tickets_tables() {

    if (get_option('nasim_tickets_db_version') !== NASIM_TICKETS_DB_VERSION) {
        nasim_install_tickets_tables();
    }
}
add_action('init', 'nasim_maybe_install_tickets_tables', 5);
add_action('after_switch_theme', 'nasim_install_tickets_tables');
