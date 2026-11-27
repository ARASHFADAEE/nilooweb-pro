<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Iran DigiPay (mydigipay.com) installment & credit payment gateway (WooCommerce-free).
 *
 * Official Business UPG API:
 * Flow: OAuth2 Basic -> Bearer token -> POST /businesses/ticket?type=11 -> redirect
 *       -> callback (POST/GET result=SUCCESS, trackingCode, providerId, type)
 *       -> POST /purchases/verify/{trackingCode} -> POST /purchases/deliver/{trackingCode}
 */
class Nasim_Digipay_Gateway {

    const LIVE_API_URL    = 'https://api.mydigipay.com/digipay/api';
    const SANDBOX_API_URL = 'https://uat.mydigipay.info/digipay/api';
    const API_VERSION     = '2022-02-02';
    const TOKEN_TRANSIENT = 'nasim_digipay_access_token';

    /** @var bool */
    private $enabled;

    /** @var bool */
    private $sandbox;

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
        bool $sandbox,
        string $custom_base_url,
        string $client_id,
        string $client_secret,
        string $username,
        string $password
    ) {
        $this->enabled       = $enabled;
        $this->sandbox       = $sandbox;
        $default_base        = $sandbox ? self::SANDBOX_API_URL : self::LIVE_API_URL;
        $this->base_url      = rtrim(trim($custom_base_url) ?: $default_base, '/');
        $this->client_id     = trim($client_id);
        $this->client_secret = trim($client_secret);
        $this->username      = trim($username);
        $this->password      = trim($password);
    }

    public static function from_settings(): self
    {
        return new self(
            (bool) nasim_opt('digipay_enabled', false),
            (bool) nasim_opt('digipay_sandbox', false),
            (string) nasim_opt('digipay_base_url', ''),
            (string) nasim_opt('digipay_client_id', ''),
            (string) nasim_opt('digipay_client_secret', ''),
            (string) nasim_opt('digipay_username', ''),
            (string) nasim_opt('digipay_password', '')
        );
    }

    public function id(): string
    {
        return 'digipay';
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
            && '' !== $this->password;
    }

    public function is_available(): bool
    {
        return $this->is_enabled() && $this->is_configured();
    }

    /**
     * Normalize mobile to 09xxxxxxxxx for DigiPay cellNumber.
     */
    public static function format_mobile(string $mobile): string
    {
        $mobile = str_replace(
            ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'],
            ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
            trim($mobile)
        );
        $digits = preg_replace('/\D/', '', $mobile);

        if (0 === strpos($digits, '0098') && 14 === strlen($digits)) {
            $digits = '0' . substr($digits, 4);
        } elseif (0 === strpos($digits, '98') && 12 === strlen($digits)) {
            $digits = '0' . substr($digits, 2);
        } elseif (10 === strlen($digits) && '9' === $digits[0]) {
            $digits = '0' . $digits;
        }

        return preg_match('/^09\d{9}$/', $digits) ? $digits : '';
    }

    /**
     * Request a DigiPay ticket and redirect URL.
     *
     * @param int    $amount_rial  Amount in Rial.
     * @param string $callback_url Callback URL.
     * @param int    $order_id     Internal payment ID.
     * @param string $title        Course / bundle title.
     * @param string $mobile       Customer mobile (optional).
     * @param int    $item_id      Course / plan ID.
     * @return array{track_id:string,start_url:string}|WP_Error
     */
    public function request(int $amount_rial, string $callback_url, int $order_id, string $title = '', string $mobile = '', int $item_id = 0)
    {
        if (!$this->is_configured()) {
            return new WP_Error('digipay_config', 'تنظیمات درگاه اقساطی دیجی‌پی کامل نیست.');
        }

        $cell = $mobile ? self::format_mobile($mobile) : '';

        $payload = [
            'amount'      => $amount_rial,
            'providerId'  => (string) $order_id,
            'callbackUrl' => $callback_url,
            'userType'    => 0,
            'basketDetailsDto' => [
                'basketId' => (string) $order_id,
                'items'    => [
                    [
                        'sellerId'    => '1',
                        'supplierId'  => '1',
                        'productCode' => (string) ($item_id ?: $order_id),
                        'brand'       => 'academy',
                        'productType' => 2, // Digital / educational product
                        'count'       => 1,
                        'categoryId'  => '1',
                    ],
                ],
            ],
        ];

        if ($cell) {
            $payload['cellNumber'] = $cell;
        }

        // type=11: Unified Payment Gateway (supports DigiPay Credit / Installments + IPG)
        $response = $this->request_api('/businesses/ticket?type=11', 'POST', $payload);

        if (is_wp_error($response)) {
            return $response;
        }

        $ticket       = (string) ($response['ticket'] ?? '');
        $redirect_url = (string) ($response['redirectUrl'] ?? '');

        if ('' === $ticket || '' === $redirect_url) {
            $msg = (string) ($response['result']['message'] ?? $response['message'] ?? 'اتصال به درگاه دیجی‌پی ناموفق بود.');
            return new WP_Error('digipay_ticket', $msg, $response);
        }

        return [
            'track_id'  => $ticket,
            'start_url' => $redirect_url,
        ];
    }

    /**
     * Verify (and deliver for credit/installment) a DigiPay purchase.
     *
     * @param string $tracking_code Tracking code returned by DigiPay callback.
     * @param string $provider_id   Internal payment ID sent as providerId.
     * @param int    $type          Payment type returned by callback (optional).
     * @param string $title         Product title for delivery payload.
     * @return array{amount:int,ref_number:string,card_number:string}|WP_Error
     */
    public function verify(string $tracking_code, string $provider_id, int $type = 0, string $title = '')
    {
        if ('' === $tracking_code) {
            return new WP_Error('digipay_tracking', 'کد پیگیری دیجی‌پی دریافت نشد.');
        }

        $endpoint = '/purchases/verify/' . rawurlencode($tracking_code);
        if ($type > 0) {
            $endpoint .= '?type=' . $type;
        }

        $response = $this->request_api($endpoint, 'POST', [
            'trackingCode' => $tracking_code,
            'providerId'   => $provider_id,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $status = (int) ($response['result']['status'] ?? -1);
        if (0 !== $status) {
            $msg = (string) ($response['result']['message'] ?? 'تأیید تراکنش دیجی‌پی ناموفق بود.');
            return new WP_Error('digipay_verify_' . $status, $msg, $response);
        }

        // Best-effort deliver call (required by DigiPay credit/BNPL flows to finalize settlement).
        $deliver_endpoint = '/purchases/deliver/' . rawurlencode($tracking_code);
        if ($type > 0) {
            $deliver_endpoint .= '?type=' . $type;
        }
        $this->request_api($deliver_endpoint, 'POST', [
            'invoiceNumber' => $provider_id,
            'deliveryDate'  => (int) round(microtime(true) * 1000),
            'products'      => [$title ?: ('سفارش #' . $provider_id)],
        ]);

        return [
            'amount'      => (int) ($response['amount'] ?? 0),
            'ref_number'  => (string) ($response['rrn'] ?? $tracking_code),
            'card_number' => (string) ($response['maskedPan'] ?? 'DigiPay'),
        ];
    }

    /**
     * Acquire or return cached OAuth2 access token.
     *
     * @return string|WP_Error
     */
    private function get_access_token(bool $force_refresh = false)
    {
        if (!$force_refresh) {
            $cached = get_transient(self::TOKEN_TRANSIENT);
            if (is_string($cached) && '' !== $cached) {
                return $cached;
            }
        }

        $basic = 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret);

        $response = wp_remote_post($this->base_url . '/oauth/token', [
            'timeout' => 20,
            'headers' => [
                'Authorization' => $basic,
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
            'body'    => http_build_query([
                'username'   => $this->username,
                'password'   => $this->password,
                'grant_type' => 'password',
            ]),
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('digipay_http', 'ارتباط با سرور دیجی‌پی برقرار نشد.', $response->get_error_message());
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data) || empty($data['access_token'])) {
            $msg = is_array($data) ? (string) ($data['error_description'] ?? $data['result']['message'] ?? $data['message'] ?? '') : '';
            return new WP_Error('digipay_auth', $msg ?: 'احراز هویت با درگاه دیجی‌پی ناموفق بود. اطلاعات API را بررسی کنید.');
        }

        $token = (string) $data['access_token'];
        $ttl   = max(60, ((int) ($data['expires_in'] ?? 600)) - 60);
        set_transient(self::TOKEN_TRANSIENT, $token, $ttl);

        return $token;
    }

    /**
     * Authenticated request to DigiPay API (auto-retries once on 401).
     *
     * @param array<string,mixed> $body
     * @return array<string,mixed>|WP_Error
     */
    private function request_api(string $endpoint, string $method = 'POST', array $body = [], bool $retried = false)
    {
        $token = $this->get_access_token($retried);
        if (is_wp_error($token)) {
            return $token;
        }

        $url = $this->base_url . '/' . ltrim($endpoint, '/');
        $req = [
            'method'  => $method,
            'timeout' => 25,
            'headers' => [
                'Authorization'   => 'Bearer ' . $token,
                'Agent'           => 'WEB',
                'Digipay-Version' => self::API_VERSION,
                'Content-Type'    => 'application/json',
                'Accept'          => 'application/json',
            ],
        ];

        if ('GET' !== $method && $body) {
            $req['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($url, $req);

        if (is_wp_error($response)) {
            return new WP_Error('digipay_http', 'ارتباط با درگاه دیجی‌پی برقرار نشد.', $response->get_error_message());
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if (401 === $code && !$retried) {
            delete_transient(self::TOKEN_TRANSIENT);
            return $this->request_api($endpoint, $method, $body, true);
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($data)) {
            return new WP_Error('digipay_response', 'پاسخ نامعتبر از درگاه دیجی‌پی دریافت شد.');
        }

        return $data;
    }
}
