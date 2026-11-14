<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Canonical public profile — defaults are آکادمی نیلوفر وفایی (filterable).
 */

/**
 * @return array<string, mixed>
 */
function nasim_profile() {

    static $profile = null;

    if (null !== $profile) {
        return $profile;
    }

    $profile = [
        'name'          => 'نیلوفر وفایی',
        'legal_name'    => 'نیلوفر وفایی',
        'role'          => 'طراح و توسعه‌دهنده وب · مدرس آکادمی اولترا',
        'tagline'       => 'مدرس و توسعه‌دهنده وب، بنیان‌گذار وبلبز و آکادمی اولترا.',
        'location'      => 'ایران',
        'bio_url'       => home_url('/connect/'),
        'bio_label'     => 'داستان کامل من را بخوان',
        'telegram'      => '',
        'telegram_user' => '',
        'phone'         => '',
        'phone_tel'     => '',
        'hours'         => '',
        'author_bio'    => 'طراح و توسعه‌دهنده وب؛ بنیان‌گذار وبلبز و آکادمی اولترا. تمرکز روی آموزش کاربردی و پروژه‌های واقعی.',
        'about_short'   => 'سلام، من نیلوفر وفایی، طراح و توسعه‌دهنده وب هستم. تجربه‌ام در آموزش، طراحی و اجرای پروژه‌های واقعی کمک می‌کند ایده‌ها را به راهکار دیجیتال کاربردی تبدیل کنیم؛ از ساخت سایت حرفه‌ای تا یادگیری مسیر گرفتن پروژه.',
        'domains'       => [
            ['title' => 'توسعه وب', 'text' => 'طراحی و توسعه وب‌سایت‌های حرفه‌ای متناسب با نیاز کسب‌وکار'],
            ['title' => 'آموزش', 'text' => 'یادگیری مهارت‌های کاربردی برای ورود به دنیای طراحی و توسعه وب'],
            ['title' => 'محصول', 'text' => 'معرفی محصولات و راهکارهای دیجیتال برای حل نیازهای واقعی'],
        ],
        'skills'        => [
            'WordPress', 'Elementor', 'WooCommerce', 'UI/UX', 'Figma', 'آموزش بازارمحور U3P',
        ],
        'services'      => [
            ['title' => 'وبلبز', 'text' => 'طراحی و توسعه سایت شرکتی/فروشگاهی، سامانه اختصاصی، سئو و مشاوره'],
            ['title' => 'آکادمی اولترا', 'text' => 'آموزش طراحی سایت، نمونه‌کار و مسیر گرفتن پروژه'],
            ['title' => 'محصولات وردپرس', 'text' => 'توسعه و ارائه راهکارهای وردپرسی در وبلبز'],
        ],
        'instagram'     => 'https://www.instagram.com/niloofarvafaei',
        'bale'          => 'https://ble.ir/niloofarvafaei',
    ];

    return apply_filters('nasim_profile', $profile);
}


/**
 * Permalink of the about / bio page.
 */
function nasim_profile_bio_url() {

    $p = nasim_profile();
    if (!empty($p['bio_url'])) {
        return $p['bio_url'];
    }

    return home_url('/connect/');
}


/**
 * Gutenberg blocks for the About page.
 *
 * @return string[]
 */
function nasim_profile_about_page_blocks() {

    if (function_exists('nasim_niloo_page_blocks_connect')) {
        return nasim_niloo_page_blocks_connect();
    }

    $p       = nasim_profile();
    $bio     = nasim_profile_bio_url();
    $domains = [];
    foreach ($p['domains'] as $item) {
        $domains[] = '<strong>' . esc_html($item['title']) . ':</strong> ' . esc_html($item['text']);
    }

    return [
        nasim_block_paragraph('سلام، من <strong>' . esc_html($p['name']) . '</strong> هستم — ' . esc_html($p['role']) . '.'),
        nasim_block_paragraph(esc_html($p['about_short'])),
        nasim_block_heading('حوزه‌های کاری'),
        nasim_block_list($domains),
        nasim_block_paragraph('صفحه معرفی: ' . nasim_block_link($bio, $p['bio_label'] ?: 'درباره من')),
    ];
}


/**
 * Gutenberg blocks for the Contact page.
 *
 * @return string[]
 */
function nasim_profile_contact_page_blocks() {

    if (function_exists('nasim_niloo_page_blocks_contact')) {
        return nasim_niloo_page_blocks_contact();
    }

    $p = nasim_profile();

    return [
        nasim_block_paragraph('برای سفارش پروژه، آموزش یا همکاری در ارتباط باشیم.'),
        nasim_block_heading('راه‌های ارتباط'),
        nasim_block_list([
            'اینستاگرام: ' . nasim_block_link($p['instagram'] ?? '#', '@niloofarvafaei'),
            'صفحه معرفی: ' . nasim_block_link(nasim_profile_bio_url(), 'معرفی نیلوفر وفایی'),
        ]),
    ];
}
