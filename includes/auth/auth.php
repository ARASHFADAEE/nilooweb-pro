<?php

if(!defined('ABSPATH')) exit;



/**
 * Bump when the login/register routes change
 */
define('NASIM_AUTH_REWRITE_VERSION', '1');


include_once THEME_DIR . '/includes/auth/settings.php';
include_once THEME_DIR . '/includes/auth/otp.php';
include_once THEME_DIR . '/includes/auth/account.php';
include_once THEME_DIR . '/includes/auth/ajax.php';


/**
 * Flush rewrite rules once when the auth routes change
 */
function nasim_auth_maybe_flush_rewrite_rules() {

    if (get_option('nasim_auth_rewrite_version') === NASIM_AUTH_REWRITE_VERSION) {
        return;
    }

    flush_rewrite_rules(false);
    update_option('nasim_auth_rewrite_version', NASIM_AUTH_REWRITE_VERSION);
}
add_action('init', 'nasim_auth_maybe_flush_rewrite_rules', 99);


function nasim_auth_on_theme_activation() {

    delete_option('nasim_auth_rewrite_version');
}
add_action('after_switch_theme', 'nasim_auth_on_theme_activation');
