<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Start a plan purchase: create a pending payment and send the user to the active gateway.
 */
function nasim_handle_subscribe()
{
    check_admin_referer('nasim_subscribe');

    $plan = nasim_get_plan(absint($_POST['plan_id'] ?? 0));

    if (!$plan || $plan['price'] <= 0) {
        wp_safe_redirect(nasim_subscription_url(['nasim_error' => 'plan']));
        exit;
    }

    $url = nasim_start_plan_payment(get_current_user_id(), $plan);

    if (is_wp_error($url)) {
        $payment_hint = get_current_user_id() ? ['nasim_error' => 'gateway'] : ['nasim_error' => 'payment'];
        if ('payment' === $url->get_error_code()) {
            $payment_hint = ['nasim_error' => 'payment'];
        } elseif ('gateway' === $url->get_error_code()) {
            $payment_hint = ['nasim_error' => 'gateway'];
        } else {
            // Last failed payment for this user/plan may exist; fall back to account.
            $payment_hint = ['nasim_error' => 'payment'];
        }
        wp_safe_redirect(nasim_subscription_url($payment_hint));
        exit;
    }

    // External gateway URL, so wp_safe_redirect() does not apply.
    wp_redirect($url); // phpcs:ignore WordPress.Security.SafeRedirect
    exit;
}
add_action('admin_post_nasim_subscribe', 'nasim_handle_subscribe');


function nasim_handle_subscribe_guest()
{
    $plan_id = absint($_POST['plan_id'] ?? 0);
    $plan    = $plan_id ? nasim_get_plan($plan_id, false) : null;
    $args    = ['tab' => 'plans'];

    if ($plan && $plan['price'] > 0) {
        $args['highlight'] = $plan_id;
        if (!empty($plan['course_id'])) {
            $args['course'] = (int) $plan['course_id'];
            $args['buy']    = 'course';
        } elseif (in_array($plan['type'] ?? '', ['course', 'spot_course'], true)) {
            $args['buy'] = 'course';
        }
    }

    wp_safe_redirect(nasim_auth_url('login', nasim_subscription_url($args)));
    exit;
}
add_action('admin_post_nopriv_nasim_subscribe', 'nasim_handle_subscribe_guest');
