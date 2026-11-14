<?php

if(!defined('ABSPATH')) exit;



/**
 * Zibal payment gateway client
 *
 * @link https://help.zibal.ir/IPG/API/
 */
class Nasim_Zibal_Gateway {

    const API_URL   = 'https://gateway.zibal.ir/v1/';
    const START_URL = 'https://gateway.zibal.ir/start/';

    /**
     * Merchant code ("zibal" is the sandbox merchant)
     *
     * @var string
     */
    private $merchant;


    public function __construct($merchant) {
        $this->merchant = (string) $merchant;
    }


    public static function from_settings() {

        $merchant = get_option('nasim_zibal_sandbox') ? 'zibal' : get_option('nasim_zibal_merchant', '');

        return new self($merchant);
    }


    public function is_configured() {
        return '' !== $this->merchant;
    }


    /**
     * Create a payment request; returns the trackId or WP_Error
     *
     * @param int $amount Amount in Rial.
     */
    public function request($amount, $callback_url, $order_id, $description = '') {

        $response = $this->post('request', [
            'merchant'    => $this->merchant,
            'amount'      => (int) $amount,
            'callbackUrl' => $callback_url,
            'orderId'     => (string) $order_id,
            'description' => $description,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        if (100 !== (int) ($response['result'] ?? 0) || empty($response['trackId'])) {
            return $this->error($response);
        }

        return (string) $response['trackId'];
    }


    /**
     * Verify a payment after the callback; returns verified data or WP_Error
     *
     * Result 201 means it was verified before, which is still a successful payment.
     */
    public function verify($track_id) {

        $response = $this->post('verify', [
            'merchant' => $this->merchant,
            'trackId'  => $track_id,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $result = (int) ($response['result'] ?? 0);

        if (!in_array($result, [100, 201], true)) {
            return $this->error($response);
        }

        return [
            'amount'      => (int) ($response['amount'] ?? 0),
            'ref_number'  => (string) ($response['refNumber'] ?? ''),
            'card_number' => (string) ($response['cardNumber'] ?? ''),
            'order_id'    => (string) ($response['orderId'] ?? ''),
        ];
    }


    public function start_url($track_id) {
        return self::START_URL . rawurlencode($track_id);
    }


    private function post($endpoint, array $body) {

        $response = wp_remote_post(self::API_URL . $endpoint, [
            'timeout' => 20,
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode($body),
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('nasim_zibal_http', 'ارتباط با درگاه زیبال برقرار نشد.', $response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($data)) {
            return new WP_Error('nasim_zibal_response', 'پاسخ نامعتبر از درگاه زیبال دریافت شد.');
        }

        return $data;
    }


    private function error(array $response) {

        $result = (int) ($response['result'] ?? 0);

        return new WP_Error('nasim_zibal_' . $result, self::message($result, $response['message'] ?? ''), $response);
    }


    private static function message($result, $fallback = '') {

        $messages = [
            102 => 'کد مرچنت یافت نشد.',
            103 => 'مرچنت غیرفعال است.',
            104 => 'مرچنت نامعتبر است.',
            105 => 'مبلغ باید بیشتر از ۱۰۰۰ ریال باشد.',
            106 => 'آدرس بازگشت (callbackUrl) نامعتبر است.',
            113 => 'مبلغ تراکنش از سقف مجاز بیشتر است.',
            202 => 'پرداخت انجام نشده یا ناموفق بوده است.',
            203 => 'شناسه تراکنش (trackId) نامعتبر است.',
        ];

        return $messages[$result] ?? ($fallback ?: 'خطای ناشناخته از درگاه پرداخت.');
    }
}
