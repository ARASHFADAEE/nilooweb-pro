<?php
/**
 * Nasim License Client — drop this file into your plugin/theme.
 *
 * Usage:
 *   Nasim_License_Client::instance([
 *     'api_home' => 'https://yoursite.com/',
 *     'slug'     => 'my-plugin-slug',
 *     'version'  => '1.0.0',
 *     'file'     => __FILE__, // main plugin file or style.css for themes
 *     'option'   => 'my_plugin_license_key',
 *   ]);
 *
 * Then call activate()/status()/deactivate() from your settings page.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Nasim_License_Client')) :

class Nasim_License_Client {

    /** @var self|null */
    private static $instance = null;

    /** @var array */
    private $config;

    public static function instance(array $config = []) {

        if (null === self::$instance) {
            self::$instance = new self($config);
        } elseif ($config) {
            self::$instance->config = array_merge(self::$instance->config, $config);
        }

        return self::$instance;
    }

    private function __construct(array $config) {

        $this->config = wp_parse_args($config, [
            'api_home' => home_url('/'),
            'slug'     => '',
            'version'  => '1.0.0',
            'file'     => '',
            'option'   => 'nasim_license_key',
        ]);

        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_plugin_update']);
        add_filter('pre_set_site_transient_update_themes', [$this, 'check_theme_update']);
        add_filter('plugins_api', [$this, 'plugins_api'], 20, 3);
    }

    public function api_url($path = '') {

        return trailingslashit($this->config['api_home']) . 'wp-json/nasim/v1/' . ltrim($path, '/');
    }

    public function current_domain() {

        $host = wp_parse_url(home_url('/'), PHP_URL_HOST);

        return is_string($host) ? preg_replace('#^www\.#', '', strtolower($host)) : '';
    }

    public function get_license_key() {

        return strtoupper(trim((string) get_option($this->config['option'], '')));
    }

    /**
     * @return array|WP_Error
     */
    public function request($method, $path, array $body = []) {

        $url  = $this->api_url($path);
        $args = [
            'timeout' => 20,
            'headers' => ['Accept' => 'application/json'],
        ];

        if ('GET' === strtoupper($method)) {
            $url  = add_query_arg($body, $url);
            $response = wp_remote_get($url, $args);
        } else {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode($body);
            $response = wp_remote_post($url, $args);
        }

        if (is_wp_error($response)) {
            return $response;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($data)) {
            return new WP_Error('bad_response', 'پاسخ سرور لایسنس نامعتبر است.');
        }

        if ($code >= 400 || (isset($data['success']) && false === $data['success'])) {
            return new WP_Error(
                $data['code'] ?? 'api_error',
                $data['message'] ?? 'خطا در ارتباط با سرور لایسنس.'
            );
        }

        return $data;
    }

    /**
     * Activate this site's domain against the license key
     *
     * @return array|WP_Error
     */
    public function activate($license_key = '') {

        $license_key = strtoupper(trim($license_key ?: $this->get_license_key()));

        if ('' === $license_key) {
            return new WP_Error('empty_license', 'کلید لایسنس خالی است.');
        }

        $result = $this->request('POST', 'license/activate', [
            'license' => $license_key,
            'domain'  => $this->current_domain(),
            'slug'    => $this->config['slug'],
        ]);

        if (!is_wp_error($result)) {
            update_option($this->config['option'], $license_key);
        }

        return $result;
    }

    /**
     * @return array|WP_Error
     */
    public function deactivate($license_key = '') {

        $license_key = strtoupper(trim($license_key ?: $this->get_license_key()));

        return $this->request('POST', 'license/deactivate', [
            'license' => $license_key,
            'domain'  => $this->current_domain(),
            'slug'    => $this->config['slug'],
        ]);
    }

    /**
     * @return array|WP_Error
     */
    public function status($license_key = '') {

        $license_key = strtoupper(trim($license_key ?: $this->get_license_key()));

        return $this->request('GET', 'license/status', [
            'license' => $license_key,
            'domain'  => $this->current_domain(),
            'slug'    => $this->config['slug'],
        ]);
    }

    /**
     * Whether this install may use premium features
     */
    public function is_active() {

        $status = $this->status();

        return !is_wp_error($status)
            && !empty($status['valid'])
            && !empty($status['domain_activated']);
    }

    private function fetch_update() {

        $license = $this->get_license_key();

        if ('' === $license || '' === $this->config['slug']) {
            return null;
        }

        $data = $this->request('GET', 'update/' . rawurlencode($this->config['slug']), [
            'license' => $license,
            'domain'  => $this->current_domain(),
        ]);

        return is_wp_error($data) ? null : $data;
    }

    public function check_plugin_update($transient) {

        if (!is_object($transient) || empty($this->config['file'])) {
            return $transient;
        }

        $basename = plugin_basename($this->config['file']);
        $remote   = $this->fetch_update();

        if (!$remote || empty($remote['version']) || version_compare($this->config['version'], $remote['version'], '>=')) {
            return $transient;
        }

        $transient->response[$basename] = (object) [
            'slug'        => $this->config['slug'],
            'plugin'      => $basename,
            'new_version' => $remote['version'],
            'url'         => $remote['url'] ?? '',
            'package'     => $remote['package'] ?? '',
            'tested'      => $remote['tested'] ?? '',
        ];

        return $transient;
    }

    public function check_theme_update($transient) {

        if (!is_object($transient) || empty($this->config['slug'])) {
            return $transient;
        }

        $remote = $this->fetch_update();

        if (!$remote || empty($remote['version']) || version_compare($this->config['version'], $remote['version'], '>=')) {
            return $transient;
        }

        $transient->response[$this->config['slug']] = [
            'theme'       => $this->config['slug'],
            'new_version' => $remote['version'],
            'url'         => $remote['url'] ?? '',
            'package'     => $remote['package'] ?? '',
        ];

        return $transient;
    }

    public function plugins_api($result, $action, $args) {

        if ('plugin_information' !== $action || empty($args->slug) || $args->slug !== $this->config['slug']) {
            return $result;
        }

        $remote = $this->fetch_update();

        if (!$remote) {
            return $result;
        }

        return (object) [
            'name'          => $remote['name'] ?? $this->config['slug'],
            'slug'          => $this->config['slug'],
            'version'       => $remote['version'] ?? '',
            'homepage'      => $remote['homepage'] ?? '',
            'download_link' => $remote['package'] ?? '',
            'sections'      => [
                'changelog' => wpautop($remote['changelog'] ?? ''),
            ],
            'requires'      => $remote['requires'] ?? '',
            'tested'        => $remote['tested'] ?? '',
        ];
    }
}

endif;
