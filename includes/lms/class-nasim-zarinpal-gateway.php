<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ZarinPal payment gateway (v4 API, amounts in Rial).
 *
 * @link https://docs.zarinpal.com/paymentGateway/
 */
class Nasim_Zarinpal_Gateway {

    const API_URL     = 'https://api.zarinpal.com/pg/v4/payment/';
    const SANDBOX_API = 'https://sandbox.zarinpal.com/pg/v4/payment/';
    const START_URL   = 'https://www.zarinpal.com/pg/StartPay/';
    const SANDBOX_START = 'https://sandbox.zarinpal.com/pg/StartPay/';

    /** @var string */
    private $merchant;

    /** @var bool */
    private $sandbox;


    public function __construct($merchant, $sandbox = false) {
        $this->merchant = trim((string) $merchant);
        $this->sandbox  = (bool) $sandbox;
    }


    public static function from_settings() {

        $sandbox  = (bool) nasim_opt('zarinpal_sandbox', false);
        $merchant = (string) nasim_opt('zarinpal_merchant', '');

        return new self($merchant, $sandbox);
    }


    public function is_configured() {
        return '' !== $this->merchant;
    }


    public function id() {
        return 'zarinpal';
    }


    /**
     * Create a payment request; returns Authority or WP_Error.
     *
     * @param int $amount Amount in Rial.
     * @return string|WP_Error
     */
    public function request($amount, $callback_url, $order_id, $description = '') {

        $response = $this->post('request.json', [
            'merchant_id'  => $this->merchant,
            'amount'       => (int) $amount,
            'callback_url' => $callback_url,
            'description'  => $description ?: ('پرداخت #' . $order_id),
            'metadata'     => [
                'order_id' => (string) $order_id,
            ],
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code      = (int) ($response['data']['code'] ?? $response['errors']['code'] ?? 0);
        $authority = (string) ($response['data']['authority'] ?? '');

        if (100 !== $code || '' === $authority) {
            return $this->error($response);
        }

        return $authority;
    }


    /**
     * Verify payment after callback; returns verified data or WP_Error.
     *
     * @return array{amount:int,ref_number:string,card_number:string,order_id:string}|WP_Error
     */
    public function verify($authority, $amount) {

        $response = $this->post('verify.json', [
            'merchant_id' => $this->merchant,
            'amount'      => (int) $amount,
            'authority'   => (string) $authority,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = (int) ($response['data']['code'] ?? 0);

        // 100 = first verify, 101 = already verified.
        if (!in_array($code, [100, 101], true)) {
            return $this->error($response);
        }

        return [
            'amount'      => (int) $amount,
            'ref_number'  => (string) ($response['data']['ref_id'] ?? ''),
            'card_number' => (string) ($response['data']['card_pan'] ?? ''),
            'order_id'    => '',
        ];
    }


    public function start_url($authority) {
        $base = $this->sandbox ? self::SANDBOX_START : self::START_URL;

        return $base . rawurlencode((string) $authority);
    }


    private function api_base() {
        return $this->sandbox ? self::SANDBOX_API : self::API_URL;
    }


    private function post($endpoint, array $body) {

        $response = wp_remote_post($this->api_base() . ltrim($endpoint, '/'), [
            'timeout' => 20,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'body'    => wp_json_encode($body),
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('nasim_zarinpal_http', 'ارتباط با درگاه زرین‌پال برقرار نشد.', $response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (!is_array($data)) {
            return new WP_Error('nasim_zarinpal_response', 'پاسخ نامعتبر از درگاه زرین‌پال دریافت شد.');
        }

        return $data;
    }


    private function error(array $response) {

        $code = (int) ($response['errors']['code'] ?? $response['data']['code'] ?? 0);
        $msg  = (string) ($response['errors']['message'] ?? $response['data']['message'] ?? '');

        return new WP_Error('nasim_zarinpal_' . $code, self::message($code, $msg), $response);
    }


    private static function message($code, $fallback = '') {

        $messages = [
            -9  => 'خطای اعتبارسنجی داده‌ها.',
            -10 => 'آی‌پی یا مرچنت‌کد پذیرنده صحیح نیست.',
            -11 => 'مرچنت‌کد فعال نیست.',
            -12 => 'تلاش بیش از حد در یک بازه زمانی کوتاه.',
            -15 => 'درگاه پرداخت به حالت تعلیق در آمده است.',
            -16 => 'سطح تأیید پذیرنده پایین‌تر از سطح نقره‌ای است.',
            -30 => 'اجازه دسترسی به تسویه اشتراکی شناور ندارید.',
            -50 => 'مبلغ پرداخت‌شده با مبلغ ارسالی در متد وریفای متفاوت است.',
            -51 => 'پرداخت ناموفق بوده است.',
            -52 => 'خطای غیرمنتظره؛ با پشتیبانی زرین‌پال تماس بگیرید.',
            -53 => 'اتوریتی برای این مرچنت‌کد نیست.',
            -54 => 'اتوریتی نامعتبر است.',
        ];

        return $messages[$code] ?? ($fallback ?: 'خطای ناشناخته از درگاه زرین‌پال.');
    }
}
