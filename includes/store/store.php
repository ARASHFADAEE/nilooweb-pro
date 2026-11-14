<?php

if(!defined('ABSPATH')) exit;



/**
 * Bump when product rewrite rules change
 */
define('NASIM_STORE_REWRITE_VERSION', '2');


/**
 * Bump when the licenses table schema changes
 */
define('NASIM_STORE_DB_VERSION', '2');


include_once THEME_DIR . '/includes/store/product-post-type.php';
include_once THEME_DIR . '/includes/store/product-functions.php';
include_once THEME_DIR . '/includes/store/product-meta.php';
include_once THEME_DIR . '/includes/store/product-catalog.php';
include_once THEME_DIR . '/includes/store/license.php';
include_once THEME_DIR . '/includes/store/domain-checkout.php';
include_once THEME_DIR . '/includes/store/download.php';
include_once THEME_DIR . '/includes/store/update-api.php';
include_once THEME_DIR . '/includes/store/admin.php';


/**
 * Flush rewrite rules once after products routes change
 */
function nasim_store_maybe_flush_rewrite_rules() {

    if (get_option('nasim_store_rewrite_version') === NASIM_STORE_REWRITE_VERSION) {
        return;
    }

    flush_rewrite_rules(false);
    update_option('nasim_store_rewrite_version', NASIM_STORE_REWRITE_VERSION);
}
add_action('init', 'nasim_store_maybe_flush_rewrite_rules', 99);


/**
 * Install licenses table and force a rewrite flush on theme switch
 */
function nasim_store_on_theme_activation() {

    delete_option('nasim_store_rewrite_version');
    nasim_install_licenses_table();
}
add_action('after_switch_theme', 'nasim_store_on_theme_activation');
