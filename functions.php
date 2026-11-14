<?php

if(!defined('ABSPATH')) exit;



/**
 * Theme directory
 */
define('THEME_DIR', get_template_directory());


/**
 * Theme URL
 */
define('THEME_URL', get_template_directory_uri());


/**
 * Client / deployment profile (niloofarvafaei.ir defaults)
 */
include_once THEME_DIR . '/includes/client-config.php';


/**
 * Mino Option settings framework (bundled)
 */
$nasim_mino = THEME_DIR . '/includes/mino-option/mino-option.php';
if (is_readable($nasim_mino)) {
    require_once $nasim_mino;
}


/**
* Include theme setup
*/
include_once THEME_DIR . '/includes/theme-setup.php';


/**
 * Theme settings panel (Mino Option) — colors, SMS, auth, gateways
 */
include_once THEME_DIR . '/includes/theme-settings.php';


/**
 * Elementor: preserve builder pages (homepage + inner pages)
 */
include_once THEME_DIR . '/includes/elementor.php';


/**
* Include assets
*/
include_once THEME_DIR . '/includes/enqueue.php';


/**
* Include template helpers
*/
include_once THEME_DIR . '/includes/template-functions.php';


/**
* Include navigation walker
*/
include_once THEME_DIR . '/includes/class-nasim-nav-walker.php';


/**
* Include customizer settings
*/
include_once THEME_DIR . '/includes/customizer.php';


/**
* Include testimonials post type
*/
include_once THEME_DIR . '/includes/post-types.php';


/**
* Include demo content: sample testimonials and default subscription plans created on theme activation
*/
include_once THEME_DIR . '/includes/demo-content.php';


/**
* Include default pages: about, contact, FAQ, terms, privacy created on theme activation
*/
include_once THEME_DIR . '/includes/default-pages.php';


/**
* Include blog: /blog/ route, article helpers (TOC, related posts)
*/
include_once THEME_DIR . '/includes/blog.php';


/**
* Include public profile data (About / Contact / author box)
*/
include_once THEME_DIR . '/includes/profile.php';


/**
* Include technical SEO: schema, meta, redirects, internal hubs, migrations
*/
include_once THEME_DIR . '/includes/seo.php';


/**
* Include LMS: courses, plans, Zibal/ZarinPal payments
*/
include_once THEME_DIR . '/includes/lms/lms.php';
include_once THEME_DIR . '/includes/niloo-posts-seed.php';
include_once THEME_DIR . '/includes/niloo-pages-seed.php';
include_once THEME_DIR . '/includes/niloo-menus-seed.php';


/**
* Include Store: digital products (plugins/themes), licenses, downloads, update API
*/
if (nasim_store_enabled()) {
    include_once THEME_DIR . '/includes/store/store.php';
}


/**
* Include Tickets: support tickets in the user panel and admin, private attachments, email notices
*/
include_once THEME_DIR . '/includes/tickets/tickets.php';


/**
* Include SMS: sms.ir and Rah Payam clients, provider selection, subscription activation message
*/
include_once THEME_DIR . '/includes/sms/sms.php';


/**
* Include auth: login/register pages, one-time codes, theme settings page
*/
include_once THEME_DIR . '/includes/auth/auth.php';




/**
 * AWC Pay Proxy + Zibal — برای سایت fadaee-dev.ir
 *
 * نصب (یکی از دو روش):
 *   الف) کل این فایل را در انتهای functions.php قالب کپی کنید
 *   ب) بهتر: ذخیره به‌عنوان wp-content/mu-plugins/awc-pay-proxy.php
 *
 * راه‌اندازی:
 *   1. در wp-config.php همان سایت:
 *        define( 'AWC_PAY_PROXY_SECRET', 'همان-کلید-HMAC-مینوکد' );
 *        define( 'AWC_ZIBAL_MERCHANT', 'مرچنت-کد-زیبال' );
 *   2. تنظیمات ← پیوندهای یکتا ← ذخیره (یک‌بار)
 *   3. در مینوکد:
 *        آدرس شروع پرداخت = https://fadaee-dev.ir/awc-pay/
 *        کلید مشترک HMAC  = همان AWC_PAY_PROXY_SECRET
 *
 * مبلغ از مینوکد به تومان می‌آید؛ برای زیبال ×۱۰ به ریال تبدیل می‌شود.
 *
 * @package AWC_Pay_Proxy
 */




function awc_pay_proxy_secret(): string {
	if ( defined( 'AWC_PAY_PROXY_SECRET' ) && AWC_PAY_PROXY_SECRET ) {
		return (string) AWC_PAY_PROXY_SECRET;
	}
	return (string) get_option( 'awc_pay_proxy_secret', '' );
}

function awc_zibal_merchant(): string {
	if ( defined( 'AWC_ZIBAL_MERCHANT' ) && AWC_ZIBAL_MERCHANT ) {
		return (string) AWC_ZIBAL_MERCHANT;
	}
	return (string) get_option( 'awc_zibal_merchant', '' );
}

add_action(
	'init',
	static function (): void {
		add_rewrite_rule( '^awc-pay/callback/?$', 'index.php?awc_pay_proxy=callback', 'top' );
		add_rewrite_rule( '^awc-pay/?$', 'index.php?awc_pay_proxy=start', 'top' );
		add_rewrite_tag( '%awc_pay_proxy%', '([^&]+)' );
		if ( get_option( 'awc_pay_proxy_rules' ) !== '3' ) {
			flush_rewrite_rules( false );
			update_option( 'awc_pay_proxy_rules', '3', true );
		}
	}
);

add_filter(
	'query_vars',
	static function ( array $vars ): array {
		$vars[] = 'awc_pay_proxy';
		return $vars;
	}
);

add_action(
	'template_redirect',
	static function (): void {
		$mode = (string) get_query_var( 'awc_pay_proxy' );
		if ( 'start' === $mode ) {
			awc_pay_proxy_handle_start();
		}
		if ( 'callback' === $mode ) {
			awc_pay_proxy_handle_zibal_callback();
		}
	},
	1
);

/**
 * POST JSON to Zibal (بدون وابستگی به ووکامرس).
 *
 * @param array<string, mixed> $body Body.
 * @return array<string, mixed>|WP_Error
 */
function awc_zibal_http( string $path, array $body ) {
	$response = wp_remote_post(
		'https://gateway.zibal.ir/v1/' . ltrim( $path, '/' ),
		array(
			'timeout' => 30,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( $body ),
		)
	);
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	return is_array( $data ) ? $data : new WP_Error( 'awc_zibal_json', 'پاسخ نامعتبر زیبال.' );
}

/**
 * Start: verify minocode signature → Zibal request → redirect to gateway.
 * callbackUrl زیبال فقط روی همین دامنه است.
 */
function awc_pay_proxy_handle_start(): void {
	$secret   = awc_pay_proxy_secret();
	$merchant = awc_zibal_merchant();
	if ( '' === $secret || '' === $merchant ) {
		wp_die( 'کلید HMAC یا مرچنت زیبال تنظیم نشده است.', '', array( 'response' => 500 ) );
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended
	$amount = isset( $_GET['amount'] ) ? absint( $_GET['amount'] ) : 0;
	$order  = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : '';
	$token  = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
	$desc   = isset( $_GET['desc'] ) ? sanitize_text_field( wp_unslash( $_GET['desc'] ) ) : 'خرید اشتراک';
	$notify = isset( $_GET['notify'] ) ? esc_url_raw( wp_unslash( $_GET['notify'] ) ) : '';
	$return = isset( $_GET['return'] ) ? esc_url_raw( wp_unslash( $_GET['return'] ) ) : '';
	$cancel = isset( $_GET['cancel'] ) ? esc_url_raw( wp_unslash( $_GET['cancel'] ) ) : $return;
	$email  = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '';
	$mobile = isset( $_GET['mobile'] ) ? preg_replace( '/\D+/', '', (string) wp_unslash( $_GET['mobile'] ?? '' ) ) : '';
	$ts     = isset( $_GET['ts'] ) ? absint( $_GET['ts'] ) : 0;
	$sig    = isset( $_GET['sig'] ) ? sanitize_text_field( wp_unslash( $_GET['sig'] ) ) : '';
	// phpcs:enable

	if ( $amount < 1000 || '' === $token || '' === $order || '' === $notify || '' === $return ) {
		wp_die( 'درخواست پرداخت نامعتبر است (پارامتر ناقص).', '', array( 'response' => 400 ) );
	}
	if ( $ts < 1 || abs( time() - $ts ) > HOUR_IN_SECONDS ) {
		wp_die( 'درخواست پرداخت منقضی شده است؛ دوباره از پنل مینوکد اقدام کنید.', '', array( 'response' => 400 ) );
	}

	$expect = hash_hmac( 'sha256', implode( "\n", array( (string) $ts, $token, (string) $amount, $order ) ), $secret );
	if ( ! hash_equals( $expect, $sig ) ) {
		wp_die( 'امضای درخواست معتبر نیست.', '', array( 'response' => 403 ) );
	}

	$rial = $amount * 10; // تومان → ریال

	$session = array(
		'token'   => $token,
		'order'   => $order,
		'amount'  => $amount,
		'rial'    => $rial,
		'desc'    => $desc,
		'notify'  => $notify,
		'return'  => $return,
		'cancel'  => $cancel ?: $return,
		'email'   => $email,
		'mobile'  => $mobile,
		'created' => time(),
	);

	$payload = array(
		'merchant'    => $merchant,
		'amount'      => $rial,
		'callbackUrl' => home_url( '/awc-pay/callback/' ), // فقط دامنهٔ همین سایت
		'description' => mb_substr( $desc, 0, 250 ),
		'orderId'     => $order,
	);
	if ( preg_match( '/^09\d{9}$/', (string) $mobile ) ) {
		$payload['mobile'] = $mobile;
	}

	$res = awc_zibal_http( 'request', $payload );
	if ( is_wp_error( $res ) ) {
		wp_die( esc_html( $res->get_error_message() ), '', array( 'response' => 502 ) );
	}
	if ( 100 !== (int) ( $res['result'] ?? 0 ) || empty( $res['trackId'] ) ) {
		$msg = (string) ( $res['message'] ?? 'خطای زیبال در ایجاد تراکنش' );
		wp_die( esc_html( $msg ), '', array( 'response' => 502 ) );
	}

	$track_id            = (string) $res['trackId'];
	$session['track_id'] = $track_id;
	set_transient( 'awc_pay_proxy_' . $token, $session, DAY_IN_SECONDS );
	set_transient( 'awc_pay_track_' . $track_id, $token, DAY_IN_SECONDS );

	// HTML hop (not bare 302): guarantees Referer = fadaee-dev.ir for Zibal /start.
	awc_pay_proxy_redirect_to_zibal( $track_id );
}

/**
 * Send the browser to Zibal with an explicit origin Referer.
 *
 * Zibal/shaparak match three domains: merchant terminal, callbackUrl, and Referer on /start.
 * A naked wp_redirect 302 can lose Referer (Referrer-Policy / redirect chains from minocode).
 */
function awc_pay_proxy_redirect_to_zibal( string $track_id ): void {
	$url = 'https://gateway.zibal.ir/start/' . rawurlencode( $track_id );
	nocache_headers();
	header( 'Referrer-Policy: origin' );
	status_header( 200 );
	echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8">';
	echo '<meta name="referrer" content="origin">';
	echo '<meta http-equiv="refresh" content="0;url=' . esc_attr( $url ) . '">';
	echo '<title>انتقال به درگاه</title></head><body>';
	echo '<p style="font-family:Tahoma,sans-serif;text-align:center;margin-top:3rem">در حال انتقال به درگاه پرداخت…</p>';
	echo '<script>location.replace(' . wp_json_encode( $url ) . ');</script>';
	echo '<p style="text-align:center"><a href="' . esc_url( $url ) . '">ورود به درگاه</a></p>';
	echo '</body></html>';
	exit;
}

/**
 * Notify minocode to activate the subscription (server → server).
 *
 * @param array<string, mixed> $session Session.
 * @return array{ok: bool, redirect: string, message: string}
 */
function awc_pay_proxy_notify_hub( array $session, string $ref, bool $ok ): array {
	$secret  = awc_pay_proxy_secret();
	$token   = (string) $session['token'];
	$amount  = (int) $session['amount'];
	$status  = $ok ? 'ok' : 'cancelled';
	$ts      = time();
	$sig     = hash_hmac( 'sha256', implode( "\n", array( (string) $ts, $token, (string) $amount, $ref, $status ) ), $secret );
	$fallback = $ok ? (string) $session['return'] : (string) $session['cancel'];

	if ( ! $ok ) {
		return array(
			'ok'       => false,
			'redirect' => $fallback,
			'message'  => 'پرداخت لغو یا ناموفق بود.',
		);
	}

	$response = wp_remote_post(
		(string) $session['notify'],
		array(
			'timeout' => 25,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode(
				array(
					'token'  => $token,
					'amount' => $amount,
					'ref'    => $ref,
					'ts'     => $ts,
					'sig'    => $sig,
					'status' => 'ok',
				)
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		// Fallback: signed browser return so hub can still activate.
		$fallback = add_query_arg(
			array(
				'token'  => $token,
				'status' => 'ok',
				'ref'    => $ref,
				'amount' => $amount,
				'ts'     => $ts,
				'sig'    => $sig,
			),
			(string) $session['return']
		);
		return array(
			'ok'       => false,
			'redirect' => $fallback,
			'message'  => 'پرداخت ثبت شد؛ در حال تکمیل فعال‌سازی…',
		);
	}

	$code = (int) wp_remote_retrieve_response_code( $response );
	$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	$redirect = is_array( $data ) && ! empty( $data['redirect'] )
		? (string) $data['redirect']
		: (string) $session['return'];

	if ( $code >= 200 && $code < 300 && is_array( $data ) && ! empty( $data['ok'] ) ) {
		return array(
			'ok'       => true,
			'redirect' => $redirect,
			'message'  => 'اشتراک شما فعال شد.',
		);
	}

	$msg = is_array( $data ) && ! empty( $data['message'] )
		? (string) $data['message']
		: 'پرداخت موفق بود؛ فعال‌سازی را در پنل بررسی کنید.';

	return array(
		'ok'       => false,
		'redirect' => add_query_arg(
			array(
				'token'  => $token,
				'status' => 'ok',
				'ref'    => $ref,
				'amount' => $amount,
				'ts'     => $ts,
				'sig'    => $sig,
			),
			(string) $session['return']
		),
		'message'  => $msg,
	);
}

/**
 * Intermediate success / fail page on fadaee-dev.ir, then redirect to minocode.
 */
function awc_pay_proxy_render_bridge( bool $success, string $title, string $message, string $redirect, string $ref = '' ): void {
	nocache_headers();
	status_header( 200 );
	$redirect_js = esc_url( $redirect );
	$delay_ms    = $success ? 2200 : 3500;
	$ref_html    = $ref ? '<p class="ref">رسید: <b dir="ltr">' . esc_html( $ref ) . '</b></p>' : '';
	$icon        = $success ? '✓' : '!';
	$tone        = $success ? '#0f7b4a' : '#b45309';
	echo '<!DOCTYPE html><html lang="fa" dir="rtl"><head><meta charset="utf-8">';
	echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
	echo '<meta http-equiv="refresh" content="' . (int) ceil( $delay_ms / 1000 ) . ';url=' . esc_attr( $redirect ) . '">';
	echo '<title>' . esc_html( $title ) . '</title>';
	echo '<style>
		:root{color-scheme:light}
		*{box-sizing:border-box}
		body{margin:0;min-height:100vh;display:grid;place-items:center;font-family:Tahoma,Arial,sans-serif;
			background:radial-gradient(1200px 600px at 80% -10%,#dbeafe 0%,transparent 55%),
			radial-gradient(900px 500px at 0% 100%,#ecfdf5 0%,transparent 50%),#f8fafc;color:#0f172a}
		.card{width:min(440px,92vw);padding:2.25rem 1.75rem;text-align:center}
		.icon{width:64px;height:64px;border-radius:50%;display:grid;place-items:center;margin:0 auto 1.25rem;
			background:' . esc_attr( $tone ) . ';color:#fff;font-size:1.75rem;font-weight:700}
		h1{margin:0 0 .65rem;font-size:1.35rem}
		p{margin:0;line-height:1.7;color:#334155}
		.ref{margin-top:1rem;font-size:.9rem;color:#64748b}
		.hint{margin-top:1.5rem;font-size:.85rem;color:#94a3b8}
		a{color:' . esc_attr( $tone ) . '}
		.bar{margin:1.75rem auto 0;height:3px;width:140px;background:#e2e8f0;border-radius:99px;overflow:hidden}
		.bar>i{display:block;height:100%;width:0;background:' . esc_attr( $tone ) . ';
			animation:fill ' . (int) $delay_ms . 'ms linear forwards}
		@keyframes fill{to{width:100%}}
	</style></head><body><div class="card">';
	echo '<div class="icon" aria-hidden="true">' . esc_html( $icon ) . '</div>';
	echo '<h1>' . esc_html( $title ) . '</h1>';
	echo '<p>' . esc_html( $message ) . '</p>';
	echo $ref_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<p class="hint">در حال انتقال به مینوکد… اگر منتقل نشدید، <a href="' . $redirect_js . '">اینجا کلیک کنید</a>.</p>';
	echo '<div class="bar" aria-hidden="true"><i></i></div>';
	echo '</div><script>setTimeout(function(){location.replace(' . wp_json_encode( $redirect ) . ');},' . (int) $delay_ms . ');</script>';
	echo '</body></html>';
	exit;
}

/**
 * Zibal returns here (only fadaee-dev.ir) → verify → notify hub → bridge page.
 */
function awc_pay_proxy_handle_zibal_callback(): void {
	$secret   = awc_pay_proxy_secret();
	$merchant = awc_zibal_merchant();
	$track_id = isset( $_GET['trackId'] ) ? sanitize_text_field( wp_unslash( $_GET['trackId'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$success  = isset( $_GET['success'] ) ? absint( $_GET['success'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( '' === $track_id || '' === $secret || '' === $merchant ) {
		wp_die( 'پارامترهای بازگشت نامعتبر است.', '', array( 'response' => 400 ) );
	}

	$token   = (string) get_transient( 'awc_pay_track_' . $track_id );
	$session = $token ? get_transient( 'awc_pay_proxy_' . $token ) : false;
	if ( ! is_array( $session ) ) {
		wp_die( 'نشست پرداخت منقضی شده است.', '', array( 'response' => 404 ) );
	}

	$ref = '';
	$ok  = false;

	if ( 1 === $success ) {
		$verify = awc_zibal_http(
			'verify',
			array(
				'merchant' => $merchant,
				'trackId'  => (int) $track_id,
			)
		);
		if ( ! is_wp_error( $verify ) ) {
			$result = (int) ( $verify['result'] ?? 0 );
			// 100 = paid first verify, 201 = already verified.
			if ( in_array( $result, array( 100, 201 ), true ) ) {
				$paid_rial = (int) ( $verify['amount'] ?? 0 );
				if ( $paid_rial === (int) $session['rial'] || 0 === $paid_rial ) {
					$ok  = true;
					$ref = (string) ( $verify['refNumber'] ?? $track_id );
				}
			}
		}
	}

	$result = awc_pay_proxy_notify_hub( $session, $ref, $ok );
	delete_transient( 'awc_pay_track_' . $track_id );

	if ( $ok ) {
		awc_pay_proxy_render_bridge(
			true,
			'پرداخت با موفقیت انجام شد',
			$result['message'] . ' در حال انتقال به سایت مینوکد…',
			$result['redirect'],
			$ref
		);
	}

	awc_pay_proxy_render_bridge(
		false,
		'پرداخت تکمیل نشد',
		$result['message'],
		$result['redirect']
	);
}
