<?php

if(!defined('ABSPATH')) exit;



/**
 * sms.ir REST client (v1): verify (pattern) messages and account credit
 *
 * @link https://sms.ir/rest-api/
 */
class Nasim_SmsIr {

    const API_URL = 'https://api.sms.ir/v1/';

    private $api_key;


    public function __construct($api_key) {

        $this->api_key = trim((string) $api_key);
    }


    public static function from_settings() {

        return new self(nasim_setting('smsir_api_key'));
    }


    public function is_configured() {

        return '' !== $this->api_key;
    }


    /**
     * Send a pattern message; $parameters maps template variable names (without #) to values
     *
     * @return array|WP_Error  ['messageId' => int, 'cost' => float] on success
     */
    public function send_verify($mobile, $template_id, array $parameters) {

        if (!absint($template_id)) {
            return new WP_Error('smsir_template', __('کد قالب پیامک تنظیم نشده است.', 'nasim-fitness'));
        }

        $body = [
            'mobile'     => (string) $mobile,
            'templateId' => absint($template_id),
            'parameters' => [],
        ];

        foreach ($parameters as $name => $value) {
            $body['parameters'][] = ['name' => (string) $name, 'value' => (string) $value];
        }

        return $this->request('POST', 'send/verify', $body);
    }


    /**
     * @return float|WP_Error
     */
    public function credit() {

        return $this->request('GET', 'credit');
    }


    private function request($method, $path, $body = null) {

        if (!$this->is_configured()) {
            return new WP_Error('smsir_not_configured', __('کلید API پیامک تنظیم نشده است.', 'nasim-fitness'));
        }

        $args = [
            'method'  => $method,
            'timeout' => 15,
            'headers' => [
                'Accept'    => 'application/json',
                'x-api-key' => $this->api_key,
            ],
        ];

        if (null !== $body) {
            $args['headers']['Content-Type'] = 'application/json';
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request(self::API_URL . $path, $args);

        if (is_wp_error($response)) {
            return new WP_Error('smsir_http', __('ارتباط با سامانه پیامک برقرار نشد.', 'nasim-fitness'), $response->get_error_message());
        }

        $http = (int) wp_remote_retrieve_response_code($response);
        $json = json_decode(wp_remote_retrieve_body($response), true);

        if (200 === $http && is_array($json) && 1 === (int) ($json['status'] ?? 0)) {
            return $json['data'] ?? null;
        }

        $message = is_array($json) && !empty($json['message']) ? sanitize_text_field($json['message']) : self::http_error_message($http);

        return new WP_Error('smsir_error', $message, ['http' => $http, 'status' => is_array($json) ? ($json['status'] ?? null) : null]);
    }


    private static function http_error_message($http) {

        $messages = [
            401 => __('کلید API نامعتبر است.', 'nasim-fitness'),
            429 => __('تعداد درخواست‌ها بیش از حد مجاز است.', 'nasim-fitness'),
            500 => __('خطای داخلی سامانه پیامک.', 'nasim-fitness'),
        ];

        return $messages[$http] ?? sprintf(__('خطای سامانه پیامک (کد %d).', 'nasim-fitness'), $http);
    }
}
