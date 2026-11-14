<?php

if(!defined('ABSPATH')) exit;



/**
 * Redirect back to the panel tickets tab with a short notice code
 */
function nasim_ticket_redirect($args = []) {

    wp_safe_redirect(nasim_subscription_url(array_merge(['tab' => 'tickets'], $args)));
    exit;
}


/**
 * One message per user every few seconds
 */
function nasim_ticket_is_flooding($user_id) {

    return (bool) get_transient('nasim_ticket_flood_' . (int) $user_id);
}


function nasim_ticket_mark_sent($user_id) {

    set_transient('nasim_ticket_flood_' . (int) $user_id, 1, nasim_ticket_limits()['flood_secs']);
}


/**
 * Keep a failed form's values for one redirect so the user doesn't retype
 */
function nasim_ticket_remember_form($user_id, array $data) {

    set_transient('nasim_ticket_form_' . (int) $user_id, $data, 5 * MINUTE_IN_SECONDS);
}


function nasim_ticket_pull_form($user_id) {

    $key  = 'nasim_ticket_form_' . (int) $user_id;
    $data = get_transient($key);

    delete_transient($key);

    return is_array($data) ? $data : [];
}


function nasim_handle_ticket_create() {

    check_admin_referer('nasim_ticket_create');

    if (!is_user_logged_in()) {
        wp_safe_redirect(nasim_auth_url('login', nasim_ticket_new_url()));
        exit;
    }

    $user_id = get_current_user_id();
    $data    = [
        'subject'    => wp_unslash($_POST['subject'] ?? ''),
        'message'    => wp_unslash($_POST['message'] ?? ''),
        'department' => wp_unslash($_POST['department'] ?? ''),
        'priority'   => wp_unslash($_POST['priority'] ?? 'normal'),
        'product_id' => absint($_POST['product_id'] ?? 0),
        'site_url'   => wp_unslash($_POST['site_url'] ?? ''),
    ];

    if (nasim_ticket_is_flooding($user_id)) {
        nasim_ticket_remember_form($user_id, $data);
        nasim_ticket_redirect(['ticket' => 'new', 'ticket_error' => 'flood']);
    }

    $attachment = nasim_ticket_handle_upload('attachment');

    if (is_wp_error($attachment)) {
        nasim_ticket_remember_form($user_id, $data);
        set_transient('nasim_ticket_error_' . $user_id, $attachment->get_error_message(), MINUTE_IN_SECONDS);
        nasim_ticket_redirect(['ticket' => 'new', 'ticket_error' => 'custom']);
    }

    $ticket_id = nasim_create_ticket($user_id, $data, $attachment);

    if (is_wp_error($ticket_id)) {
        nasim_ticket_remember_form($user_id, $data);
        set_transient('nasim_ticket_error_' . $user_id, $ticket_id->get_error_message(), MINUTE_IN_SECONDS);
        nasim_ticket_redirect(['ticket' => 'new', 'ticket_error' => 'custom']);
    }

    nasim_ticket_mark_sent($user_id);
    nasim_ticket_redirect(['ticket' => $ticket_id, 'ticket_notice' => 'created']);
}
add_action('admin_post_nasim_ticket_create', 'nasim_handle_ticket_create');


function nasim_handle_ticket_reply() {

    check_admin_referer('nasim_ticket_reply');

    if (!is_user_logged_in()) {
        wp_safe_redirect(nasim_auth_url('login', nasim_subscription_url(['tab' => 'tickets'])));
        exit;
    }

    $user_id   = get_current_user_id();
    $ticket_id = absint($_POST['ticket_id'] ?? 0);
    $ticket    = nasim_get_ticket($ticket_id);

    if (!$ticket || (int) $ticket->user_id !== $user_id) {
        nasim_ticket_redirect(['ticket_error' => 'ticket']);
    }

    $message = wp_unslash($_POST['message'] ?? '');

    if (nasim_ticket_is_flooding($user_id)) {
        nasim_ticket_remember_form($user_id, ['message' => $message]);
        nasim_ticket_redirect(['ticket' => $ticket_id, 'ticket_error' => 'flood']);
    }

    $attachment = nasim_ticket_handle_upload('attachment');

    if (is_wp_error($attachment)) {
        nasim_ticket_remember_form($user_id, ['message' => $message]);
        set_transient('nasim_ticket_error_' . $user_id, $attachment->get_error_message(), MINUTE_IN_SECONDS);
        nasim_ticket_redirect(['ticket' => $ticket_id, 'ticket_error' => 'custom']);
    }

    $reply = nasim_add_ticket_reply($ticket_id, $user_id, $message, false, $attachment);

    if (is_wp_error($reply)) {
        nasim_ticket_remember_form($user_id, ['message' => $message]);
        set_transient('nasim_ticket_error_' . $user_id, $reply->get_error_message(), MINUTE_IN_SECONDS);
        nasim_ticket_redirect(['ticket' => $ticket_id, 'ticket_error' => 'custom']);
    }

    nasim_ticket_mark_sent($user_id);
    nasim_ticket_redirect(['ticket' => $ticket_id, 'ticket_notice' => 'replied']);
}
add_action('admin_post_nasim_ticket_reply', 'nasim_handle_ticket_reply');


function nasim_handle_ticket_close() {

    check_admin_referer('nasim_ticket_close');

    $ticket_id = absint($_POST['ticket_id'] ?? 0);
    $ticket    = nasim_get_ticket($ticket_id);

    if (!$ticket || (int) $ticket->user_id !== get_current_user_id()) {
        nasim_ticket_redirect(['ticket_error' => 'ticket']);
    }

    nasim_set_ticket_status($ticket_id, 'closed');

    nasim_ticket_redirect(['ticket' => $ticket_id, 'ticket_notice' => 'closed']);
}
add_action('admin_post_nasim_ticket_close', 'nasim_handle_ticket_close');


function nasim_handle_ticket_guest() {

    wp_safe_redirect(nasim_auth_url('login', nasim_subscription_url(['tab' => 'tickets'])));
    exit;
}
add_action('admin_post_nopriv_nasim_ticket_create', 'nasim_handle_ticket_guest');
add_action('admin_post_nopriv_nasim_ticket_reply', 'nasim_handle_ticket_guest');
add_action('admin_post_nopriv_nasim_ticket_close', 'nasim_handle_ticket_guest');
add_action('admin_post_nopriv_nasim_ticket_file', 'nasim_handle_ticket_guest');
