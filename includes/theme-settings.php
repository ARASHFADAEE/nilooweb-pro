<?php

if (!defined('ABSPATH')) {
    exit;
}

use Mino\Option\Settings;

/**
 * Mino Option page id / option key for Niloo theme settings.
 */
define('NILOO_SETTINGS_PAGE', 'niloo_theme_settings');


/**
 * Read a Niloo theme setting (Mino Option → legacy option → default).
 *
 * @param mixed $default Fallback when unset.
 * @return mixed
 */
function nasim_opt($key, $default = null)
{
    $defaults = function_exists('nasim_settings_defaults') ? nasim_settings_defaults() : [];
    $fallback = array_key_exists($key, $defaults) ? $defaults[$key] : $default;

    $raw = get_option(NILOO_SETTINGS_PAGE, null);
    if (is_array($raw) && array_key_exists($key, $raw)) {
        return $raw[$key];
    }

    if (function_exists('mino_get_option')
        && class_exists('\Mino\Option\Registry\PageRegistry')
        && \Mino\Option\Registry\PageRegistry::instance()->has_page(NILOO_SETTINGS_PAGE)
    ) {
        $value = mino_get_option(NILOO_SETTINGS_PAGE, $key, null);
        if (null !== $value) {
            return $value;
        }
    }

    // Legacy payment options (pre-Mino).
    if ('zibal_merchant' === $key) {
        $legacy = get_option('nasim_zibal_merchant', null);
        if (null !== $legacy && '' !== $legacy) {
            return $legacy;
        }
    }
    if ('zibal_sandbox' === $key) {
        $legacy = get_option('nasim_zibal_sandbox', null);
        if (null !== $legacy) {
            return (bool) $legacy;
        }
    }

    $legacy_all = (array) get_option('nasim_theme_settings', []);
    if (array_key_exists($key, $legacy_all)) {
        return $legacy_all[$key];
    }

    return null !== $default ? $default : $fallback;
}


/**
 * Migrate legacy options into Mino Option storage once.
 */
function nasim_migrate_settings_to_mino()
{
    if (get_option('niloo_mino_migrated_v1')) {
        return;
    }

    if (!function_exists('mino_get_options') || !class_exists('\Mino\Option\Storage\OptionStore')) {
        return;
    }

    $merged = (array) get_option('nasim_theme_settings', []);

    $zibal_merchant = (string) get_option('nasim_zibal_merchant', '');
    if ($zibal_merchant) {
        $merged['zibal_merchant'] = $zibal_merchant;
    }
    if (get_option('nasim_zibal_sandbox')) {
        $merged['zibal_sandbox'] = true;
    }

    $spot = get_option('spotplayer', []);
    if (is_array($spot) && !empty($spot['api'])) {
        $merged['spot_api'] = (string) $spot['api'];
    }
    if (is_array($spot) && !empty($spot['test'])) {
        $merged['spot_test'] = true;
    }

    if ($merged) {
        $current = (array) get_option(NILOO_SETTINGS_PAGE, []);
        update_option(NILOO_SETTINGS_PAGE, array_merge($merged, $current), false);
    }

    update_option('niloo_mino_migrated_v1', 1, false);
}


/**
 * Register Niloo theme settings UI (Mino Option).
 */
function nasim_register_mino_theme_settings()
{
    if (!class_exists(Settings::class)) {
        return;
    }

    Settings::register_page([
        'id'          => NILOO_SETTINGS_PAGE,
        'title'       => __('تنظیمات نیلو', 'niloo-web'),
        'menu_title'  => __('تنظیمات نیلو', 'niloo-web'),
        'menu_type'   => 'menu',
        'capability'  => 'manage_options',
        'icon'        => 'dashicons-admin-customizer',
        'position'    => 58,
        'description' => __('رنگ‌ها، ورود، پیامک، درگاه پرداخت و اسپات‌پلیر', 'niloo-web'),
        'option_key'  => NILOO_SETTINGS_PAGE,
    ]);

    nasim_register_mino_tab_general();
    nasim_register_mino_tab_colors();
    nasim_register_mino_tab_auth();
    nasim_register_mino_tab_sms();
    nasim_register_mino_tab_payment();
    nasim_register_mino_tab_spot();
    nasim_register_mino_tab_social();
    nasim_register_mino_tab_tools();

    nasim_migrate_settings_to_mino();
}
add_action('after_setup_theme', 'nasim_register_mino_theme_settings', 30);


/**
 * Keep API secrets when the password field is submitted empty.
 *
 * @param mixed               $value Sanitized value.
 * @param array<string,mixed> $field Field config.
 * @return mixed
 */
function nasim_mino_preserve_secrets($value, array $field)
{
    $secrets = [
        'smsir_api_key',
        'msgway_api_key',
        'spot_api',
        'snapppay_client_secret',
        'snapppay_password',
        'digipay_client_secret',
        'digipay_password',
    ];
    $id      = (string) ($field['id'] ?? '');

    if (!in_array($id, $secrets, true)) {
        return $value;
    }

    if ('' !== (string) $value) {
        return $value;
    }

    $existing = get_option(NILOO_SETTINGS_PAGE, []);

    return is_array($existing) ? (string) ($existing[$id] ?? '') : '';
}
add_filter('mino_sanitize_field', 'nasim_mino_preserve_secrets', 10, 2);


function nasim_register_mino_tab_general()
{
    Settings::register_tab(NILOO_SETTINGS_PAGE, [
        'id'          => 'general',
        'title'       => __('عمومی', 'niloo-web'),
        'description' => __('برند و رفتار کلی قالب', 'niloo-web'),
        'icon'        => 'dashicons-admin-home',
        'fields'      => [
            [
                'id'          => 'site_tagline',
                'type'        => 'text',
                'title'       => __('شعار کوتاه سایت', 'niloo-web'),
                'description' => __('در فوتر و جاهایی که شعار نمایش داده می‌شود استفاده می‌شود (اگر در سفارشی‌ساز خالی باشد).', 'niloo-web'),
                'default'     => '',
            ],
            [
                'id'      => 'announcement_enabled',
                'type'    => 'switch',
                'title'   => __('نوار اطلاع‌رسانی بالای سایت', 'niloo-web'),
                'default' => true,
            ],
            [
                'id'      => 'courses_home_count',
                'type'    => 'number',
                'title'   => __('تعداد دوره در صفحه اصلی', 'niloo-web'),
                'min'     => 1,
                'max'     => 12,
                'default' => 4,
            ],
        ],
    ]);
}


function nasim_register_mino_tab_colors()
{
    Settings::register_tab(NILOO_SETTINGS_PAGE, [
        'id'          => 'colors',
        'title'       => __('رنگ‌بندی', 'niloo-web'),
        'description' => __('پالت اصلی قالب', 'niloo-web'),
        'icon'        => 'dashicons-art',
        'fields'      => [
            [
                'id'      => 'color_primary',
                'type'    => 'color',
                'title'   => __('رنگ اصلی (بنفش)', 'niloo-web'),
                'default' => '#7466f0',
            ],
            [
                'id'      => 'color_primary_soft',
                'type'    => 'color',
                'title'   => __('رنگ اصلی روشن', 'niloo-web'),
                'default' => '#8b7cf8',
            ],
            [
                'id'      => 'color_primary_dark',
                'type'    => 'color',
                'title'   => __('رنگ اصلی تیره', 'niloo-web'),
                'default' => '#5c4fd4',
            ],
            [
                'id'      => 'color_accent',
                'type'    => 'color',
                'title'   => __('رنگ تأکید (نارنجی)', 'niloo-web'),
                'default' => '#f2994a',
            ],
            [
                'id'      => 'color_ink',
                'type'    => 'color',
                'title'   => __('رنگ متن', 'niloo-web'),
                'default' => '#17152b',
            ],
            [
                'id'      => 'color_muted',
                'type'    => 'color',
                'title'   => __('رنگ متن ثانویه', 'niloo-web'),
                'default' => '#5f6278',
            ],
            [
                'id'      => 'color_soft',
                'type'    => 'color',
                'title'   => __('پس‌زمینه ملایم', 'niloo-web'),
                'default' => '#f5f6fb',
            ],
        ],
    ]);
}


function nasim_register_mino_tab_auth()
{
    Settings::register_tab(NILOO_SETTINGS_PAGE, [
        'id'          => 'auth',
        'title'       => __('ورود و ثبت‌نام', 'niloo-web'),
        'description' => __('OTP و ثبت‌نام', 'niloo-web'),
        'icon'        => 'dashicons-lock',
        'fields'      => [
            [
                'id'          => 'auth_otp_login',
                'type'        => 'switch',
                'title'       => __('ورود با کد یک‌بارمصرف (پیامک)', 'niloo-web'),
                'description' => __('ورود با نام کاربری و رمز همیشه فعال است.', 'niloo-web'),
                'default'     => true,
            ],
            [
                'id'      => 'auth_registration',
                'type'    => 'switch',
                'title'   => __('ثبت‌نام کاربر جدید', 'niloo-web'),
                'default' => true,
            ],
            [
                'id'      => 'otp_length',
                'type'    => 'number',
                'title'   => __('تعداد ارقام کد', 'niloo-web'),
                'min'     => 4,
                'max'     => 8,
                'default' => 5,
            ],
            [
                'id'      => 'otp_ttl',
                'type'    => 'number',
                'title'   => __('اعتبار کد (ثانیه)', 'niloo-web'),
                'min'     => 60,
                'max'     => 600,
                'default' => 120,
            ],
            [
                'id'      => 'otp_resend',
                'type'    => 'number',
                'title'   => __('فاصله ارسال مجدد (ثانیه)', 'niloo-web'),
                'min'     => 30,
                'max'     => 600,
                'default' => 90,
            ],
            [
                'id'          => 'auth_pages_notice',
                'type'        => 'html',
                'title'       => __('آدرس صفحات', 'niloo-web'),
                'trusted'     => true,
                'content'     => function_exists('nasim_auth_url')
                    ? '<code dir="ltr">' . esc_html(nasim_auth_url('login')) . '</code> · <code dir="ltr">' . esc_html(nasim_auth_url('register')) . '</code>'
                    : '',
            ],
        ],
    ]);
}


function nasim_register_mino_tab_sms()
{
    $msgway_lines = ['0' => __('پیش‌فرض راه پیام', 'niloo-web')];
    if (class_exists('Nasim_Msgway') && !empty(Nasim_Msgway::LINES)) {
        foreach (Nasim_Msgway::LINES as $line => $label) {
            $msgway_lines[(string) $line] = $label;
        }
    }

    Settings::register_tab(NILOO_SETTINGS_PAGE, [
        'id'          => 'sms',
        'title'       => __('پیامک', 'niloo-web'),
        'description' => __('sms.ir و راه پیام', 'niloo-web'),
        'icon'        => 'dashicons-email-alt',
        'fields'      => [
            [
                'id'      => 'sms_provider',
                'type'    => 'buttonset',
                'title'   => __('سامانه فعال', 'niloo-web'),
                'options' => [
                    'smsir'  => 'sms.ir',
                    'msgway' => 'راه پیام',
                ],
                'default' => 'smsir',
            ],
            [
                'id'    => 'smsir_heading',
                'type'  => 'heading',
                'title' => __('تنظیمات sms.ir', 'niloo-web'),
                'require' => [['sms_provider', '==', 'smsir']],
            ],
            [
                'id'          => 'smsir_api_key',
                'type'        => 'password',
                'title'       => __('کلید API — sms.ir', 'niloo-web'),
                'description' => __('از پنل sms.ir، بخش برنامه‌نویسان.', 'niloo-web'),
                'default'     => '',
                'require'     => [['sms_provider', '==', 'smsir']],
            ],
            [
                'id'      => 'smsir_login_template',
                'type'    => 'text',
                'title'   => __('کد قالب ورود', 'niloo-web'),
                'default' => '',
                'require' => [['sms_provider', '==', 'smsir']],
            ],
            [
                'id'          => 'smsir_register_template',
                'type'        => 'text',
                'title'       => __('کد قالب ثبت‌نام', 'niloo-web'),
                'description' => __('اگر خالی بماند از قالب ورود استفاده می‌شود.', 'niloo-web'),
                'default'     => '',
                'require'     => [['sms_provider', '==', 'smsir']],
            ],
            [
                'id'          => 'smsir_code_param',
                'type'        => 'text',
                'title'       => __('نام متغیر کد در قالب', 'niloo-web'),
                'description' => __('بدون #؛ مثلاً CODE', 'niloo-web'),
                'default'     => 'CODE',
                'require'     => [['sms_provider', '==', 'smsir']],
            ],
            [
                'id'      => 'smsir_subscription_template',
                'type'    => 'text',
                'title'   => __('کد قالب اطلاع خرید/دسترسی', 'niloo-web'),
                'default' => '',
                'require' => [['sms_provider', '==', 'smsir']],
            ],
            [
                'id'      => 'msgway_heading',
                'type'    => 'heading',
                'title'   => __('تنظیمات راه پیام', 'niloo-web'),
                'require' => [['sms_provider', '==', 'msgway']],
            ],
            [
                'id'      => 'msgway_api_key',
                'type'    => 'password',
                'title'   => __('کلید API — راه پیام', 'niloo-web'),
                'default' => '',
                'require' => [['sms_provider', '==', 'msgway']],
            ],
            [
                'id'      => 'msgway_login_template',
                'type'    => 'text',
                'title'   => __('templateID ورود', 'niloo-web'),
                'default' => '',
                'require' => [['sms_provider', '==', 'msgway']],
            ],
            [
                'id'      => 'msgway_register_template',
                'type'    => 'text',
                'title'   => __('templateID ثبت‌نام', 'niloo-web'),
                'default' => '',
                'require' => [['sms_provider', '==', 'msgway']],
            ],
            [
                'id'      => 'msgway_subscription_template',
                'type'    => 'text',
                'title'   => __('templateID اطلاع خرید', 'niloo-web'),
                'default' => '',
                'require' => [['sms_provider', '==', 'msgway']],
            ],
            [
                'id'      => 'msgway_line',
                'type'    => 'select',
                'title'   => __('خط ارسال', 'niloo-web'),
                'options' => $msgway_lines,
                'default' => '0',
                'require' => [['sms_provider', '==', 'msgway']],
            ],
            [
                'id'      => 'sms_subscription',
                'type'    => 'switch',
                'title'   => __('پیامک بعد از خرید موفق', 'niloo-web'),
                'default' => false,
            ],
            [
                'id'          => 'sms_subscription_params',
                'type'        => 'text',
                'title'       => __('متغیرهای قالب خرید (با کاما)', 'niloo-web'),
                'description' => __('مثلاً NAME,PLAN,EXPIRE', 'niloo-web'),
                'default'     => 'NAME,PLAN,EXPIRE',
                'require'     => [['sms_subscription', '==', true]],
            ],
        ],
    ]);
}


function nasim_register_mino_tab_payment()
{
    Settings::register_tab(NILOO_SETTINGS_PAGE, [
        'id'          => 'payment',
        'title'       => __('درگاه پرداخت', 'niloo-web'),
        'description' => __('زیبال، زرین‌پال و پرداخت اقساطی اسنپ‌پی / دیجی‌پی', 'niloo-web'),
        'icon'        => 'dashicons-money-alt',
        'fields'      => [
            [
                'id'          => 'payment_gateway',
                'type'        => 'buttonset',
                'title'       => __('درگاه پرداخت نقدی (آنلاین)', 'niloo-web'),
                'description' => __('بدون ووکامرس؛ خرید نقدی دوره‌ها با درگاه انتخابی انجام می‌شود.', 'niloo-web'),
                'options'     => [
                    'zibal'    => 'زیبال',
                    'zarinpal' => 'زرین‌پال',
                ],
                'default'     => 'zibal',
            ],
            [
                'id'      => 'payment_callback_notice',
                'type'    => 'html',
                'title'   => __('آدرس بازگشت (Callback)', 'niloo-web'),
                'trusted' => true,
                'content' => '<p><code dir="ltr">' . esc_html(admin_url('admin-post.php?action=nasim_payment_callback')) . '</code></p>'
                    . '<p class="description">' . esc_html__('این آدرس را در پنل درگاه ثبت کنید؛ دامنه باید با دامنه سایت یکی باشد.', 'niloo-web') . '</p>',
            ],
            [
                'id'      => 'zibal_heading',
                'type'    => 'heading',
                'title'   => __('زیبال', 'niloo-web'),
                'require' => [['payment_gateway', '==', 'zibal']],
            ],
            [
                'id'          => 'zibal_merchant',
                'type'        => 'text',
                'title'       => __('کد مرچنت زیبال', 'niloo-web'),
                'placeholder' => 'zibal یا مرچنت واقعی',
                'default'     => '',
                'require'     => [['payment_gateway', '==', 'zibal']],
            ],
            [
                'id'          => 'zibal_sandbox',
                'type'        => 'switch',
                'title'       => __('حالت آزمایشی زیبال', 'niloo-web'),
                'description' => __('با مرچنت تست zibal؛ پرداخت واقعی انجام نمی‌شود.', 'niloo-web'),
                'default'     => false,
                'require'     => [['payment_gateway', '==', 'zibal']],
            ],
            [
                'id'      => 'zarinpal_heading',
                'type'    => 'heading',
                'title'   => __('زرین‌پال', 'niloo-web'),
                'require' => [['payment_gateway', '==', 'zarinpal']],
            ],
            [
                'id'          => 'zarinpal_merchant',
                'type'        => 'text',
                'title'       => __('مرچنت‌کد زرین‌پال', 'niloo-web'),
                'description' => __('از پنل زرین‌پال کپی کنید (UUID).', 'niloo-web'),
                'default'     => '',
                'require'     => [['payment_gateway', '==', 'zarinpal']],
            ],
            [
                'id'          => 'zarinpal_sandbox',
                'type'        => 'switch',
                'title'       => __('حالت آزمایشی (Sandbox) زرین‌پال', 'niloo-web'),
                'default'     => false,
                'require'     => [['payment_gateway', '==', 'zarinpal']],
            ],

            /* ---------------- SnappPay (اقساطی اسنپ‌پی) ---------------- */
            [
                'id'    => 'snapppay_heading',
                'type'  => 'heading',
                'title' => __('پرداخت اقساطی اسنپ‌پی (SnappPay)', 'niloo-web'),
            ],
            [
                'id'          => 'snapppay_enabled',
                'type'        => 'switch',
                'title'       => __('فعالسازی درگاه اقساطی اسنپ‌پی', 'niloo-web'),
                'description' => __('به‌صورت اختیاری در کنار درگاه آنلاین به کاربر نمایش داده می‌شود.', 'niloo-web'),
                'default'     => false,
            ],
            [
                'id'          => 'snapppay_base_url',
                'type'        => 'url',
                'title'       => __('آدرس API اسنپ‌پی (Base URL)', 'niloo-web'),
                'description' => __('پیش‌فرض: https://api.snapppay.ir/ — برای محیط سندباکس آدرس تست اسنپ‌پی را وارد کنید.', 'niloo-web'),
                'default'     => 'https://api.snapppay.ir/',
                'require'     => [['snapppay_enabled', '==', true]],
            ],
            [
                'id'      => 'snapppay_client_id',
                'type'    => 'text',
                'title'   => __('شناسه مشتری (Client ID)', 'niloo-web'),
                'default' => '',
                'require' => [['snapppay_enabled', '==', true]],
            ],
            [
                'id'          => 'snapppay_client_secret',
                'type'        => 'password',
                'title'       => __('کلید امنیتی مشتری (Client Secret)', 'niloo-web'),
                'description' => __('برای حفظ مقدار قبلی، خالی بگذارید.', 'niloo-web'),
                'default'     => '',
                'require'     => [['snapppay_enabled', '==', true]],
            ],
            [
                'id'      => 'snapppay_username',
                'type'    => 'text',
                'title'   => __('نام کاربری پذیرنده (Username)', 'niloo-web'),
                'default' => '',
                'require' => [['snapppay_enabled', '==', true]],
            ],
            [
                'id'          => 'snapppay_password',
                'type'        => 'password',
                'title'       => __('رمز عبور پذیرنده (Password)', 'niloo-web'),
                'description' => __('برای حفظ مقدار قبلی، خالی بگذارید.', 'niloo-web'),
                'default'     => '',
                'require'     => [['snapppay_enabled', '==', true]],
            ],
            [
                'id'      => 'snapppay_title',
                'type'    => 'text',
                'title'   => __('عنوان نمایشی در صفحه پرداخت', 'niloo-web'),
                'default' => 'پرداخت اقساطی اسنپ‌پی',
                'require' => [['snapppay_enabled', '==', true]],
            ],
            [
                'id'          => 'snapppay_description',
                'type'        => 'text',
                'title'       => __('توضیح کوتاه (اختیاری)', 'niloo-web'),
                'description' => __('اگر خالی بماند، مبلغ هر قسط (تقسیم بر ۴) خودکار نمایش داده می‌شود.', 'niloo-web'),
                'default'     => '',
                'require'     => [['snapppay_enabled', '==', true]],
            ],

            /* ---------------- DigiPay (اقساطی دیجی‌پی ایران) ---------------- */
            [
                'id'    => 'digipay_heading',
                'type'  => 'heading',
                'title' => __('پرداخت اقساطی دیجی‌پی (mydigipay.com)', 'niloo-web'),
            ],
            [
                'id'          => 'digipay_enabled',
                'type'        => 'switch',
                'title'       => __('فعالسازی درگاه اقساطی دیجی‌پی', 'niloo-web'),
                'description' => __('به‌صورت اختیاری در کنار درگاه آنلاین به کاربر نمایش داده می‌شود.', 'niloo-web'),
                'default'     => false,
            ],
            [
                'id'          => 'digipay_sandbox',
                'type'        => 'switch',
                'title'       => __('حالت آزمایشی (UAT / Sandbox) دیجی‌پی', 'niloo-web'),
                'description' => __('در حالت تست به سرور uat.mydigipay.info متصل می‌شود.', 'niloo-web'),
                'default'     => false,
                'require'     => [['digipay_enabled', '==', true]],
            ],
            [
                'id'          => 'digipay_base_url',
                'type'        => 'url',
                'title'       => __('آدرس سفارشی API دیجی‌پی (اختیاری)', 'niloo-web'),
                'description' => __('اگر خالی بماند، خودکار از https://api.mydigipay.com/digipay/api استفاده می‌شود.', 'niloo-web'),
                'default'     => '',
                'require'     => [['digipay_enabled', '==', true]],
            ],
            [
                'id'      => 'digipay_client_id',
                'type'    => 'text',
                'title'   => __('Client ID دیجی‌پی', 'niloo-web'),
                'default' => '',
                'require' => [['digipay_enabled', '==', true]],
            ],
            [
                'id'          => 'digipay_client_secret',
                'type'        => 'password',
                'title'       => __('Client Secret دیجی‌پی', 'niloo-web'),
                'description' => __('برای حفظ مقدار قبلی، خالی بگذارید.', 'niloo-web'),
                'default'     => '',
                'require'     => [['digipay_enabled', '==', true]],
            ],
            [
                'id'      => 'digipay_username',
                'type'    => 'text',
                'title'   => __('نام کاربری (Username) دیجی‌پی', 'niloo-web'),
                'default' => '',
                'require' => [['digipay_enabled', '==', true]],
            ],
            [
                'id'          => 'digipay_password',
                'type'        => 'password',
                'title'       => __('رمز عبور (Password) دیجی‌پی', 'niloo-web'),
                'description' => __('برای حفظ مقدار قبلی، خالی بگذارید.', 'niloo-web'),
                'default'     => '',
                'require'     => [['digipay_enabled', '==', true]],
            ],
            [
                'id'      => 'digipay_title',
                'type'    => 'text',
                'title'   => __('عنوان نمایشی در صفحه پرداخت', 'niloo-web'),
                'default' => 'پرداخت اقساطی دیجی‌پی',
                'require' => [['digipay_enabled', '==', true]],
            ],
            [
                'id'      => 'digipay_description',
                'type'    => 'text',
                'title'   => __('توضیح کوتاه (اختیاری)', 'niloo-web'),
                'default' => 'پرداخت اعتباری و اقساطی با دیجی‌پی',
                'require' => [['digipay_enabled', '==', true]],
            ],
        ],
    ]);
}


function nasim_register_mino_tab_spot()
{
    Settings::register_tab(NILOO_SETTINGS_PAGE, [
        'id'          => 'spotplayer',
        'title'       => __('اسپات‌پلیر', 'niloo-web'),
        'description' => __('لایسنس ویدیوی محافظت‌شده', 'niloo-web'),
        'icon'        => 'dashicons-video-alt3',
        'fields'      => [
            [
                'id'          => 'spot_api',
                'type'        => 'password',
                'title'       => __('کلید API اسپات‌پلیر', 'niloo-web'),
                'description' => __('اگر افزونه رسمی SpotPlayer نصب باشد، این مقدار روی تنظیمات آن هم همگام می‌شود.', 'niloo-web'),
                'default'     => '',
            ],
            [
                'id'      => 'spot_test',
                'type'    => 'switch',
                'title'   => __('حالت تست لایسنس اسپات', 'niloo-web'),
                'default' => false,
            ],
            [
                'id'          => 'spot_app_url',
                'type'        => 'url',
                'title'       => __('لینک دانلود اپلیکیشن اسپات', 'niloo-web'),
                'default'     => 'https://spotplayer.ir/',
            ],
        ],
    ]);
}


function nasim_register_mino_tab_social()
{
    Settings::register_tab(NILOO_SETTINGS_PAGE, [
        'id'          => 'social',
        'title'       => __('شبکه‌های اجتماعی', 'niloo-web'),
        'description' => __('لینک‌های فوتر', 'niloo-web'),
        'icon'        => 'dashicons-share',
        'fields'      => [
            ['id' => 'social_instagram', 'type' => 'url', 'title' => __('اینستاگرام', 'niloo-web'), 'default' => ''],
            ['id' => 'social_telegram', 'type' => 'url', 'title' => __('تلگرام', 'niloo-web'), 'default' => ''],
            ['id' => 'social_bale', 'type' => 'url', 'title' => __('بله', 'niloo-web'), 'default' => ''],
            ['id' => 'social_youtube', 'type' => 'url', 'title' => __('یوتیوب', 'niloo-web'), 'default' => ''],
            ['id' => 'social_linkedin', 'type' => 'url', 'title' => __('لینکدین', 'niloo-web'), 'default' => ''],
            ['id' => 'footer_copyright', 'type' => 'text', 'title' => __('متن کپی‌رایت', 'niloo-web'), 'default' => '© تمامی حقوق برای نیلوفر وفایی محفوظ می‌باشد.'],
        ],
    ]);
}


function nasim_register_mino_tab_tools()
{
    Settings::register_tab(NILOO_SETTINGS_PAGE, [
        'id'          => 'tools',
        'title'       => __('ابزارها', 'niloo-web'),
        'description' => __('تست پیامک و وضعیت', 'niloo-web'),
        'icon'        => 'dashicons-admin-tools',
        'callback'    => 'nasim_render_mino_tools_tab',
    ]);
}


/**
 * Custom tools tab (SMS test + status).
 *
 * @param string               $page_id Page.
 * @param array<string,mixed>  $tab     Tab.
 * @param array<string,mixed>  $values  Values.
 */
function nasim_render_mino_tools_tab($page_id, $tab, $values)
{
    unset($page_id, $tab);

    $provider = (string) ($values['sms_provider'] ?? 'smsir');
    $labels   = function_exists('nasim_sms_providers') ? nasim_sms_providers() : ['smsir' => 'sms.ir', 'msgway' => 'راه پیام'];
    $test     = get_transient('nasim_sms_test_' . get_current_user_id());
    if ($test) {
        delete_transient('nasim_sms_test_' . get_current_user_id());
    }

    $gateway_id = (string) ($values['payment_gateway'] ?? 'zibal');
    $gw_labels  = function_exists('nasim_payment_gateway_labels') ? nasim_payment_gateway_labels() : [];
    $configured = function_exists('nasim_payment_gateway') && nasim_payment_gateway()->is_configured();

    if ($test) {
        printf(
            '<div class="notice notice-%1$s"><p>%2$s</p></div>',
            'ok' === ($test['status'] ?? '') ? 'success' : 'error',
            esc_html((string) ($test['message'] ?? ''))
        );
    }

    $otp_login_flag = function_exists('nasim_setting') && (bool) nasim_setting('auth_otp_login');
    $reg_flag       = function_exists('nasim_setting') && (bool) nasim_setting('auth_registration');
    $sms_ready      = function_exists('nasim_sms_configured') && nasim_sms_configured();
    $login_tpl      = function_exists('nasim_sms_template') ? (int) nasim_sms_template('login') : 0;
    $otp_active     = function_exists('nasim_otp_login_enabled') && nasim_otp_login_enabled();
    $reg_active     = function_exists('nasim_registration_enabled') && nasim_registration_enabled();

    echo '<div class="mino-card" style="margin-bottom:20px">';
    echo '<h3>' . esc_html__('وضعیت ورود و ثبت‌نام با کد یک‌بارمصرف (OTP)', 'niloo-web') . '</h3>';
    printf(
        '<p>%s: %s</p>',
        esc_html__('وضعیت کلی OTP در صفحه ورود', 'niloo-web'),
        ($otp_active || $reg_active)
            ? '<span style="color:#008a20;font-weight:600">' . esc_html__('فعال و آماده ارسال کد', 'niloo-web') . '</span>'
            : '<span style="color:#b32d2e;font-weight:600">' . esc_html__('غیرفعال (موارد قرمز زیر را بررسی کنید)', 'niloo-web') . '</span>'
    );
    printf(
        '<p>%s: %s · %s: %s</p>',
        esc_html__('سوییچ ورود با OTP', 'niloo-web'),
        $otp_login_flag ? '<span style="color:#008a20">' . esc_html__('روشن', 'niloo-web') . '</span>' : '<span style="color:#b32d2e">' . esc_html__('خاموش', 'niloo-web') . '</span>',
        esc_html__('سوییچ ثبت‌نام جدید', 'niloo-web'),
        $reg_flag ? '<span style="color:#008a20">' . esc_html__('روشن', 'niloo-web') . '</span>' : '<span style="color:#b32d2e">' . esc_html__('خاموش', 'niloo-web') . '</span>'
    );
    printf(
        '<p>%s: <strong>%s</strong> — %s · %s: %s</p>',
        esc_html__('سامانه پیامک', 'niloo-web'),
        esc_html($labels[$provider] ?? $provider),
        $sms_ready ? '<span style="color:#008a20">' . esc_html__('کلید API ثبت شده', 'niloo-web') . '</span>' : '<span style="color:#b32d2e">' . esc_html__('کلید API وارد نشده', 'niloo-web') . '</span>',
        esc_html__('کد قالب ورود', 'niloo-web'),
        $login_tpl > 0 ? '<span style="color:#008a20" dir="ltr">#' . esc_html((string) $login_tpl) . '</span>' : '<span style="color:#b32d2e">' . esc_html__('تنظیم نشده', 'niloo-web') . '</span>'
    );
    echo '</div>';

    echo '<div class="mino-card" style="margin-bottom:20px">';
    echo '<h3>' . esc_html__('وضعیت درگاه‌های پرداخت', 'niloo-web') . '</h3>';
    printf(
        '<p>%s: <strong>%s</strong> — %s</p>',
        esc_html__('درگاه نقدی فعال', 'niloo-web'),
        esc_html($gw_labels[$gateway_id] ?? $gateway_id),
        $configured
            ? '<span style="color:#008a20">' . esc_html__('پیکربندی شده', 'niloo-web') . '</span>'
            : '<span style="color:#b32d2e">' . esc_html__('مرچنت تنظیم نشده', 'niloo-web') . '</span>'
    );
    if (class_exists('Nasim_Snapppay_Gateway')) {
        $spp = Nasim_Snapppay_Gateway::from_settings();
        printf(
            '<p>%s: %s</p>',
            esc_html__('اقساطی اسنپ‌پی', 'niloo-web'),
            $spp->is_available()
                ? '<span style="color:#008a20">' . esc_html__('فعال و پیکربندی شده', 'niloo-web') . '</span>'
                : ($spp->is_enabled()
                    ? '<span style="color:#b32d2e">' . esc_html__('فعال ولی اطلاعات API ناقص است', 'niloo-web') . '</span>'
                    : '<span style="color:#646970">' . esc_html__('غیرفعال', 'niloo-web') . '</span>')
        );
    }
    if (class_exists('Nasim_Digipay_Gateway')) {
        $dp = Nasim_Digipay_Gateway::from_settings();
        printf(
            '<p>%s: %s</p>',
            esc_html__('اقساطی دیجی‌پی', 'niloo-web'),
            $dp->is_available()
                ? '<span style="color:#008a20">' . esc_html__('فعال و پیکربندی شده', 'niloo-web') . '</span>'
                : ($dp->is_enabled()
                    ? '<span style="color:#b32d2e">' . esc_html__('فعال ولی اطلاعات API ناقص است', 'niloo-web') . '</span>'
                    : '<span style="color:#646970">' . esc_html__('غیرفعال', 'niloo-web') . '</span>')
        );
    }
    echo '</div>';

    echo '<div class="mino-card">';
    echo '<h3>' . esc_html__('ارسال پیامک آزمایشی', 'niloo-web') . '</h3>';
    printf(
        '<p>%s</p>',
        esc_html(sprintf(
            __('با سامانه ذخیره‌شده (%s) ارسال می‌شود.', 'niloo-web'),
            $labels[$provider] ?? $provider
        ))
    );
    echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="nasim_sms_test">';
    wp_nonce_field('nasim_sms_test');
    echo '<p><input type="tel" name="mobile" class="regular-text" dir="ltr" placeholder="09123456789" required></p>';
    echo '<p><select name="type"><option value="code">' . esc_html__('کد ورود', 'niloo-web') . '</option>';
    echo '<option value="subscription">' . esc_html__('پیام خرید/دسترسی', 'niloo-web') . '</option></select></p>';
    submit_button(__('ارسال آزمایشی', 'niloo-web'), 'secondary', 'submit', false);
    echo '</form></div>';
}


/**
 * After save: sync SpotPlayer option + flush SMS credit cache + mirror legacy keys.
 *
 * @param array<string,mixed> $values Values.
 */
function nasim_on_mino_settings_saved(array $values, string $page_id)
{
    if (NILOO_SETTINGS_PAGE !== $page_id) {
        return;
    }

    delete_transient('nasim_smsir_credit');
    delete_transient('nasim_snapppay_bearer_token');
    delete_transient('nasim_digipay_access_token');

    // Keep legacy options in sync for any leftover readers.
    $legacy = (array) get_option('nasim_theme_settings', []);
    foreach ([
        'sms_provider', 'smsir_api_key', 'smsir_login_template', 'smsir_register_template',
        'smsir_subscription_template', 'smsir_code_param', 'msgway_api_key', 'msgway_login_template',
        'msgway_register_template', 'msgway_subscription_template', 'msgway_line', 'sms_subscription',
        'sms_subscription_params', 'otp_length', 'otp_ttl', 'otp_resend', 'auth_otp_login', 'auth_registration',
    ] as $key) {
        if (array_key_exists($key, $values)) {
            $val = $values[$key];
            if (is_bool($val)) {
                $val = $val ? 1 : 0;
            }
            $legacy[$key] = $val;
        }
    }
    update_option('nasim_theme_settings', $legacy, false);

    if (isset($values['zibal_merchant'])) {
        update_option('nasim_zibal_merchant', sanitize_text_field((string) $values['zibal_merchant']), false);
    }
    if (array_key_exists('zibal_sandbox', $values)) {
        update_option('nasim_zibal_sandbox', !empty($values['zibal_sandbox']) ? 1 : 0, false);
    }

    $spot = get_option('spotplayer', []);
    if (!is_array($spot)) {
        $spot = [];
    }
    if (isset($values['spot_api']) && '' !== (string) $values['spot_api']) {
        $spot['api'] = sanitize_text_field((string) $values['spot_api']);
    }
    if (array_key_exists('spot_test', $values)) {
        $spot['test'] = !empty($values['spot_test']) ? 1 : 0;
    }
    update_option('spotplayer', $spot, false);
}
add_action('mino_saved', 'nasim_on_mino_settings_saved', 10, 2);


/**
 * Inject CSS variables from color settings.
 */
function nasim_output_theme_color_vars()
{
    $map = [
        '--purple'      => 'color_primary',
        '--purple-2'    => 'color_primary_soft',
        '--purple-dark' => 'color_primary_dark',
        '--accent'      => 'color_accent',
        '--ink'         => 'color_ink',
        '--muted'       => 'color_muted',
        '--soft'        => 'color_soft',
    ];

    $rules = [];
    foreach ($map as $css => $key) {
        $val = (string) nasim_opt($key, '');
        if ($val && preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $val)) {
            $rules[] = $css . ':' . $val;
        }
    }

    if (!$rules) {
        return;
    }

    $css = ':root{' . implode(';', $rules) . '}';
    wp_add_inline_style(wp_style_is('nasim-design', 'enqueued') ? 'nasim-design' : 'nasim-main', $css);
}
add_action('wp_enqueue_scripts', 'nasim_output_theme_color_vars', 30);


/**
 * Redirect old settings URLs to Mino page.
 */
function nasim_redirect_legacy_settings_pages()
{
    if (!is_admin() || empty($_GET['page'])) {
        return;
    }

    $page = sanitize_key(wp_unslash((string) $_GET['page']));
    $map  = [
        'nasim-theme-settings'   => ['mino_tab' => 'sms'],
        'nasim-payment-settings' => ['mino_tab' => 'payment'],
    ];

    if (!isset($map[$page])) {
        return;
    }

    wp_safe_redirect(add_query_arg(array_merge(['page' => NILOO_SETTINGS_PAGE], $map[$page]), admin_url('admin.php')));
    exit;
}
add_action('admin_init', 'nasim_redirect_legacy_settings_pages', 1);
