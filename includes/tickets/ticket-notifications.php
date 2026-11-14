<?php

if(!defined('ABSPATH')) exit;



function nasim_ticket_staff_email() {

    $email = (string) get_option('nasim_ticket_notify_email', '');

    return is_email($email) ? $email : (string) get_option('admin_email');
}


function nasim_ticket_send_mail($to, $subject, array $lines, $link, $link_label) {

    if (!is_email($to)) {
        return false;
    }

    $body  = implode("\n", $lines);
    $body .= "\n\n{$link_label}:\n{$link}\n\n— " . get_bloginfo('name');

    return wp_mail($to, '[' . get_bloginfo('name') . '] ' . $subject, $body);
}


/**
 * New ticket → staff
 */
function nasim_ticket_notify_created($ticket_id) {

    $ticket = nasim_get_ticket($ticket_id);
    $user   = $ticket ? get_userdata($ticket->user_id) : null;

    if (!$ticket) {
        return;
    }

    $replies = nasim_get_ticket_replies($ticket_id);
    $first   = $replies ? $replies[0]->message : '';

    nasim_ticket_send_mail(
        nasim_ticket_staff_email(),
        sprintf('تیکت جدید #%d: %s', $ticket->id, $ticket->subject),
        [
            'کاربر: ' . ($user ? $user->display_name . ' (' . $user->user_login . ')' : '—'),
            'دپارتمان: ' . nasim_ticket_label(nasim_ticket_departments(), $ticket->department),
            'اولویت: ' . nasim_ticket_label(nasim_ticket_priorities(), $ticket->priority),
            $ticket->product_id ? 'محصول: ' . get_the_title($ticket->product_id) : '',
            $ticket->site_url ? 'سایت: ' . $ticket->site_url : '',
            '',
            $first,
        ],
        nasim_ticket_admin_url($ticket->id),
        'پاسخ در پیشخوان'
    );
}
add_action('nasim_ticket_created', 'nasim_ticket_notify_created');


/**
 * Staff reply → customer, customer reply → staff
 */
function nasim_ticket_notify_replied($ticket_id, $reply_id, $is_staff) {

    $ticket = nasim_get_ticket($ticket_id);
    $reply  = nasim_get_ticket_reply($reply_id);

    if (!$ticket || !$reply) {
        return;
    }

    if ($is_staff) {
        $user = get_userdata($ticket->user_id);

        if ($user) {
            nasim_ticket_send_mail(
                $user->user_email,
                sprintf('پاسخ جدید به تیکت #%d', $ticket->id),
                [
                    'سلام ' . $user->display_name . '،',
                    sprintf('به تیکت «%s» پاسخ داده شد:', $ticket->subject),
                    '',
                    $reply->message,
                ],
                nasim_ticket_url($ticket->id),
                'مشاهده و پاسخ'
            );
        }

        return;
    }

    nasim_ticket_send_mail(
        nasim_ticket_staff_email(),
        sprintf('پاسخ کاربر در تیکت #%d: %s', $ticket->id, $ticket->subject),
        [$reply->message],
        nasim_ticket_admin_url($ticket->id),
        'پاسخ در پیشخوان'
    );
}
add_action('nasim_ticket_replied', 'nasim_ticket_notify_replied', 10, 3);
