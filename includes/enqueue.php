<?php

if(!defined('ABSPATH')) exit;



/**
 * Asset version based on file modification time, so browsers pick up rebuilt files
 */
function nasim_asset_version($relative_path) {

    $file = THEME_DIR . $relative_path;

    return file_exists($file) ? (string) filemtime($file) : wp_get_theme()->get('Version');
}


/**
 * Enqueue theme styles and scripts
 */
function nasim_enqueue_assets() {

    // Built by the Tailwind CLI: npm run build:css
    wp_enqueue_style('nasim-tailwind', THEME_URL . '/assets/css/app.css', [], nasim_asset_version('/assets/css/app.css'));

    wp_enqueue_style('nasim-main', THEME_URL . '/assets/css/tailwind.css', ['nasim-tailwind'], nasim_asset_version('/assets/css/tailwind.css'));

    wp_enqueue_script('nasim-main', THEME_URL . '/assets/js/script.js', [], nasim_asset_version('/assets/js/script.js'), true);

    if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
    }

    if (
        is_singular('nasim_course')
        || is_post_type_archive('nasim_course')
        || (nasim_store_enabled() && is_singular('nasim_product'))
        || (nasim_store_enabled() && is_post_type_archive('nasim_product'))
        || (nasim_store_enabled() && is_tax('nasim_product_cat'))
        || nasim_is_subscription_page()
    ) {
        wp_enqueue_style('nasim-lms', THEME_URL . '/assets/css/lms.css', ['nasim-main'], nasim_asset_version('/assets/css/lms.css'));
    }

    if (nasim_is_auth_page()) {
        wp_enqueue_style('nasim-auth', THEME_URL . '/assets/css/auth.css', ['nasim-main'], nasim_asset_version('/assets/css/auth.css'));
        wp_enqueue_script('nasim-auth', THEME_URL . '/assets/js/auth.js', [], nasim_asset_version('/assets/js/auth.js'), true);
        wp_localize_script('nasim-auth', 'nasimAuth', [
            'ajaxUrl'    => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('nasim_auth'),
            'codeLength' => (int) nasim_setting('otp_length'),
            'urls'       => ['login' => nasim_auth_url('login'), 'register' => nasim_auth_url('register')],
            'i18n'       => [
                'error'    => 'خطایی رخ داد؛ دوباره تلاش کن.',
                'resendIn' => 'ارسال دوباره تا %s',
                'register' => 'ثبت‌نام کن',
                'show'     => 'نمایش رمز عبور',
                'hide'     => 'پنهان کردن رمز عبور',
            ],
        ]);
    }

    if (nasim_is_course_player()) {
        $course_id = get_queried_object_id();
        $lesson    = nasim_current_lesson();

        wp_enqueue_script('nasim-lms', THEME_URL . '/assets/js/lms/lms.js', [], nasim_asset_version('/assets/js/lms/lms.js'), true);
        wp_localize_script('nasim-lms', 'nasimLms', [
            'ajaxUrl'  => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('nasim_lesson'),
            'courseId' => $course_id,
            'lessonId' => $lesson ? $lesson['id'] : '',
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
 * Preload the local IRANYekanX font to avoid a late font swap
 */
function nasim_preload_font() {

    printf(
        '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
        esc_url(THEME_URL . '/assets/fonts/IRANYekanX/IRANYekanXVF.woff2')
    );
}
add_action('wp_head', 'nasim_preload_font', 1);
