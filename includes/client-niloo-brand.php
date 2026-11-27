<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Brand copy + Customizer defaults for نیلوفر وفایی (matches live Elementor homepage).
 *
 * @param array<string, mixed> $sections
 * @return array<string, mixed>
 */
function nasim_niloo_customizer_sections(array $sections): array
{
    $courses_url = function_exists('nasim_courses_listing_url')
        ? nasim_courses_listing_url()
        : home_url('/courses/');

    $sections['nasim_content_source']['fields']['content_source']['default'] = 'theme';

    $sections['nasim_announcement']['fields']['announcement_text']['default']       = 'آخرین بروزرسانی‌ها و فعالیت در بله';
    $sections['nasim_announcement']['fields']['announcement_link_text']['default']   = 'عضویت در کانال بله';
    $sections['nasim_announcement']['fields']['announcement_link_url']['default']    = 'https://ble.ir/niloofarvafaei';

    $sections['nasim_header']['fields']['header_cta_text']['default'] = 'شروع رایگان';
    $sections['nasim_header']['fields']['header_cta_url']['default']  = $courses_url;

    $sections['nasim_hero']['fields']['hero_eyebrow']['default']           = 'آکادمی نیلوفر وفایی';
    $sections['nasim_hero']['fields']['hero_title']['default']             = 'از یادگیری طراحی سایت';
    $sections['nasim_hero']['fields']['hero_title_highlight']['default']  = 'تا گرفتن پروژه واقعی';
    $sections['nasim_hero']['fields']['hero_description']['default']       = 'مسیر یادگیری طراحی سایت را اصولی و کاربردی طی کنید و با ساخت پروژه‌های واقعی وارد بازار کار شوید.';
    $sections['nasim_hero']['fields']['hero_primary_text']['default']      = 'مشاهده دوره‌ها';
    $sections['nasim_hero']['fields']['hero_primary_url']['default']       = $courses_url;
    $sections['nasim_hero']['fields']['hero_secondary_text']['default']    = 'شروع رایگان';
    $sections['nasim_hero']['fields']['hero_secondary_url']['default']     = home_url('/connect/');
    $sections['nasim_hero']['fields']['hero_image']['default']             = 'https://niloofarvafaei.ir/wp-content/uploads/2026/11/herosss.png';
    $sections['nasim_hero']['fields']['hero_proof_title']['default']     = '';
    $sections['nasim_hero']['fields']['hero_note_top_title']['default']    = 'پروژه محور';
    $sections['nasim_hero']['fields']['hero_note_top_text']['default']     = 'یادگیری با کار واقعی';
    $sections['nasim_hero']['fields']['hero_note_bottom_title']['default'] = 'پشتیبانی';
    $sections['nasim_hero']['fields']['hero_note_bottom_text']['default']  = 'همراهی تا ورود به بازار';
    $sections['nasim_hero']['fields']['hero_caption']['default']           = '';

    $sections['nasim_benefits']['fields']['benefit_1_title']['default'] = 'پروژه محور و عملی';
    $sections['nasim_benefits']['fields']['benefit_1_text']['default']  = 'یادگیری با انجام پروژه‌های واقعی';
    $sections['nasim_benefits']['fields']['benefit_2_title']['default'] = 'پشتیبانی و همراهی';
    $sections['nasim_benefits']['fields']['benefit_2_text']['default']  = 'همراهی تا ورود به بازار';
    $sections['nasim_benefits']['fields']['benefit_3_title']['default'] = 'آپدیت دائم و کاربردی';
    $sections['nasim_benefits']['fields']['benefit_3_text']['default']  = 'مطابق با استانداردهای روز دنیا';
    $sections['nasim_benefits']['fields']['benefit_4_title']['default'] = 'جامعه دانشجویان';
    $sections['nasim_benefits']['fields']['benefit_4_text']['default']  = 'یادگیری در کنار یک جامعه فعال';

    $sections['nasim_about']['fields']['about_eyebrow']['default']          = 'درباره من';
    $sections['nasim_about']['fields']['about_title']['default']            = 'سلام، من';
    $sections['nasim_about']['fields']['about_title_highlight']['default']  = 'نیلوفر وفایی هستم';
    $sections['nasim_about']['fields']['about_text']['default']             = 'مدرس و توسعه‌دهنده وب، بنیان‌گذار وبلبز و آکادمی اولترا. تجربه‌ام در آموزش، طراحی و اجرای پروژه‌های واقعی کمک می‌کند ایده‌ها را به راهکار دیجیتال کاربردی تبدیل کنیم و مسیر رشد کسب‌وکار یا ورود شما به بازار کار را هموارتر کنیم.';
    $sections['nasim_about']['fields']['about_sticker']['default']          = 'DESIGN · DEVELOP · EDUCATE';
    $sections['nasim_about']['fields']['about_image']['default']            = 'https://niloofarvafaei.ir/wp-content/uploads/2026/11/herosss.png';
    $sections['nasim_about']['fields']['about_button_text']['default']      = 'صفحه معرفی کامل';
    $sections['nasim_about']['fields']['about_button_url']['default']       = home_url('/connect/');
    $sections['nasim_about']['fields']['about_stat_1_value']['default']     = 'وبلبز';
    $sections['nasim_about']['fields']['about_stat_1_label']['default']     = 'محصولات وردپرس';
    $sections['nasim_about']['fields']['about_stat_2_value']['default']     = 'اولترا';
    $sections['nasim_about']['fields']['about_stat_2_label']['default']     = 'مسیر مهارت تا پروژه';
    $sections['nasim_about']['fields']['about_stat_3_value']['default']     = 'U3P';
    $sections['nasim_about']['fields']['about_stat_3_label']['default']     = 'متد بازارمحور';

    $sections['nasim_testimonials']['fields']['testimonials_eyebrow']['default']          = 'نظرات و نتایج';
    $sections['nasim_testimonials']['fields']['testimonials_title']['default']            = 'موفقیت شما';
    $sections['nasim_testimonials']['fields']['testimonials_title_highlight']['default']  = 'افتخار ماست';

    $sections['nasim_articles']['fields']['articles_eyebrow']['default']         = 'مقالات ما';
    $sections['nasim_articles']['fields']['articles_title']['default']           = 'جدیدترین';
    $sections['nasim_articles']['fields']['articles_title_highlight']['default'] = 'آموزش‌ها';

    $sections['nasim_cta']['fields']['cta_eyebrow']['default']     = 'تماس و مشاوره';
    $sections['nasim_cta']['fields']['cta_title']['default']       = 'برای شروع یک همکاری خوب، فقط یک پیام فاصله داریم';
    $sections['nasim_cta']['fields']['cta_text']['default']        = 'برای سفارش پروژه، آموزش یا همکاری در ارتباط باشیم؛ یا در خبرنامه همراه مسیر طراحی سایت بمان.';
    $sections['nasim_cta']['fields']['cta_button_text']['default'] = 'رزرو مشاوره رایگان';
    $sections['nasim_cta']['fields']['cta_button_url']['default']  = home_url('/request/');

    $sections['nasim_footer']['fields']['footer_tagline']['default']   = 'آموزش طراحی سایت و کسب درآمد به زبان ساده و کاربردی همراه با پروژه‌های واقعی و پشتیبانی تا ورود شما به بازار کار';
    $sections['nasim_footer']['fields']['footer_copyright']['default'] = 'تمامی حقوق برای ©نیلوفر وفایی محفوظ می‌باشد.';
    $sections['nasim_footer']['fields']['social_instagram']['default'] = 'https://instagram.com/niloofarvafaei.ir';
    $sections['nasim_footer']['fields']['social_bale']                = ['label' => __('بله', 'niloo-web'), 'type' => 'url', 'default' => 'https://ble.ir/niloofarvafaei'];
    $sections['nasim_footer']['fields']['social_twitter']             = ['label' => __('توییتر / X', 'niloo-web'), 'type' => 'url', 'default' => 'https://twitter.com/NiIfar77'];
    $sections['nasim_footer']['fields']['social_linkedin']            = ['label' => __('لینکدین', 'niloo-web'), 'type' => 'url', 'default' => 'https://www.linkedin.com/in/niloofar-vafaei-55b840242/'];
    $sections['nasim_footer']['fields']['social_youtube']             = ['label' => __('یوتیوب', 'niloo-web'), 'type' => 'url', 'default' => 'https://www.youtube.com/channel/UColubQELWRLE3miuKRr_Kzw'];
    $sections['nasim_footer']['fields']['social_github']              = ['label' => __('گیت‌هاب', 'niloo-web'), 'type' => 'url', 'default' => 'https://github.com/NiIfr'];
    $sections['nasim_footer']['fields']['social_discord']             = ['label' => __('دیسکورد', 'niloo-web'), 'type' => 'url', 'default' => 'https://discord.gg/nGe6Rv7Cu7'];

    if (!isset($sections['nasim_courses_home'])) {
        $sections['nasim_courses_home'] = [
            'title'  => __('دوره‌ها در صفحه اصلی', 'niloo-web'),
            'fields' => [],
        ];
    }

    $sections['nasim_courses_home']['fields'] = array_merge($sections['nasim_courses_home']['fields'] ?? [], [
        'courses_home_eyebrow'         => ['label' => __('تیتر کوچک', 'niloo-web'), 'default' => 'دوره‌های آموزشی'],
        'courses_home_title'           => ['label' => __('عنوان', 'niloo-web'), 'default' => 'مسیر یادگیری خود را'],
        'courses_home_title_highlight' => ['label' => __('عنوان رنگی', 'niloo-web'), 'default' => 'از اینجا شروع کنید'],
        'courses_home_count'           => ['label' => __('تعداد دوره', 'niloo-web'), 'type' => 'number', 'default' => 4],
        'radio_eyebrow'                => ['label' => __('رادیو نیل: تیتر', 'niloo-web'), 'default' => 'رادیو نیل'],
        'radio_title'                  => ['label' => __('رادیو نیل: عنوان', 'niloo-web'), 'default' => 'پادکست و گفتگو'],
        'radio_title_highlight'        => ['label' => __('رادیو نیل: رنگی', 'niloo-web'), 'default' => 'در مسیر طراحی'],
        'radio_cat'                    => ['label' => __('اسلاگ دسته مقالات رادیو', 'niloo-web'), 'default' => 'radionil'],
        'radio_more_url'               => ['label' => __('لینک همه', 'niloo-web'), 'type' => 'url', 'default' => ''],
    ]);

    return $sections;
}
add_filter('nasim_customizer_sections', 'nasim_niloo_customizer_sections', 20);

/**
 * SEO copy for Niloo academy (overrides legacy AWC strings).
 */
function nasim_niloo_default_description(): string
{
    return 'آموزش طراحی سایت و کسب درآمد به زبان ساده و کاربردی، همراه با پروژه‌های واقعی — آکادمی نیلوفر وفایی.';
}

function nasim_niloo_filter_document_title_parts(array $parts): array
{
    if (empty($parts['site']) || false !== strpos((string) $parts['site'], 'آرش') || false !== stripos((string) $parts['site'], 'fadaee')) {
        $parts['site'] = get_bloginfo('name') ?: 'نیلوفر وفایی';
    }

    return $parts;
}
add_filter('document_title_parts', 'nasim_niloo_filter_document_title_parts', 20);

function nasim_niloo_filter_seo_hubs(array $hubs): array
{
    if (isset($hubs['about'])) {
        $hubs['about'] = [
            'title' => 'درباره نیلوفر وفایی',
            'url'   => home_url('/connect/'),
            'desc'  => 'مسیر آموزش طراحی سایت و ورود به بازار کار',
        ];
    }

    return $hubs;
}
add_filter('nasim_seo_hubs', 'nasim_niloo_filter_seo_hubs', 30);

/**
 * Public profile for niloo (about block, author).
 *
 * @param array<string, mixed> $profile
 */
function nasim_niloo_profile(array $profile): array
{
    return array_merge($profile, [
        'name'        => 'نیلوفر وفایی',
        'legal_name'  => 'نیلوفر وفایی',
        'role'        => 'طراح و توسعه‌دهنده وب · مدرس آکادمی اولترا',
        'tagline'     => 'مدرس و توسعه‌دهنده وب، بنیان‌گذار وبلبز و آکادمی اولترا.',
        'about_short' => 'سلام، من نیلوفر وفایی، طراح و توسعه‌دهنده وب هستم. تجربه‌ام در آموزش، طراحی و اجرای پروژه‌های واقعی کمک می‌کند ایده‌ها را به راهکار دیجیتال کاربردی تبدیل کنیم؛ از ساخت سایت حرفه‌ای تا یادگیری مسیر گرفتن پروژه.',
        'bio_label'   => 'داستان کامل من را بخوان',
        'bio_url'     => home_url('/connect/'),
        'domains'     => [
            ['title' => 'توسعه وب', 'text' => 'طراحی و توسعه وب‌سایت‌های حرفه‌ای متناسب با نیاز کسب‌وکار'],
            ['title' => 'آموزش', 'text' => 'یادگیری مهارت‌های کاربردی برای ورود به دنیای طراحی و توسعه وب'],
            ['title' => 'محصول', 'text' => 'معرفی محصولات و راهکارهای دیجیتال برای حل نیازهای واقعی'],
        ],
        'skills'      => [
            'WordPress', 'Elementor', 'WooCommerce', 'UI/UX', 'Figma', 'آموزش بازارمحور U3P',
        ],
        'services'    => [
            ['title' => 'وبلبز', 'text' => 'طراحی و توسعه سایت شرکتی/فروشگاهی، سامانه اختصاصی، سئو و مشاوره'],
            ['title' => 'آکادمی اولترا', 'text' => 'آموزش طراحی سایت، نمونه‌کار و مسیر گرفتن پروژه'],
            ['title' => 'محصولات وردپرس', 'text' => 'توسعه و ارائه راهکارهای وردپرسی در وبلبز'],
        ],
        'author_bio'  => 'طراح و توسعه‌دهنده وب؛ بنیان‌گذار وبلبز و آکادمی اولترا. تمرکز روی آموزش کاربردی و پروژه‌های واقعی.',
        'instagram'   => 'https://www.instagram.com/niloofarvafaei',
        'bale'        => 'https://ble.ir/niloofarvafaei',
    ]);
}
add_filter('nasim_profile', 'nasim_niloo_profile');