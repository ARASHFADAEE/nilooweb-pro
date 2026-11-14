<?php

if(!defined('ABSPATH')) exit;



/**
 * REST API for license activation and WordPress plugin/theme update checks
 *
 * Endpoints:
 * - POST /wp-json/nasim/v1/license/activate   { license, domain, slug }
 * - POST /wp-json/nasim/v1/license/deactivate { license, domain, slug }
 * - GET  /wp-json/nasim/v1/license/status     ?license=&domain=&slug=
 * - GET  /wp-json/nasim/v1/update/{slug}?license=&domain=
 */
function nasim_register_store_rest_routes() {

    register_rest_route('nasim/v1', '/license/activate', [
        'methods'             => 'POST',
        'callback'            => 'nasim_rest_activate_license',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('nasim/v1', '/license/deactivate', [
        'methods'             => 'POST',
        'callback'            => 'nasim_rest_deactivate_license',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('nasim/v1', '/license/status', [
        'methods'             => 'GET',
        'callback'            => 'nasim_rest_license_status',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('nasim/v1', '/update/(?P<slug>[a-z0-9\-]+)', [
        'methods'             => 'GET',
        'callback'            => 'nasim_rest_product_update',
        'permission_callback' => '__return_true',
        'args'                => [
            'slug' => [
                'required' => true,
                'type'     => 'string',
            ],
        ],
    ]);
}
add_action('rest_api_init', 'nasim_register_store_rest_routes');


function nasim_rest_request_license_args(WP_REST_Request $request) {

    $params = $request->get_json_params();

    if (!is_array($params)) {
        $params = [];
    }

    return [
        'license' => strtoupper(sanitize_text_field($params['license'] ?? $request->get_param('license') ?? '')),
        'domain'  => sanitize_text_field($params['domain'] ?? $request->get_param('domain') ?? ''),
        'slug'    => sanitize_title($params['slug'] ?? $request->get_param('slug') ?? ''),
    ];
}


function nasim_rest_activate_license(WP_REST_Request $request) {

    $args   = nasim_rest_request_license_args($request);
    $result = nasim_activate_license($args['license'], $args['domain'], $args['slug']);

    if (is_wp_error($result)) {
        return new WP_REST_Response([
            'success' => false,
            'code'    => $result->get_error_code(),
            'message' => $result->get_error_message(),
        ], 400);
    }

    $license = nasim_get_license_by_key($args['license']);
    $limit   = nasim_product_domain_limit($license, $args['slug']);
    $used    = count(nasim_license_product_activations($license, $args['slug']));

    return [
        'success'          => true,
        'message'          => 'لایسنس این افزونه روی دامنه فعال شد.',
        'slug'             => $args['slug'],
        'domain'           => nasim_normalize_domain($args['domain']),
        'expires'          => nasim_has_active_subscription((int) $license->user_id)
            ? gmdate('c', nasim_subscription_expires((int) $license->user_id))
            : null,
        'activations_used' => $used,
        'max_activations'  => $limit,
    ];
}


function nasim_rest_deactivate_license(WP_REST_Request $request) {

    $args   = nasim_rest_request_license_args($request);
    $result = nasim_deactivate_license($args['license'], $args['domain'], $args['slug']);

    if (is_wp_error($result)) {
        return new WP_REST_Response([
            'success' => false,
            'code'    => $result->get_error_code(),
            'message' => $result->get_error_message(),
        ], 400);
    }

    return [
        'success' => true,
        'message' => 'دامنه این افزونه آزاد شد.',
    ];
}


function nasim_rest_license_status(WP_REST_Request $request) {

    $args   = nasim_rest_request_license_args($request);
    $status = nasim_license_status($args['license'], $args['domain'], $args['slug']);

    if (is_wp_error($status)) {
        return new WP_REST_Response([
            'success' => false,
            'code'    => $status->get_error_code(),
            'message' => $status->get_error_message(),
        ], 400);
    }

    return array_merge(['success' => true], $status);
}


/**
 * Update payload compatible with WordPress plugins_api / themes_api consumers
 */
function nasim_rest_product_update(WP_REST_Request $request) {

    $slug        = sanitize_title($request['slug']);
    $license_key = strtoupper(sanitize_text_field($request->get_param('license') ?? ''));
    $domain      = sanitize_text_field($request->get_param('domain') ?? '');

    $product = nasim_get_product_by_slug($slug);

    if (!$product) {
        return new WP_REST_Response([
            'success' => false,
            'code'    => 'not_found',
            'message' => 'محصول پیدا نشد.',
        ], 404);
    }

    $license = nasim_get_license_by_key($license_key);

    if (!$license || !nasim_license_is_valid($license)) {
        return new WP_REST_Response([
            'success' => false,
            'code'    => 'invalid_license',
            'message' => 'لایسنس معتبر نیست یا اشتراک منقضی شده.',
        ], 403);
    }

    if ($domain && !nasim_license_is_activated_for_domain($license, $domain, $slug)) {
        return new WP_REST_Response([
            'success' => false,
            'code'    => 'not_activated',
            'message' => 'این دامنه برای این افزونه فعال نشده است.',
        ], 403);
    }

    $meta = nasim_get_product_meta($product->ID);

    if (!$meta['file_id'] || !nasim_get_product_file_path($product->ID)) {
        return new WP_REST_Response([
            'success' => false,
            'code'    => 'no_package',
            'message' => 'فایل آپدیت آماده نیست.',
        ], 404);
    }

    $package = nasim_product_update_package_url($product->ID, $license_key);

    return [
        'success'       => true,
        'slug'          => $meta['slug'],
        'name'          => get_the_title($product),
        'version'       => $meta['version'],
        'requires'      => $meta['requires'],
        'tested'        => $meta['tested'],
        'homepage'      => $meta['homepage'] ?: get_permalink($product),
        'download_url'  => $package,
        'package'       => $package,
        'changelog'     => $meta['changelog'],
        'last_updated'  => get_post_modified_time('c', true, $product),
        'expires'       => gmdate('c', nasim_subscription_expires((int) $license->user_id)),
        'new_version'   => $meta['version'],
        'url'           => get_permalink($product),
    ];
}
