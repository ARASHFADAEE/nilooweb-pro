<?php

if(!defined('ABSPATH')) exit;



/**
 * Rah Payam (msgway.com) REST client: template messages over SMS
 *
 * Codes are generated and verified by the theme and passed in "code", so the /otp/verify endpoint is not used.
 *
 * @link https://msgway.com/
 */
class Nasim_Msgway {

    const API_URL = 'https://api.msgway.com/';

    /**
     * SMS lines accepted in "provider"; 0 lets Rah Payam pick
     */
    const LINES = [
        1 => 'مگفا (3000)',
        2 => 'آتیه (2000)',
        3 => 'آسیاتک (9000)',
        5 => 'ارمغان راه طلایی (50004)',
    ];

    private $api_key;

    private $line;


    public function __construct($api_key, $line = 0) {

        $this->api_key = trim((string) $api_key);
        $this->line    = isset(self::LINES[(int) $line]) ? (int) $line : 0;
    }


    public static function from_settings() {

        return new self(nasim_setting('msgway_api_key'), nasim_setting('msgway_line'));
    }


    public function is_configured() {

        return '' !== $this->api_key;
    }


    /**
     * Send a template message; the template decides where "code" and the positional $params appear
     *
     * @return string|WP_Error  referenceID on success
     */
    public function send($mobile, $template_id, array $params = [], $code = '') {

        if (!absint($template_id)) {
            return new WP_Error('msgway_template', __('کد قالب پیامک تنظیم نشده است.', 'niloo-web'));
        }

        $body = [
            'mobile'     => (string) $mobile,
            'method'     => 'sms',
            'templateID' => absint($template_id),
        ];

        if ($this->line) {
            $body['provider'] = $this->line;
        }

        if ('' !== (string) $code) {
            $body['code'] = (string) $code;
        }

        // API limits: up to 10 parameters, 40 characters each.
        if ($params) {
            $body['params'] = array_map(function ($value) {
                return mb_substr((string) $value, 0, 40);
            }, array_slice(array_values($params), 0, 10));
        }

        $result = $this->request('send', $body);

        return is_wp_error($result) ? $result : (string) ($result['referenceID'] ?? '');
    }


    private function request($path, array $body) {

        if (!$this->is_configured()) {
            return new WP_Error('msgway_not_configured', __('کلید API راه پیام تنظیم نشده است.', 'niloo-web'));
        }

        $response = wp_remote_post(self::API_URL . $path, [
            'timeout' => 15,
            'headers' => [
                'apiKey'          => $this->api_key,
                'accept-language' => 'fa',
                'Accept'          => 'application/json',
                'Content-Type'    => 'application/json',
            ],
            'body'    => wp_json_encode($body),
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('msgway_http', __('ارتباط با سامانه پیامک برقرار نشد.', 'niloo-web'), $response->get_error_message());
        }

        $http = (int) wp_remote_retrieve_response_code($response);
        $json = json_decode(wp_remote_retrieve_body($response), true);

        if (200 === $http && is_array($json) && 'success' === ($json['status'] ?? '')) {
            return $json;
        }

        $message = is_array($json) && !empty($json['error']['message']) ? sanitize_text_field($json['error']['message']) : self::http_error_message($http);

        return new WP_Error('msgway_error', $message, ['http' => $http, 'code' => is_array($json) ? ($json['error']['code'] ?? null) : null]);
    }


    private static function http_error_message($http) {

        $messages = [
            400 => __('درخواست نامعتبر است (شماره، قالب یا پارامترها را بررسی کنید).', 'niloo-web'),
            401 => __('کلید API نامعتبر است.', 'niloo-web'),
            403 => __('دسترسی به این قالب یا خط مجاز نیست.', 'niloo-web'),
            429 => __('تعداد درخواست‌ها بیش از حد مجاز است.', 'niloo-web'),
            500 => __('خطای داخلی سامانه پیامک.', 'niloo-web'),
        ];

        return $messages[$http] ?? sprintf(__('خطای سامانه پیامک (کد %d).', 'niloo-web'), $http);
    }
}
