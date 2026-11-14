<?php

if(!defined('ABSPATH')) exit;



/**
 * Subscription expiry (unix timestamp) of a user; 0 when never subscribed
 */
function nasim_subscription_expires($user_id = null) {

    $user_id = $user_id ?? get_current_user_id();

    return $user_id ? (int) get_user_meta($user_id, '_nasim_subscription_expires', true) : 0;
}


function nasim_has_active_subscription($user_id = null) {

    return nasim_subscription_expires($user_id) > time();
}


function nasim_subscription_days_left($user_id = null) {

    return max(0, (int) ceil((nasim_subscription_expires($user_id) - time()) / DAY_IN_SECONDS));
}


/**
 * Extend a subscription; renewals stack on top of the remaining time
 */
function nasim_extend_subscription($user_id, $days, $plan_id = 0) {

    $starts_at  = max(time(), nasim_subscription_expires($user_id));
    $expires_at = $starts_at + ((int) $days * DAY_IN_SECONDS);

    update_user_meta($user_id, '_nasim_subscription_expires', $expires_at);

    if ($plan_id) {
        update_user_meta($user_id, '_nasim_subscription_plan', (int) $plan_id);
    }

    do_action('nasim_subscription_extended', $user_id, $expires_at, $plan_id);

    return $expires_at;
}


/**
 * Subscription page route: /subscription/
 */
function nasim_register_subscription_route() {

    add_rewrite_rule('^subscription/?$', 'index.php?nasim_page=subscription', 'top');
}
add_action('init', 'nasim_register_subscription_route');


function nasim_subscription_query_vars($vars) {

    $vars[] = 'nasim_page';

    return $vars;
}
add_filter('query_vars', 'nasim_subscription_query_vars');


function nasim_is_subscription_page() {

    return 'subscription' === get_query_var('nasim_page');
}


function nasim_subscription_url($args = []) {

    $url = get_option('permalink_structure') ? home_url('/subscription/') : add_query_arg('nasim_page', 'subscription', home_url('/'));

    return $args ? add_query_arg($args, $url) : $url;
}


function nasim_subscription_template($template) {

    if (nasim_is_subscription_page()) {
        return locate_template('templates/subscription.php') ?: $template;
    }

    return $template;
}
add_filter('template_include', 'nasim_subscription_template');


function nasim_subscription_document_title($parts) {

    if (nasim_is_subscription_page()) {
        $parts = ['title' => is_user_logged_in() ? 'پنل کاربری' : 'اشتراک', 'site' => get_bloginfo('name')];
    }

    return $parts;
}
add_filter('document_title_parts', 'nasim_subscription_document_title');


/**
 * Theme routes (subscription, login, register) are virtual pages: keep WordPress from treating them as the blog index
 */
function nasim_subscription_main_query($query) {

    // The blog route is a real, paginated post listing.
    if (!is_admin() && $query->is_main_query() && !in_array((string) $query->get('nasim_page'), ['', 'blog'], true)) {
        $query->set('posts_per_page', 1);
        $query->set('no_found_rows', true);
    }
}
add_action('pre_get_posts', 'nasim_subscription_main_query');


/**
 * Without this, a request carrying only nasim_page is parsed as the front page (menus highlight "Home")
 */
function nasim_virtual_page_flags($query) {

    if ($query->is_main_query() && '' !== (string) $query->get('nasim_page')) {
        $query->is_home = false;
    }
}
add_action('parse_query', 'nasim_virtual_page_flags');


function nasim_virtual_page_no_404($preempt, $query) {

    $page = (string) $query->get('nasim_page');

    // Blog pages past the last one should still 404.
    if ('blog' === $page && $query->get('paged') > 1 && !$query->posts) {
        return $preempt;
    }

    return '' !== $page ? true : $preempt;
}
add_filter('pre_handle_404', 'nasim_virtual_page_no_404', 10, 2);
