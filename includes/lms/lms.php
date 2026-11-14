<?php

if(!defined('ABSPATH')) exit;



/**
 * Bump when rewrite rules (courses, learn endpoint, subscription page) change
 */
define('NASIM_LMS_REWRITE_VERSION', '2');


/**
 * Bump when the payments table schema changes
 */
define('NASIM_LMS_DB_VERSION', '2');


include_once THEME_DIR . '/includes/lms/helpers.php';
include_once THEME_DIR . '/includes/lms/course-post-type.php';
include_once THEME_DIR . '/includes/lms/course-taxonomy.php';
include_once THEME_DIR . '/includes/lms/course-catalog.php';
include_once THEME_DIR . '/includes/lms/course-functions.php';
include_once THEME_DIR . '/includes/lms/course-meta-boxes.php';
include_once THEME_DIR . '/includes/lms/plan-post-type.php';
include_once THEME_DIR . '/includes/lms/subscription.php';
include_once THEME_DIR . '/includes/lms/access.php';
include_once THEME_DIR . '/includes/lms/payments.php';
include_once THEME_DIR . '/includes/lms/class-nasim-zibal-gateway.php';
include_once THEME_DIR . '/includes/lms/class-nasim-zarinpal-gateway.php';
include_once THEME_DIR . '/includes/lms/gateway.php';
include_once THEME_DIR . '/includes/lms/checkout.php';
include_once THEME_DIR . '/includes/lms/express-checkout.php';
include_once THEME_DIR . '/includes/lms/ajax.php';
include_once THEME_DIR . '/includes/lms/admin.php';
include_once THEME_DIR . '/includes/lms/woo-import.php';
include_once THEME_DIR . '/includes/lms/niloo-course-seed.php';
include_once THEME_DIR . '/includes/integrations/spotplayer.php';


/**
 * Flush rewrite rules once after activation or when the LMS routes change
 */
function nasim_lms_maybe_flush_rewrite_rules() {

    if (get_option('nasim_rewrite_version') === NASIM_LMS_REWRITE_VERSION) {
        return;
    }

    flush_rewrite_rules(false);
    update_option('nasim_rewrite_version', NASIM_LMS_REWRITE_VERSION);
}
add_action('init', 'nasim_lms_maybe_flush_rewrite_rules', 99);


/**
 * Force a flush on theme activation (routes are registered by then, see check_theme_switched on init:99)
 */
function nasim_lms_on_theme_activation() {

    delete_option('nasim_rewrite_version');
    nasim_install_payments_table();
}
add_action('after_switch_theme', 'nasim_lms_on_theme_activation');
