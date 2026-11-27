<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * SnappPay installment payment gateway (official api.snapppay.ir API, WooCommerce-free).
 *
 * Adapted from plugins/snapppay-woocommerce-gateway for NilooWeb Pro LMS.
 * Flow: OAuth2 Basic -> Bearer token -> /payment/v1/token -> redirect -> callback (POST state=OK) -> /verify -> /settle.
 */
class Nasim_Snapppay_Gateway {

    const DEFAULT_BASE_URL = 'https://api.snapppay.ir/';
    const TOKEN_TRANSIENT  = 'nasim_snapppay_bearer_token';

    /** @var bool */
    private $enabled;

    /** @var string */
    private $base_url;

    /** @var string */
    private $client_id;

    /** @var string */
    private $client_secret;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    public function __construct(
        bool $enabled,
        string $base_url,
        string $client_id,
        string $client_secret,
        string $username,
        string $password
    ) {
        $this->enabled       = $enabled;
        $base                = trim($base_url) ?: self::DEFAULT_BASE_URL;
        $this->base_url      = rtrim($base, '/') . '/';
        $this->client_id     = trim($client_id);
        $this->client_secret = str_replace('amp;', '', trim($client_secret));
        $this->username      = trim($username);
        $this->password      = str_replace('amp;', '', trim($password));
    }

    public static function from_settings(): self
    {
        return new self(
            (bool) nasim_opt('snapppay_enabled', false),
            (string) nasim_opt('snapppay_base_url', self::DEFAULT_BASE_URL),
            (string) nasim_opt('snapppay_client_id', ''),
            (string) nasim_opt('snapppay_client_secret', ''),
            (string) nasim_opt('snapppay_username', ''),
            (string) nasim_opt('snapppay_password', '')
        );
    }

    public function id(): string
    {
        return 'snapppay';
    }

    public function is_enabled(): bool
    {
        return $this->enabled;
    }

    public function is_configured(): bool
    {
        return '' !== $this->client_id
            && '' !== $this->client_secret
            && '' !== $this->username
            && '' !== $this->password
            && '' !== $this->base_url;
    }

    public function is_available(): bool
    {
        return $this->is_enabled() && $this->is_configured();
    }

    /**
     * Format Iranian mobile to +989xxxxxxxxx (SnappPay format).
     */
    public static function format_mobile(string $mobile): string
    {
        $mobile = str_replace(
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            trim($mobile)
        );
        $mobile = preg_replace('/[^0-9+]/', '', $mobile);

        if (preg_match('/^\+980(\d{10})$/', $mobile, $m)) {
            return '+98' . $m[1];
        }
        if (preg_match('/^\+989\d{9}$/', $mobile)) {
            return $mobile;
        }
        if (preg_match('/^989\d{9}$/', $mobile)) {
            return '+' . $mobile;
        }
        if (preg_match('/^09\d{9}$/', $mobile)) {
            return '+98' . substr($mobile, 1);
        }
        if (preg_match('/^9\d{9}$/', $mobile)) {
            return '+98' . $mobile;
        }

        return '';
    }

    /**
     * Check merchant eligibility for an amount (in Rial).
     *
     * @return array<string,mixed>|WP_Error
     */
    public function is_eligible(int $amount_rial)
    {
        if (!$this->is_configured()) {
            return new WP_Error('snapppay_config', 'تنظیمات درگاه اسنپ‌پی کامل نیست.');
        }

        return $this->request_api('api/online/offer/v1/eligible', 'GET', ['amount' => $amount_rial]);
    }

    /**
     * Request a payment token & redirect URL.
     *
     * @param int    $amount_rial  Amount in Rial.
     * @param string $callback_url Callback URL.
     * @param int    $order_id     Internal payment ID.
     * @param string $title        Item / plan title.
     * @param string $mobile       Customer mobile (optional).
     * @param int    $item_id      Course / plan ID.
     * @return array{track_id:string,start_url:string,transaction_id:string}|WP_Error
     */
    public function request(int $amount_rial, string $callback_url, int $order_id, string $title = '', string $mobile = '', int $item_id = 0)
    {
        if (!$this->is_configured()) {
            return new WP_Error('snapppay_config', 'تنظیمات درگاه اقساطی اسنپ‌پی کامل نیست.');
        }

        $transaction_id = time() . '-' . $order_id;
        $formatted_mob  = $mobile ? self::format_mobile($mobile) : '';

        $payload = [
            'amount'               => $amount_rial,
            'paymentMethodTypeDto' => 'INSTALLMENT',
            'returnURL'            => $callback_url,
            'transactionId'        => $transaction_id,
            'discountAmount'       => 0,
            'cartList'             => [
                [
                    'cartId'             => $order_id,
                    'totalAmount'        => $amount_rial,
                    'taxAmount'          => 0,
                    'shippingAmount'     => 0,
                    'isShipmentIncluded' => false,
                    'isTaxIncluded'      => false,
                    'cartItems'          => [
                        [
                            'id'       => $item_id ?: $order_id,
                            'name'     => mb_substr($title ?: ('سفارش #' . $order_id), 0, 120),
                            'count'    => 1,
                            'amount'   => $amount_rial,
                            'category' => 'دوره آموزشی',
                        ],
                    ],
                ],
            ],
        ];

        if ($formatted_mob) {
            $payload['mobile'] = $formatted_mob;
        }

        $response = $this->request_api('api/online/payment/v1/token', 'POST', $payload);

        if (is_wp_error($response)) {
            return $response;
        }

        if (empty($response['successful']) || empty($response['response']['paymentToken']) || empty($response['response']['paymentPageUrl'])) {
            $msg = (string) ($response['errorData']['message'] ?? 'اتصال به درگاه اسنپ‌پی ناموفق بود.');
            return new WP_Error('snapppay_token', $msg, $response);
        }

        return [
            'track_id'       => (string) $response['response']['paymentToken'],
            'start_url'      => (string) $response['response']['paymentPageUrl'],
            'transaction_id' => $transaction_id,
        ];
    }

    /**
     * Verify + settle a SnappPay payment token after callback.
     *
     * @return array{amount:int,ref_number:string,card_number:string}|WP_Error
     */
    public function verify_and_settle(string $payment_token, int $expected_amount_rial)
    {
        $verify = $this->request_api('api/online/payment/v1/verify', 'POST', [
            'paymentToken' => $payment_token,
        ]);

        if (is_wp_error($verify)) {
            return $verify;
        }

        if (empty($verify['successful'])) {
            $msg = (string) ($verify['errorData']['message'] ?? 'تأیید تراکنش اسنپ‌پی ناموفق بود.');
            return new WP_Error('snapppay_verify', $msg, $verify);
        }

        $settle = $this->request_api('api/online/payment/v1/settle', 'POST', [
            'paymentToken' => $payment_token,
        ]);

        if (is_wp_error($settle)) {
            return $settle;
        }

        if (empty($settle['successful'])) {
            $msg = (string) ($settle['errorData']['message'] ?? 'تسویه تراکنش اسنپ‌پی ناموفق بود.');
            return new WP_Error('snapppay_settle', $msg, $settle);
        }

        $tx_id = (string) ($settle['response']['transactionId'] ?? $verify['response']['transactionId'] ?? '');

        return [
            'amount'      => $expected_amount_rial,
            'ref_number'  => $tx_id ?: substr($payment_token, 0, 32),
            'card_number' => 'SnappPay',
        ];
    }

    /**
     * Cancel a SnappPay payment token.
     *
     * @return array<string,mixed>|WP_Error
     */
    public function cancel(string $payment_token)
    {
        return $this->request_api('api/online/payment/v1/cancel', 'POST', [
            'paymentToken' => $payment_token,
        ]);
    }

    /**
     * Get payment status from SnappPay.
     *
     * @return array<string,mixed>|WP_Error
     */
    public function status(string $payment_token)
    {
        return $this->request_api('api/online/payment/v1/status', 'GET', [
            'paymentToken' => $payment_token,
        ]);
    }

    /**
     * Acquire or return cached OAuth2 bearer token.
     *
     * @return string|WP_Error
     */
    private function get_bearer_token(bool $force_refresh = false)
    {
        if (!$force_refresh) {
            $cached = get_transient(self::TOKEN_TRANSIENT);
            if (is_string($cached) && '' !== $cached) {
                return $cached;
            }
        }

        $basic = 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret);

        $response = wp_remote_post($this->base_url . 'api/online/v1/oauth/token', [
            'timeout' => 20,
            'headers' => [
                'Authorization' => $basic,
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'User-Agent'    => 'SnappPay, ' . $this->client_id,
            ],
            'body'    => http_build_query([
                'grant_type' => 'password',
                'scope'      => 'online-merchant',
                'username'   => $this->username,
                'password'   => $this->password,
            ]),
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('snapppay_http', 'ارتباط با سرور اسنپ‌پی برقرار نشد.', $response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data) || empty($data['access_token'])) {
            $msg = is_array($data) ? (string) ($data['error_description'] ?? $data['message'] ?? '') : '';
            return new WP_Error('snapppay_auth', $msg ?: 'احراز هویت با درگاه اسنپ‌پی ناموفق بود. اطلاعات API را بررسی کنید.');
        }

        $token = (string) $data['access_token'];
        $ttl   = max(60, ((int) ($data['expires_in'] ?? 3600)) - 60);
        set_transient(self::TOKEN_TRANSIENT, $token, $ttl);

        return $token;
    }

    /**
     * Send an authenticated request to SnappPay API (auto-retries once on 401).
     *
     * @param array<string,mixed> $args
     * @return array<string,mixed>|WP_Error
     */
    private function request_api(string $endpoint, string $method = 'POST', array $args = [], bool $retried = false)
    {
        $token = $this->get_bearer_token($retried);
        if (is_wp_error($token)) {
            return $token;
        }

        $url = $this->base_url . ltrim($endpoint, '/');
        $req = [
            'method'  => $method,
            'timeout' => 25,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'User-Agent'    => 'SnappPay, ' . $this->client_id,
            ],
        ];

        if ('GET' === $method && $args) {
            $url = add_query_arg($args, $url);
        } elseif ('GET' !== $method) {
            $req['body'] = wp_json_encode($args);
        }

        $response = wp_remote_request($url, $req);

        if (is_wp_error($response)) {
            return new WP_Error('snapppay_http', 'ارتباط با درگاه اسنپ‌پی برقرار نشد.', $response->get_error_message());
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if (401 === $code && !$retried) {
            delete_transient(self::TOKEN_TRANSIENT);
            return $this->request_api($endpoint, $method, $args, true);
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data)) {
            return new WP_Error('snapppay_response', 'پاسخ نامعتبر از درگاه اسنپ‌پی دریافت شد.');
        }

        return $data;
    }
}
