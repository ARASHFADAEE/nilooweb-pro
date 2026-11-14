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
    $sections['nasim_hero']['fields']['hero_image']['default']             = 'https://niloofarvafaei.ir/wp-content/uploads/2026/03/niloo-hero.webp';
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

    $sections['nasim_about']['fields']['about_eyebrow']['default']          = 'چرا این مسیر؟';
    $sections['nasim_about']['fields']['about_title']['default']            = 'من';
    $sections['nasim_about']['fields']['about_title_highlight']['default']  = 'نیلوفر وفایی هستم';
    $sections['nasim_about']['fields']['about_text']['default']             = 'طراح و توسعه‌دهنده وب با سال‌ها تجربه در طراحی سایت و آموزش؛ هدفم رساندن شما از یادگیری تئوری به اجرای پروژه‌های واقعی و کسب درآمد از طراحی وب است.';
    $sections['nasim_about']['fields']['about_sticker']['default']          = 'آموزش · طراحی سایت';
    $sections['nasim_about']['fields']['about_image']['default']            = 'https://niloofarvafaei.ir/wp-content/uploads/2026/03/niloo-about.webp';
    $sections['nasim_about']['fields']['about_button_text']['default']      = 'درباره من';
    $sections['nasim_about']['fields']['about_button_url']['default']       = home_url('/connect/');
    $sections['nasim_about']['fields']['about_stat_1_value']['default']     = '+۱۰۰۰';
    $sections['nasim_about']['fields']['about_stat_1_label']['default']     = 'دانشجویان';
    $sections['nasim_about']['fields']['about_stat_2_value']['default']     = '۱۰۰٪';
    $sections['nasim_about']['fields']['about_stat_2_label']['default']     = 'پروژه‌محور';
    $sections['nasim_about']['fields']['about_stat_3_value']['default']     = '۲۴/۷';
    $sections['nasim_about']['fields']['about_stat_3_label']['default']     = 'پشتیبانی';

    $sections['nasim_testimonials']['fields']['testimonials_eyebrow']['default']          = 'نظرات و نتایج';
    $sections['nasim_testimonials']['fields']['testimonials_title']['default']            = 'موفقیت شما';
    $sections['nasim_testimonials']['fields']['testimonials_title_highlight']['default']  = 'افتخار ماست';

    $sections['nasim_articles']['fields']['articles_eyebrow']['default']         = 'مقالات ما';
    $sections['nasim_articles']['fields']['articles_title']['default']           = 'جدیدترین';
    $sections['nasim_articles']['fields']['articles_title_highlight']['default'] = 'آموزش‌ها';

    $sections['nasim_cta']['fields']['cta_eyebrow']['default']     = 'خبرنامه';
    $sections['nasim_cta']['fields']['cta_title']['default']       = 'برای رشد در مسیر طراحی سایت؛ همراه ما باشید';
    $sections['nasim_cta']['fields']['cta_text']['default']        = 'جدیدترین آموزش‌ها، مقالات و دوره‌ها را با ما دنبال کنید.';
    $sections['nasim_cta']['fields']['cta_button_text']['default'] = '';

    $sections['nasim_footer']['fields']['footer_tagline']['default']   = 'آموزش طراحی سایت و کسب درآمد به زبان ساده و کاربردی، همراه با پروژه‌های واقعی و پشتیبانی تا ورود شما به بازار کار.';
    $sections['nasim_footer']['fields']['footer_copyright']['default'] = '© تمامی حقوق برای نیلوفر وفایی محفوظ می‌باشد.';
    $sections['nasim_footer']['fields']['social_instagram']['default'] = 'https://www.instagram.com/niloofarvafaei';

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
        'courses_home_count'           => ['label' => __('تعداد دوره', 'niloo-web'), 'type' => 'number', 'default' => 2],
        'radio_eyebrow'                => ['label' => __('رادیو نیل: تیتر', 'niloo-web'), 'default' => 'رادیو نیل'],
        'radio_title'                  => ['label' => __('رادیو نیل: عنوان', 'niloo-web'), 'default' => 'پادکست و گفتگو'],
        'radio_title_highlight'        => ['label' => __('رادیو نیل: رنگی', 'niloo-web'), 'default' => 'در مسیر طراحی'],
        'radio_cat'                    => ['label' => __('اسلاگ دسته مقالات رادیو', 'niloo-web'), 'default' => 'podcast'],
        'radio_more_url'               => ['label' => __('لینک همه', 'niloo-web'), 'type' => 'url', 'default' => home_url('/%d8%b1%d8%a7%d8%af%db%8c%d9%88%d9%86%db%8c%d9%84/')],
    ]);

    return $sections;
}
add_filter('nasim_customizer_sections', 'nasim_niloo_customizer_sections', 20);

/**
 * Public profile for niloo (about block, author).
 *
 * @param array<string, mixed> $profile
 */
function nasim_niloo_profile(array $profile): array
{
    return array_merge($profile, [
        'name'        => 'نیلوفر وفایی',
        'role'        => 'طراح و مدرس طراحی سایت',
        'tagline'     => 'آموزش طراحی سایت و کسب درآمد به زبان ساده و کاربردی.',
        'about_short' => 'طراح و توسعه‌دهنده وب؛ از یادگیری پایه تا پروژه واقعی و ورود به بازار کار کنارتان هستم.',
        'bio_label'   => 'درباره من',
        'bio_url'     => home_url('/connect/'),
        'domains'     => [
            ['title' => 'آموزش از پایه', 'text' => 'ساده، کاربردی و مرحله‌به‌مرحله'],
            ['title' => 'پشتیبانی', 'text' => 'همراهی در مسیر یادگیری و درآمد'],
            ['title' => 'بازار کار', 'text' => 'آماده‌سازی برای پروژه واقعی'],
        ],
        'author_bio'  => 'مدرس طراحی سایت و مسیر فریلنسری؛ تمرکز روی پروژه‌های واقعی.',
    ]);
}
add_filter('nasim_profile', 'nasim_niloo_profile');