<?php

if(!defined('ABSPATH')) exit;



function nasim_licenses_table() {

    global $wpdb;

    return $wpdb->prefix . 'nasim_licenses';
}


/**
 * Default free domain slots included per product (plugin/theme)
 */
function nasim_license_default_slots() {

    return max(1, (int) get_option('nasim_license_default_slots', 1));
}


/**
 * Price in Toman for buying one extra domain slot for a single product
 */
function nasim_domain_slot_price() {

    return max(0, (int) get_option('nasim_domain_slot_price', 90000));
}


/**
 * Marker stored on payments that buy an extra domain activation slot
 */
function nasim_domain_slot_payment_title() {

    return 'افزودن دامنه';
}


function nasim_is_domain_slot_payment($payment) {

    return $payment
        && 0 === (int) $payment->plan_id
        && 0 === (int) $payment->duration_days
        && 0 === strpos((string) $payment->plan_title, nasim_domain_slot_payment_title());
}


/**
 * Product package slug stored on a domain-slot payment (note: domain_slot:slug)
 */
function nasim_domain_slot_payment_slug($payment) {

    if (!$payment || empty($payment->note)) {
        return '';
    }

    if (preg_match('/^domain_slot:(.+)$/', (string) $payment->note, $m)) {
        return sanitize_title($m[1]);
    }

    return '';
}


/**
 * One license key per subscriber; valid while the subscription is active
 */
function nasim_install_licenses_table() {

    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $table   = nasim_licenses_table();
    $charset = $wpdb->get_charset_collate();

    dbDelta("CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        license_key varchar(64) NOT NULL,
        max_activations smallint(5) unsigned NOT NULL DEFAULT 1,
        activations longtext NULL,
        status varchar(20) NOT NULL DEFAULT 'active',
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY license_key (license_key),
        KEY user_id (user_id),
        KEY status (status)
    ) {$charset};");

    update_option('nasim_store_db_version', NASIM_STORE_DB_VERSION);
}


function nasim_maybe_install_licenses_table() {

    if (get_option('nasim_store_db_version') !== NASIM_STORE_DB_VERSION) {
        nasim_install_licenses_table();
    }
}
add_action('admin_init', 'nasim_maybe_install_licenses_table');


/**
 * Generate a readable license key: XXXX-XXXX-XXXX-XXXX
 */
function nasim_generate_license_key() {

    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $chunks   = [];

    for ($i = 0; $i < 4; $i++) {
        $chunk = '';
        for ($j = 0; $j < 4; $j++) {
            $chunk .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $chunks[] = $chunk;
    }

    return implode('-', $chunks);
}


function nasim_get_user_license($user_id) {

    global $wpdb;

    $user_id = (int) $user_id;

    if (!$user_id) {
        return null;
    }

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM " . nasim_licenses_table() . " WHERE user_id = %d AND status = 'active' ORDER BY id DESC LIMIT 1",
            $user_id
        )
    );
}


function nasim_get_license_by_id($license_id) {

    global $wpdb;

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM " . nasim_licenses_table() . " WHERE id = %d LIMIT 1",
            (int) $license_id
        )
    );
}


function nasim_get_license_by_key($license_key) {

    global $wpdb;

    $license_key = strtoupper(trim((string) $license_key));

    if ('' === $license_key) {
        return null;
    }

    return $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM " . nasim_licenses_table() . " WHERE license_key = %s LIMIT 1",
            $license_key
        )
    );
}


/**
 * Create a license for the user if they do not already have an active one
 */
function nasim_ensure_user_license($user_id) {

    global $wpdb;

    $user_id  = (int) $user_id;
    $existing = nasim_get_user_license($user_id);

    if ($existing) {
        return $existing;
    }

    $key = nasim_generate_license_key();

    for ($i = 0; $i < 5 && nasim_get_license_by_key($key); $i++) {
        $key = nasim_generate_license_key();
    }

    $wpdb->insert(nasim_licenses_table(), [
        'user_id'         => $user_id,
        'license_key'     => $key,
        'max_activations' => nasim_license_default_slots(),
        'activations'     => wp_json_encode([]),
        'status'          => 'active',
        'created_at'      => current_time('mysql', true),
    ], ['%d', '%s', '%d', '%s', '%s', '%s']);

    return nasim_get_user_license($user_id);
}


/**
 * Issue a license when a subscription is first activated or extended
 */
function nasim_issue_license_on_subscription($user_id) {

    nasim_ensure_user_license($user_id);
}
add_action('nasim_subscription_extended', 'nasim_issue_license_on_subscription', 10, 1);


function nasim_license_activations($license) {

    if (!$license || empty($license->activations)) {
        return [];
    }

    $decoded = json_decode($license->activations, true);

    return is_array($decoded) ? $decoded : [];
}


/**
 * Activations belonging to one product slug
 *
 * Legacy rows without "slug" count for every product until migrated on next activate.
 */
function nasim_license_product_activations($license, $slug) {

    $slug = sanitize_title($slug);

    return array_values(array_filter(nasim_license_activations($license), function ($row) use ($slug) {
        $row_slug = sanitize_title($row['slug'] ?? '');

        return '' === $row_slug || $row_slug === $slug;
    }));
}


/**
 * Extra paid domain slots per product slug (user meta)
 */
function nasim_get_product_extra_slots($user_id) {

    $stored = get_user_meta((int) $user_id, '_nasim_product_extra_slots', true);

    return is_array($stored) ? $stored : [];
}


function nasim_product_domain_limit($license, $slug) {

    $slug   = sanitize_title($slug);
    $base   = max(1, (int) ($license->max_activations ?? nasim_license_default_slots()));
    $extras = nasim_get_product_extra_slots((int) $license->user_id);

    return $base + max(0, (int) ($extras[$slug] ?? 0));
}


/**
 * A license unlocks downloads/updates only while the owner has an active subscription
 */
function nasim_license_is_valid($license) {

    if (!$license || 'active' !== $license->status) {
        return false;
    }

    return nasim_has_active_subscription((int) $license->user_id)
        || user_can((int) $license->user_id, 'manage_options');
}


/**
 * Normalize a domain for activation matching
 */
function nasim_normalize_domain($domain) {

    $domain = strtolower(trim((string) $domain));
    $domain = preg_replace('#^https?://#', '', $domain);
    $domain = preg_replace('#/.*$#', '', $domain);
    $domain = preg_replace('#:\d+$#', '', $domain);
    $domain = preg_replace('#^www\.#', '', $domain);

    return $domain;
}


/**
 * Add one paid domain slot for a specific product slug
 */
function nasim_add_product_domain_slot($user_id, $slug, $slots = 1) {

    $user_id = (int) $user_id;
    $slug    = sanitize_title($slug);
    $slots   = max(1, (int) $slots);

    if (!$user_id || '' === $slug) {
        return false;
    }

    nasim_ensure_user_license($user_id);

    $extras        = nasim_get_product_extra_slots($user_id);
    $extras[$slug] = max(0, (int) ($extras[$slug] ?? 0)) + $slots;

    update_user_meta($user_id, '_nasim_product_extra_slots', $extras);

    return true;
}


/**
 * @deprecated Use nasim_add_product_domain_slot(); kept for older callbacks without slug
 */
function nasim_add_license_activation_slot($user_id, $slots = 1) {

    global $wpdb;

    $license = nasim_ensure_user_license($user_id);

    if (!$license) {
        return false;
    }

    $slots = max(1, (int) $slots);
    $next  = (int) $license->max_activations + $slots;

    $wpdb->update(
        nasim_licenses_table(),
        ['max_activations' => $next],
        ['id' => (int) $license->id],
        ['%d'],
        ['%d']
    );

    return nasim_get_license_by_id($license->id);
}


/**
 * Set base free domains per product (admin)
 */
function nasim_set_license_max_activations($license_id, $max) {

    global $wpdb;

    $max = max(1, (int) $max);

    return false !== $wpdb->update(
        nasim_licenses_table(),
        ['max_activations' => $max],
        ['id' => (int) $license_id],
        ['%d'],
        ['%d']
    );
}


/**
 * Activate a license for one product on one domain
 *
 * Rule: each product gets its own free domain slot(s). Same domain may hold many products.
 *
 * @return true|WP_Error
 */
function nasim_activate_license($license_key, $domain, $slug = '') {

    global $wpdb;

    $license = nasim_get_license_by_key($license_key);
    $slug    = sanitize_title($slug);

    if (!$license) {
        return new WP_Error('invalid_license', 'لایسنس معتبر نیست.');
    }

    if (!nasim_license_is_valid($license)) {
        return new WP_Error('inactive_subscription', 'اشتراک مربوط به این لایسنس فعال نیست.');
    }

    if ('' === $slug) {
        return new WP_Error('missing_slug', 'اسلاگ محصول برای فعال‌سازی لازم است.');
    }

    if (!nasim_get_product_by_slug($slug)) {
        return new WP_Error('unknown_product', 'این محصول در فروشگاه تعریف نشده است.');
    }

    $domain = nasim_normalize_domain($domain);

    if ('' === $domain) {
        return new WP_Error('invalid_domain', 'دامنه نامعتبر است.');
    }

    $activations = nasim_license_activations($license);

    foreach ($activations as $row) {
        $row_slug = sanitize_title($row['slug'] ?? '');
        if (($row['domain'] ?? '') === $domain && ($row_slug === $slug || '' === $row_slug)) {
            // Migrate legacy domain-only rows to this product.
            if ('' === $row_slug) {
                foreach ($activations as &$item) {
                    if (($item['domain'] ?? '') === $domain && empty($item['slug'])) {
                        $item['slug'] = $slug;
                    }
                }
                unset($item);
                $wpdb->update(
                    nasim_licenses_table(),
                    ['activations' => wp_json_encode(array_values($activations))],
                    ['id' => (int) $license->id],
                    ['%s'],
                    ['%d']
                );
            }
            return true;
        }
    }

    $used  = count(nasim_license_product_activations($license, $slug));
    $limit = nasim_product_domain_limit($license, $slug);

    if ($used >= $limit) {
        return new WP_Error(
            'activation_limit',
            sprintf(
                'برای این افزونه فقط %s دامنه رایگان/خریداری‌شده مجاز است. دامنه فعلی را آزاد کن یا از پنل کاربری دامنه اضافه بخر.',
                nasim_fa_digits($limit)
            )
        );
    }

    $activations[] = [
        'slug'         => $slug,
        'domain'       => $domain,
        'activated_at' => gmdate('c'),
    ];

    $wpdb->update(
        nasim_licenses_table(),
        ['activations' => wp_json_encode($activations)],
        ['id' => (int) $license->id],
        ['%s'],
        ['%d']
    );

    return true;
}


/**
 * Remove a product↔domain activation
 *
 * @return true|WP_Error
 */
function nasim_deactivate_license($license_key, $domain, $slug = '') {

    global $wpdb;

    $license = nasim_get_license_by_key($license_key);

    if (!$license) {
        return new WP_Error('invalid_license', 'لایسنس معتبر نیست.');
    }

    $domain = nasim_normalize_domain($domain);
    $slug   = sanitize_title($slug);

    $filtered = array_values(array_filter(nasim_license_activations($license), function ($row) use ($domain, $slug) {
        if (($row['domain'] ?? '') !== $domain) {
            return true;
        }

        if ('' === $slug) {
            return false;
        }

        $row_slug = sanitize_title($row['slug'] ?? '');

        return '' !== $row_slug && $row_slug !== $slug;
    }));

    $wpdb->update(
        nasim_licenses_table(),
        ['activations' => wp_json_encode($filtered)],
        ['id' => (int) $license->id],
        ['%s'],
        ['%d']
    );

    return true;
}


/**
 * Whether this license+product is activated for the given domain
 */
function nasim_license_is_activated_for_domain($license, $domain, $slug = '') {

    $domain = nasim_normalize_domain($domain);
    $slug   = sanitize_title($slug);

    foreach (nasim_license_activations($license) as $row) {
        if (($row['domain'] ?? '') !== $domain) {
            continue;
        }

        $row_slug = sanitize_title($row['slug'] ?? '');

        if ('' === $slug || '' === $row_slug || $row_slug === $slug) {
            return true;
        }
    }

    return false;
}


/**
 * Full status check used by plugins/themes on the customer site
 *
 * @return array|WP_Error
 */
function nasim_license_status($license_key, $domain = '', $slug = '') {

    $license = nasim_get_license_by_key($license_key);

    if (!$license) {
        return new WP_Error('invalid_license', 'لایسنس معتبر نیست.');
    }

    $valid       = nasim_license_is_valid($license);
    $domain      = $domain ? nasim_normalize_domain($domain) : '';
    $slug        = sanitize_title($slug);
    $product_rows = $slug ? nasim_license_product_activations($license, $slug) : nasim_license_activations($license);
    $limit        = $slug ? nasim_product_domain_limit($license, $slug) : (int) $license->max_activations;
    $activated    = ($domain && $slug) ? nasim_license_is_activated_for_domain($license, $domain, $slug) : null;

    return [
        'valid'            => $valid,
        'status'           => $license->status,
        'subscription'     => $valid,
        'slug'             => $slug ?: null,
        'expires'          => nasim_subscription_expires((int) $license->user_id) ?: null,
        'expires_iso'      => nasim_subscription_expires((int) $license->user_id)
            ? gmdate('c', nasim_subscription_expires((int) $license->user_id))
            : null,
        'domain'           => $domain ?: null,
        'domain_activated' => $activated,
        'activations_used' => count($product_rows),
        'max_activations'  => $limit,
        'slots_remaining'  => max(0, $limit - count($product_rows)),
        'domains'          => array_values(array_unique(array_filter(array_map(function ($row) {
            return $row['domain'] ?? '';
        }, $product_rows)))),
        'rule'             => 'هر افزونه به‌صورت پیش‌فرض یک دامنه رایگان دارد؛ دامنه اضافه جداگانه خریداری می‌شود.',
    ];
}


/**
 * Paginated licenses for the admin list
 */
function nasim_get_licenses($page = 1, $per_page = 30, $search = '') {

    global $wpdb;

    $table  = nasim_licenses_table();
    $offset = max(0, ($page - 1) * $per_page);
    $where  = '';
    $params = [];

    if ($search) {
        $like    = '%' . $wpdb->esc_like($search) . '%';
        $user_ids = [];

        $users = get_users([
            'search'         => '*' . $search . '*',
            'search_columns' => ['user_login', 'user_email', 'display_name'],
            'fields'         => 'ID',
            'number'         => 50,
        ]);

        $user_ids = array_map('intval', $users);

        if ($user_ids) {
            $in     = implode(',', $user_ids);
            $where  = "WHERE (license_key LIKE %s OR user_id IN ({$in}))";
            $params[] = $like;
        } else {
            $where    = 'WHERE license_key LIKE %s';
            $params[] = $like;
        }
    }

    // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
    if ($params) {
        $sql_items = "SELECT * FROM {$table} {$where} ORDER BY id DESC LIMIT %d OFFSET %d";
        $sql_count = "SELECT COUNT(*) FROM {$table} {$where}";
        $items     = $wpdb->get_results($wpdb->prepare($sql_items, ...array_merge($params, [$per_page, $offset])));
        $total     = (int) $wpdb->get_var($wpdb->prepare($sql_count, ...$params));
    } else {
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ));
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }
    // phpcs:enable

    return ['items' => $items ?: [], 'total' => $total];
}
