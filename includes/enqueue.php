<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Asset version based on file modification time, so browsers pick up rebuilt files.
 */
function nasim_asset_version($relative_path)
{
    $file = THEME_DIR . $relative_path;

    return file_exists($file) ? (string) filemtime($file) : wp_get_theme()->get('Version');
}

/**
 * Enqueue theme styles and scripts (Tailwind CSS v4 unified design system).
 */
function nasim_enqueue_assets()
{
    // Compiled Tailwind CSS v4 stylesheet: npm run build:css
    wp_enqueue_style(
        'nasim-theme',
        THEME_URL . '/assets/css/theme.css',
        [],
        nasim_asset_version('/assets/css/theme.css')
    );

    // Keep legacy style handles registered as lightweight aliases for Elementor widgets compatibility.
    wp_register_style('nasim-design', false, ['nasim-theme']);
    wp_register_style('nasim-home', false, ['nasim-theme']);

    wp_enqueue_script(
        'nasim-main',
        THEME_URL . '/assets/js/script.js',
        [],
        nasim_asset_version('/assets/js/script.js'),
        true
    );

    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }

    if (is_singular('nasim_bundle')) {
        wp_enqueue_script(
            'nasim-express-checkout',
            THEME_URL . '/assets/js/express-checkout.js',
            [],
            nasim_asset_version('/assets/js/express-checkout.js'),
            true
        );
        wp_localize_script('nasim-express-checkout', 'nasimExpress', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('nasim_express_checkout'),
            'i18n'    => [
                'error'  => 'خطایی رخ داد؛ دوباره تلاش کن.',
                'paying' => 'در حال انتقال به درگاه پرداخت…',
            ],
        ]);
    }

    if (is_singular('nasim_course') && !nasim_is_course_player()) {
        $preview_media = function_exists('nasim_course_preview_media')
            ? nasim_course_preview_media(get_queried_object_id())
            : null;
        $preview_plyr  = is_array($preview_media) && !empty($preview_media['use_plyr']);

        if ($preview_plyr) {
            wp_enqueue_style(
                'nasim-plyr',
                THEME_URL . '/assets/vendor/plyr/plyr.css',
                [],
                nasim_asset_version('/assets/vendor/plyr/plyr.css')
            );
            wp_enqueue_script(
                'nasim-plyr',
                THEME_URL . '/assets/vendor/plyr/plyr.min.js',
                [],
                nasim_asset_version('/assets/vendor/plyr/plyr.min.js'),
                true
            );
        }

        wp_enqueue_script(
            'nasim-express-checkout',
            THEME_URL . '/assets/js/express-checkout.js',
            [],
            nasim_asset_version('/assets/js/express-checkout.js'),
            true
        );
        wp_localize_script('nasim-express-checkout', 'nasimExpress', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('nasim_express_checkout'),
            'i18n'    => [
                'error'  => 'خطایی رخ داد؛ دوباره تلاش کن.',
                'paying' => 'در حال انتقال به درگاه پرداخت…',
            ],
        ]);
    }

    if (nasim_is_auth_page()) {
        wp_enqueue_script(
            'nasim-auth',
            THEME_URL . '/assets/js/auth.js',
            [],
            nasim_asset_version('/assets/js/auth.js'),
            true
        );
        wp_localize_script('nasim-auth', 'nasimAuth', [
            'ajaxUrl'    => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('nasim_auth'),
            'codeLength' => (int) nasim_setting('otp_length'),
            'urls'       => ['login' => nasim_auth_url('login'), 'register' => nasim_auth_url('register')],
            'i18n'       => [
                'error'            => 'خطایی رخ داد؛ دوباره تلاش کن.',
                'network'          => 'اتصال اینترنت را بررسی کن و دوباره تلاش کن.',
                'resendIn'         => 'ارسال دوباره کد تا %s',
                'show'             => 'نمایش رمز عبور',
                'hide'             => 'پنهان کردن رمز عبور',
                'badMobile'        => 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.',
                'needName'         => 'نام و نام خانوادگی‌ات را بنویس.',
                'needCode'         => 'کد تأیید را کامل وارد کن.',
                'login'            => 'تأیید',
                'createAccount'    => 'تأیید و ساخت حساب',
                'usePassword'      => 'ورود با رمز عبور',
                'success'          => 'در حال ورود به پنل...',
                'codeTitle'        => 'ورود کد تأیید',
                'codeSubtitle'     => '',
                'newTitle'         => 'تکمیل ثبت‌نام',
                'newSubtitle'      => '',
                'passwordTitle'    => 'ورود با رمز عبور',
                'passwordSubtitle' => 'لطفاً برای ورود، موبایل یا ایمیل و رمز خود را وارد کنید',
            ],
        ]);
    }

    if (nasim_is_course_player()) {
        $course_id = get_queried_object_id();
        $lesson    = nasim_current_lesson();
        $use_plyr  = $lesson && function_exists('nasim_lesson_uses_plyr') && nasim_lesson_uses_plyr($course_id, $lesson);

        if ($use_plyr) {
            wp_enqueue_style(
                'nasim-plyr',
                THEME_URL . '/assets/vendor/plyr/plyr.css',
                [],
                nasim_asset_version('/assets/vendor/plyr/plyr.css')
            );
            wp_enqueue_script(
                'nasim-plyr',
                THEME_URL . '/assets/vendor/plyr/plyr.min.js',
                [],
                nasim_asset_version('/assets/vendor/plyr/plyr.min.js'),
                true
            );
        }

        wp_enqueue_script(
            'nasim-lms',
            THEME_URL . '/assets/js/lms/lms.js',
            $use_plyr ? ['nasim-plyr'] : [],
            nasim_asset_version('/assets/js/lms/lms.js'),
            true
        );
        wp_localize_script('nasim-lms', 'nasimLms', [
            'ajaxUrl'  => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('nasim_lesson'),
            'courseId' => $course_id,
            'lessonId' => $lesson ? $lesson['id'] : '',
            'usePlyr'  => (bool) $use_plyr,
            'i18n'     => [
                'complete'   => 'علامت‌گذاری به‌عنوان تکمیل‌شده ✓',
                'completed'  => 'تکمیل شد ✓ (لغو)',
                'bookmark'   => '♡ <span>ذخیره درس</span>',
                'bookmarked' => '♥ <span>ذخیره شد</span>',
                'noteSaved'  => 'یادداشت ذخیره شد.',
                'error'      => 'خطایی رخ داد؛ دوباره تلاش کن.',
            ],
        ]);
    }
}
add_action('wp_enqueue_scripts', 'nasim_enqueue_assets');

/**
 * Add `js` class to <html> immediately and preload local IRANYekanX variable font.
 */
function nasim_preload_font()
{
    echo '<script>document.documentElement.classList.add("js");</script>' . "\n";
    printf(
        '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
        esc_url(THEME_URL . '/assets/fonts/IRANYekanX/IRANYekanXVF.woff2')
    );
}
add_action('wp_head', 'nasim_preload_font', 1);
