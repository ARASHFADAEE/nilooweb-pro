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
        'role'          => 'طراح و مدرس طراحی سایت',
        'tagline'       => 'آموزش طراحی سایت و کسب درآمد به زبان ساده و کاربردی.',
        'location'      => 'ایران',
        'bio_url'       => home_url('/connect/'),
        'bio_label'     => 'درباره من',
        'telegram'      => '',
        'telegram_user' => '',
        'phone'         => '',
        'phone_tel'     => '',
        'hours'         => '',
        'author_bio'    => 'مدرس طراحی سایت و مسیر فریلنسری؛ تمرکز روی پروژه‌های واقعی.',
        'about_short'   => 'طراح و توسعه‌دهنده وب؛ از یادگیری پایه تا پروژه واقعی و ورود به بازار کار کنارتان هستم.',
        'domains'       => [
            ['title' => 'آموزش از پایه', 'text' => 'ساده، کاربردی و مرحله‌به‌مرحله'],
            ['title' => 'پشتیبانی', 'text' => 'همراهی در مسیر یادگیری و درآمد'],
            ['title' => 'بازار کار', 'text' => 'آماده‌سازی برای پروژه واقعی'],
        ],
        'skills'        => [
            'WordPress', 'Elementor', 'WooCommerce', 'UI/UX', 'Figma', 'HTML', 'CSS',
        ],
        'services'      => [
            ['title' => 'آموزش طراحی سایت', 'text' => 'از پایه تا پروژه واقعی'],
            ['title' => 'مسیر فریلنسری', 'text' => 'گرفتن پروژه و درآمد'],
            ['title' => 'سفارش طراحی سایت', 'text' => 'همراهی تا تحویل'],
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

    $p       = nasim_profile();
    $bio     = nasim_profile_bio_url();
    $skills  = array_map('esc_html', $p['skills']);
    $domains = [];
    foreach ($p['domains'] as $item) {
        $domains[] = '<strong>' . esc_html($item['title']) . ':</strong> ' . esc_html($item['text']);
    }
    $services = [];
    foreach ($p['services'] as $item) {
        $services[] = '<strong>' . esc_html($item['title']) . ':</strong> ' . esc_html($item['text']);
    }

    $blocks = [
        nasim_block_paragraph('سلام، من <strong>' . esc_html($p['name']) . '</strong> هستم — ' . esc_html($p['role']) . '.'),
        nasim_block_paragraph(esc_html($p['tagline'])),
        nasim_block_heading('حوزه‌های کاری'),
        nasim_block_list($domains),
        nasim_block_heading('مهارت‌ها'),
        nasim_block_paragraph(implode(' · ', $skills)),
        nasim_block_heading('چه کمکی می‌کنم؟'),
        nasim_block_list($services),
    ];

    if (!empty($p['instagram']) || !empty($p['bale'])) {
        $links = [];
        if (!empty($p['instagram'])) {
            $links[] = 'اینستاگرام: ' . nasim_block_link($p['instagram'], '@niloofarvafaei');
        }
        if (!empty($p['bale'])) {
            $links[] = 'بله: ' . nasim_block_link($p['bale'], 'کانال بله');
        }
        $blocks[] = nasim_block_heading('ارتباط');
        $blocks[] = nasim_block_list($links);
    }

    $blocks[] = nasim_block_paragraph(
        'برای آشنایی بیشتر: ' . nasim_block_link($bio, $p['bio_label'] ?: 'درباره من')
    );

    return $blocks;
}


/**
 * Gutenberg blocks for the Contact page.
 *
 * @return string[]
 */
function nasim_profile_contact_page_blocks() {

    $p   = nasim_profile();
    $bio = nasim_profile_bio_url();
    $links = [];
    if (!empty($p['instagram'])) {
        $links[] = 'اینستاگرام: ' . nasim_block_link($p['instagram'], '@niloofarvafaei');
    }
    if (!empty($p['bale'])) {
        $links[] = 'بله: ' . nasim_block_link($p['bale'], 'کانال بله');
    }
    if (!empty($p['telegram'])) {
        $links[] = 'تلگرام: ' . nasim_block_link($p['telegram'], $p['telegram_user'] ?: 'تلگرام');
    }

    return [
        nasim_block_paragraph('اگر برای یادگیری طراحی سایت، مشاوره مسیر شغلی، یا سفارش سایت نیاز به همراهی داری، خوشحال می‌شوم کمکت کنم.'),
        nasim_block_heading('راه‌های ارتباط'),
        $links ? nasim_block_list($links) : nasim_block_paragraph('از صفحه معرفی یا فرم مشاوره پیام بده.'),
        nasim_block_paragraph('صفحه معرفی: ' . nasim_block_link($bio, esc_html($p['name']))),
    ];
}
