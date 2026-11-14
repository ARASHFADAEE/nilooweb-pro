<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Canonical public profile for Arash Fadaee — used by About, Contact, author box, and SEO.
 *
 * Full story lives on the bio article; this file keeps the short, conversion-oriented facts.
 */

/**
 * @return array<string, mixed>
 */
function nasim_profile() {

    static $profile = null;

    if (null !== $profile) {
        return $profile;
    }

    $bio_url = home_url('/who-is-arash-fadaee/');

    $profile = [
        'name'          => 'آرش فدایی',
        'legal_name'    => 'حسین فدایی',
        'role'          => 'فریلنسر توسعه وب و وردپرس',
        'tagline'       => 'برنامه‌نویس، توسعه‌دهنده و عاشق تکنولوژی که همیشه دنبال یادگیری و ساختن چیزهای بهتر است.',
        'location'      => 'تهران، ایران',
        'bio_url'       => $bio_url,
        'bio_label'     => 'داستان کامل من را بخوان',
        'telegram'      => 'https://t.me/fadaeedevs',
        'telegram_user' => '@fadaeedevs',
        'phone'         => '09130912706',
        'phone_tel'     => '+989130912706',
        'hours'         => 'شنبه تا چهارشنبه، ۹ تا ۱۷',
        'author_bio'    => 'توسعه‌دهنده وب با تمرکز روی وردپرس، لاراول و اتوماسیون؛ پشتیبانی سایت، افزونه آماده و پلاگین اختصاصی.',
        'about_short'   => 'سلام، من آرش فدایی هستم؛ فریلنسر توسعه وب و وردپرس در تهران. روی کیفیت، پایداری و رضایت مشتری تمرکز دارم و از پشتیبانی ماهانه تا افزونه اختصاصی کنار کسب‌وکار آنلاینت هستم.',
        'domains'       => [
            ['title' => 'وردپرس', 'text' => 'طراحی، توسعه، قالب و افزونه'],
            ['title' => 'لاراول', 'text' => 'اپلیکیشن‌های وب Full Stack'],
            ['title' => 'اتوماسیون', 'text' => 'سفارشی‌سازی و بهینه‌سازی فرایندها'],
        ],
        'skills'        => [
            'WordPress', 'Laravel', 'PHP', 'MySQL', 'Tailwind CSS', 'Nuxt.js', 'Git', 'Linux', 'Docker',
        ],
        'services'      => [
            ['title' => 'سایت‌های وردپرسی', 'text' => 'طراحی، توسعه و پشتیبانی'],
            ['title' => 'پلاگین‌های وردپرس', 'text' => 'راهکارهای کاربردی و اختصاصی'],
            ['title' => 'پروژه‌های لاراول', 'text' => 'اپلیکیشن وب و API'],
            ['title' => 'اتوماسیون', 'text' => 'ابزارهای سازمانی و سفارشی'],
        ],
    ];

    return $profile;
}


/**
 * Permalink of the long-form bio article (falls back to stored slug lookup).
 */
function nasim_profile_bio_url() {

    $posts = get_posts([
        'name'           => 'who-is-arash-fadaee',
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
    ]);

    if ($posts) {
        return get_permalink($posts[0]);
    }

    return nasim_profile()['bio_url'];
}


/**
 * Gutenberg blocks for the About page.
 *
 * @return string[]
 */
function nasim_profile_about_page_blocks() {

    $p      = nasim_profile();
    $bio    = nasim_profile_bio_url();
    $skills = array_map('esc_html', $p['skills']);
    $domains = [];
    foreach ($p['domains'] as $item) {
        $domains[] = '<strong>' . esc_html($item['title']) . ':</strong> ' . esc_html($item['text']);
    }
    $services = [];
    foreach ($p['services'] as $item) {
        $services[] = '<strong>' . esc_html($item['title']) . ':</strong> ' . esc_html($item['text']);
    }

    return [
        nasim_block_paragraph('سلام، من <strong>' . esc_html($p['name']) . '</strong> هستم — ' . esc_html($p['role']) . '.'),
        nasim_block_paragraph(esc_html($p['tagline'])),
        nasim_block_paragraph('📍 ' . esc_html($p['location'])),
        nasim_block_heading('حوزه‌های کاری'),
        nasim_block_paragraph('توسعه وب با تمرکز بر وردپرس، لاراول و اتوماسیون. تمرکز من روی کیفیت، پایداری و رضایت مشتری است.'),
        nasim_block_list($domains),
        nasim_block_heading('مهارت‌ها و تکنولوژی‌ها'),
        nasim_block_paragraph(implode(' · ', $skills)),
        nasim_block_paragraph('تکنولوژی فقط ابزار است؛ مهارت واقعی، حل مسئله است.'),
        nasim_block_heading('پروژه‌ها و تجربیات'),
        nasim_block_list($services),
        nasim_block_heading('همکاری'),
        nasim_block_list([
            'ساعات پاسخگویی: ' . esc_html($p['hours']),
            'تلگرام: ' . nasim_block_link($p['telegram'], $p['telegram_user']),
            'تلفن: ' . nasim_block_link('tel:' . $p['phone_tel'], $p['phone']),
        ]),
        nasim_block_paragraph('برای کاهش اختلاف‌ها، قبل از شروع درباره محدوده کار، زمان‌بندی و شرایط همکاری توافق می‌کنیم.'),
        nasim_block_heading('داستان کامل'),
        nasim_block_paragraph(
            'اگر می‌خواهی بدانی از خانه ریاضیات تا فریلنسری و ساخت محصول چطور رسیدم، ' .
            nasim_block_link($bio, 'مقاله «آرش فدایی کیست؟»') .
            ' را بخوان.'
        ),
        nasim_block_paragraph('اگر پروژه‌ای داری، برای همکاری یا مشاوره در ' . nasim_block_link(nasim_default_page_url('contact', home_url('/contact/')), 'تماس با من') . ' پیام بده.'),
    ];
}


/**
 * Gutenberg blocks for the Contact page.
 *
 * @return string[]
 */
function nasim_profile_contact_page_blocks() {

    $p   = nasim_profile();
    $bio = nasim_profile_bio_url();

    return [
        nasim_block_paragraph('اگر برای پشتیبانی وردپرس، توسعه افزونه اختصاصی، یا خرید افزونه آماده نیاز به همکاری داری، خوشحال می‌شوم کمکت کنم.'),
        nasim_block_heading('راه‌های ارتباط'),
        nasim_block_list([
            'تلگرام: ' . nasim_block_link($p['telegram'], $p['telegram_user']),
            'تلفن: ' . nasim_block_link('tel:' . $p['phone_tel'], $p['phone']),
            'ساعات پاسخگویی: ' . esc_html($p['hours']),
        ]),
        nasim_block_heading('قبل از شروع پروژه'),
        nasim_block_paragraph('لطفاً درباره محدوده کار، زمان‌بندی و شرایط همکاری توافق کنیم تا پروژه شفاف و بدون حاشیه پیش برود.'),
        nasim_block_paragraph('برای آشنایی بیشتر با مسیر حرفه‌ای من: ' . nasim_block_link($bio, 'آرش فدایی کیست؟')),
    ];
}
