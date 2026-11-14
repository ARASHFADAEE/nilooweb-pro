<?php

if(!defined('ABSPATH')) exit;



/**
 * Customizer sections and fields of the theme
 *
 * Defaults mirror the static design (index.html), so a fresh install looks identical to it.
 */
function nasim_customizer_sections() {

    $products = get_post_type_archive_link('nasim_product') ?: '#products';
    $courses  = get_post_type_archive_link('nasim_course') ?: home_url('/courses/');
    $contact  = home_url('/contact/');

    $sections = [

        'nasim_content_source' => [
            'title'       => __('منبع محتوای صفحه اصلی', 'niloo-web'),
            'description' => __('انتخاب کن متن‌های صفحه اصلی از پیش‌فرض سئو‌شده قالب بیاید، یا از مقادیری که خودت در بخش‌های پایین ذخیره کرده‌ای.', 'niloo-web'),
            'fields'      => [
                'content_source' => [
                    'label'   => __('منبع محتوا', 'niloo-web'),
                    'type'    => 'select',
                    'default' => 'theme',
                    'choices' => [
                        'theme'  => __('محتوای پیش‌فرض قالب (پیشنهادی برای سئو)', 'niloo-web'),
                        'custom' => __('محتوای تنظیم‌شده در تنظیمات قالب', 'niloo-web'),
                    ],
                ],
            ],
        ],

        'nasim_announcement' => [
            'title'  => __('نوار اطلاع‌رسانی', 'niloo-web'),
            'fields' => [
                'announcement_text'      => ['label' => __('متن', 'niloo-web'), 'default' => 'پشتیبانی وردپرس، افزونه اختصاصی و رشد فروش آنلاین'],
                'announcement_link_text' => ['label' => __('متن لینک', 'niloo-web'), 'default' => 'مشاهده افزونه‌ها'],
                'announcement_link_url'  => ['label' => __('آدرس لینک', 'niloo-web'), 'type' => 'url', 'default' => $products],
            ],
        ],

        'nasim_header' => [
            'title'  => __('هدر', 'niloo-web'),
            'fields' => [
                'header_cta_text' => ['label' => __('متن دکمه', 'niloo-web'), 'default' => 'درخواست پشتیبانی'],
                'header_cta_url'  => ['label' => __('آدرس دکمه', 'niloo-web'), 'type' => 'url', 'default' => $contact],
            ],
        ],

        'nasim_hero' => [
            'title'  => __('بخش اول (Hero)', 'niloo-web'),
            'fields' => [
                'hero_eyebrow'           => ['label' => __('تیتر کوچک', 'niloo-web'), 'default' => 'پشتیبانی و توسعه وردپرس'],
                'hero_title'             => ['label' => __('عنوان', 'niloo-web'), 'default' => 'سایت وردپرسی‌ات را'],
                'hero_title_highlight'   => ['label' => __('عنوان رنگی', 'niloo-web'), 'default' => 'پایدار و فروش‌محور نگه دار'],
                'hero_description'       => ['label' => __('توضیحات', 'niloo-web'), 'type' => 'textarea', 'default' => 'پشتیبانی ماهانه وردپرس، توسعه افزونه اختصاصی و فروش افزونه‌های آماده — برای مدیران سایتی که می‌خواهند بدون دردسر فنی، فروش و پایداری داشته باشند.'],
                'hero_primary_text'      => ['label' => __('متن دکمه اصلی', 'niloo-web'), 'default' => 'درخواست پشتیبانی'],
                'hero_primary_url'       => ['label' => __('آدرس دکمه اصلی', 'niloo-web'), 'type' => 'url', 'default' => $contact],
                'hero_secondary_text'    => ['label' => __('متن دکمه دوم', 'niloo-web'), 'default' => 'مشاهده افزونه‌ها'],
                'hero_secondary_url'     => ['label' => __('آدرس دکمه دوم', 'niloo-web'), 'type' => 'url', 'default' => $products],
                'hero_image'             => ['label' => __('تصویر هیرو', 'niloo-web'), 'type' => 'image', 'default' => ''],
                'hero_proof_title'       => ['label' => __('عنوان آمار', 'niloo-web'), 'default' => '+۳۰۰ پروژه'],
                'hero_proof_text'        => ['label' => __('توضیح آمار', 'niloo-web'), 'default' => 'در مسیر فروش و توسعه وب'],
                'hero_note_top_title'    => ['label' => __('کارت بالا: عنوان', 'niloo-web'), 'default' => 'افزونه اختصاصی'],
                'hero_note_top_text'     => ['label' => __('کارت بالا: توضیح', 'niloo-web'), 'default' => 'متناسب با فرایند کسب‌وکار تو'],
                'hero_note_bottom_title' => ['label' => __('کارت پایین: عنوان', 'niloo-web'), 'default' => 'پشتیبانی ماهانه'],
                'hero_note_bottom_text'  => ['label' => __('کارت پایین: توضیح', 'niloo-web'), 'default' => 'نگهداری امن و بدون قطعی'],
                'hero_caption'           => ['label' => __('متن عمودی', 'niloo-web'), 'default' => 'WordPress · PHP · Laravel'],
            ],
        ],

        'nasim_benefits' => [
            'title'  => __('مزایا', 'niloo-web'),
            'fields' => [
                'benefit_1_title' => ['label' => __('مزیت ۱: عنوان', 'niloo-web'), 'default' => 'پشتیبانی ماهانه'],
                'benefit_1_text'  => ['label' => __('مزیت ۱: توضیح', 'niloo-web'), 'default' => 'نگهداری، بکاپ و رفع خطای وردپرس'],
                'benefit_2_title' => ['label' => __('مزیت ۲: عنوان', 'niloo-web'), 'default' => 'افزونه اختصاصی'],
                'benefit_2_text'  => ['label' => __('مزیت ۲: توضیح', 'niloo-web'), 'default' => 'پلاگین سفارشی برای فروش و اتوماسیون'],
                'benefit_3_title' => ['label' => __('مزیت ۳: عنوان', 'niloo-web'), 'default' => 'افزونه آماده'],
                'benefit_3_text'  => ['label' => __('مزیت ۳: توضیح', 'niloo-web'), 'default' => 'ابزارهای تست‌شده با لایسنس و آپدیت'],
                'benefit_4_title' => ['label' => __('مزیت ۴: عنوان', 'niloo-web'), 'default' => 'آموزش کاربردی'],
                'benefit_4_text'  => ['label' => __('مزیت ۴: توضیح', 'niloo-web'), 'default' => 'مقالات و دوره‌های سرعت و وردپرس'],
            ],
        ],

        'nasim_programs' => [
            'title'  => __('برنامه‌ها', 'niloo-web'),
            'fields' => [
                'programs_eyebrow'         => ['label' => __('تیتر کوچک', 'niloo-web'), 'default' => 'برنامه‌های منتخب'],
                'programs_title'           => ['label' => __('عنوان', 'niloo-web'), 'default' => 'برای هدف تو،'],
                'programs_title_highlight' => ['label' => __('عنوان رنگی', 'niloo-web'), 'default' => 'یک مسیر روشن'],
                'programs_description'     => ['label' => __('توضیحات', 'niloo-web'), 'type' => 'textarea', 'default' => 'قرار نیست مسیر همه یکسان باشد؛ برنامه‌ای را انتخاب کن که با زندگی و هدف تو هماهنگ است.'],

                'program_1_image'       => ['label' => __('برنامه ۱: تصویر', 'niloo-web'), 'type' => 'image', 'default' => 'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?auto=format&fit=crop&w=1000&q=85'],
                'program_1_label'       => ['label' => __('برنامه ۱: برچسب تصویر', 'niloo-web'), 'default' => 'تمرین در خانه یا باشگاه'],
                'program_1_title'       => ['label' => __('برنامه ۱: عنوان', 'niloo-web'), 'default' => 'برنامه تمرینی'],
                'program_1_description' => ['label' => __('برنامه ۱: توضیحات', 'niloo-web'), 'type' => 'textarea', 'default' => 'برنامه‌ای مرحله‌به‌مرحله برای افزایش قدرت، فرم‌دهی و ساختن عادت‌های ورزشی پایدار.'],
                'program_1_tags'        => ['label' => __('برنامه ۱: برچسب‌ها (با کاما جدا کنید)', 'niloo-web'), 'default' => 'افزایش حجم، کاهش وزن، تناسب اندام'],
                'program_1_url'         => ['label' => __('برنامه ۱: لینک جزئیات', 'niloo-web'), 'type' => 'url', 'default' => '#contact'],

                'program_2_image'       => ['label' => __('برنامه ۲: تصویر', 'niloo-web'), 'type' => 'image', 'default' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=1000&q=85'],
                'program_2_label'       => ['label' => __('برنامه ۲: برچسب تصویر', 'niloo-web'), 'default' => 'ساده، سالم، قابل اجرا'],
                'program_2_title'       => ['label' => __('برنامه ۲: عنوان', 'niloo-web'), 'default' => 'برنامه تغذیه'],
                'program_2_description' => ['label' => __('برنامه ۲: توضیحات', 'niloo-web'), 'type' => 'textarea', 'default' => 'عادت‌های غذایی مناسب خودت را بساز؛ با انتخاب‌های منعطف و قابل اجرا در زندگی روزمره.'],
                'program_2_tags'        => ['label' => __('برنامه ۲: برچسب‌ها (با کاما جدا کنید)', 'niloo-web'), 'default' => 'تغذیه سالم، اصلاح سبک زندگی'],
                'program_2_url'         => ['label' => __('برنامه ۲: لینک جزئیات', 'niloo-web'), 'type' => 'url', 'default' => '#contact'],
            ],
        ],

        'nasim_products' => [
            'title'       => __('افزونه‌ها در صفحه اصلی', 'niloo-web'),
            'description' => __('آخرین محصولات منتشرشده در صفحه اصلی نمایش داده می‌شوند.', 'niloo-web'),
            'fields'      => [
                'products_eyebrow'         => ['label' => __('تیتر کوچک', 'niloo-web'), 'default' => 'فروشگاه افزونه'],
                'products_title'           => ['label' => __('عنوان', 'niloo-web'), 'default' => 'افزونه‌های آماده برای'],
                'products_title_highlight' => ['label' => __('عنوان رنگی', 'niloo-web'), 'default' => 'رشد فروش'],
                'products_count'           => ['label' => __('تعداد محصولات', 'niloo-web'), 'type' => 'number', 'default' => 6],
            ],
        ],

        'nasim_about' => [
            'title'  => __('درباره من', 'niloo-web'),
            'fields' => [
                'about_image'           => ['label' => __('تصویر', 'niloo-web'), 'type' => 'image', 'default' => ''],
                'about_sticker'         => ['label' => __('متن برچسب روی تصویر', 'niloo-web'), 'default' => 'تهران · وردپرس · لاراول'],
                'about_eyebrow'         => ['label' => __('تیتر کوچک', 'niloo-web'), 'default' => 'درباره من'],
                'about_title'           => ['label' => __('عنوان', 'niloo-web'), 'default' => 'سلام، من'],
                'about_title_highlight' => ['label' => __('عنوان رنگی', 'niloo-web'), 'default' => 'آرش فدایی هستم'],
                'about_text'            => ['label' => __('متن', 'niloo-web'), 'type' => 'textarea', 'default' => 'فریلنسر توسعه وب در تهران با تمرکز روی پشتیبانی ماهانه وردپرس، افزونه آماده و پلاگین اختصاصی. کیفیت، پایداری و رضایت مشتری اولویت کار من است.'],
                'about_stat_1_value'    => ['label' => __('آمار ۱: مقدار', 'niloo-web'), 'default' => 'WP'],
                'about_stat_1_label'    => ['label' => __('آمار ۱: عنوان', 'niloo-web'), 'default' => 'قالب و افزونه'],
                'about_stat_2_value'    => ['label' => __('آمار ۲: مقدار', 'niloo-web'), 'default' => 'Laravel'],
                'about_stat_2_label'    => ['label' => __('آمار ۲: عنوان', 'niloo-web'), 'default' => 'Full Stack'],
                'about_stat_3_value'    => ['label' => __('آمار ۳: مقدار', 'niloo-web'), 'default' => '۹–۱۷'],
                'about_stat_3_label'    => ['label' => __('آمار ۳: عنوان', 'niloo-web'), 'default' => 'شنبه تا چهارشنبه'],
                'about_button_text'     => ['label' => __('متن دکمه دوم', 'niloo-web'), 'default' => 'صفحه درباره من'],
                'about_button_url'      => ['label' => __('آدرس دکمه دوم', 'niloo-web'), 'type' => 'url', 'default' => home_url('/about/')],
            ],
        ],

        'nasim_testimonials' => [
            'title'       => __('تجربه همراهان', 'niloo-web'),
            'description' => __('نظرات از بخش «نظرات همراهان» در پیشخوان مدیریت می‌شوند.', 'niloo-web'),
            'fields'      => [
                'testimonials_eyebrow'         => ['label' => __('تیتر کوچک', 'niloo-web'), 'default' => 'نظر مشتریان'],
                'testimonials_title'           => ['label' => __('عنوان', 'niloo-web'), 'default' => 'اعتماد کسب‌وکارها به'],
                'testimonials_title_highlight' => ['label' => __('عنوان رنگی', 'niloo-web'), 'default' => 'پشتیبانی واقعی'],
                'testimonials_count'           => ['label' => __('تعداد نظرات', 'niloo-web'), 'type' => 'number', 'default' => 6],
            ],
        ],

        'nasim_articles' => [
            'title'       => __('مقالات', 'niloo-web'),
            'description' => __('آخرین نوشته‌های سایت در این بخش نمایش داده می‌شوند.', 'niloo-web'),
            'fields'      => [
                'articles_eyebrow'         => ['label' => __('تیتر کوچک', 'niloo-web'), 'default' => 'آموزش وردپرس'],
                'articles_title'           => ['label' => __('عنوان', 'niloo-web'), 'default' => 'راهنماهایی برای'],
                'articles_title_highlight' => ['label' => __('عنوان رنگی', 'niloo-web'), 'default' => 'مدیران سایت'],
                'articles_count'           => ['label' => __('تعداد مقالات', 'niloo-web'), 'type' => 'number', 'default' => 3],
            ],
        ],

        'nasim_cta' => [
            'title'  => __('دعوت به اقدام (CTA)', 'niloo-web'),
            'fields' => [
                'cta_eyebrow'     => ['label' => __('تیتر کوچک', 'niloo-web'), 'default' => 'شروع همکاری'],
                'cta_title'       => ['label' => __('عنوان', 'niloo-web'), 'default' => 'برای پشتیبانی یا افزونه اختصاصی آماده‌ای؟'],
                'cta_text'        => ['label' => __('متن', 'niloo-web'), 'default' => 'در تلگرام @fadaeedevs پیام بده؛ درباره محدوده کار و زمان‌بندی شفاف توافق می‌کنیم.'],
                'cta_button_text' => ['label' => __('متن دکمه', 'niloo-web'), 'default' => 'پیام در تلگرام'],
                'cta_button_url'  => ['label' => __('آدرس دکمه', 'niloo-web'), 'type' => 'url', 'default' => 'https://t.me/fadaeedevs'],
            ],
        ],

        'nasim_footer' => [
            'title'  => __('فوتر', 'niloo-web'),
            'fields' => [
                'footer_tagline'   => ['label' => __('شعار', 'niloo-web'), 'default' => 'پشتیبانی وردپرس، افزونه اختصاصی و رشد فروش آنلاین'],
                'footer_copyright' => ['label' => __('متن کپی‌رایت', 'niloo-web'), 'default' => '© تمام حقوق برای آرش فدایی محفوظ است.'],
                'social_instagram' => ['label' => __('لینک اینستاگرام', 'niloo-web'), 'type' => 'url', 'default' => ''],
                'social_telegram'  => ['label' => __('لینک تلگرام', 'niloo-web'), 'type' => 'url', 'default' => 'https://t.me/fadaeedevs'],
            ],
        ],
    ];

    return apply_filters('nasim_customizer_sections', $sections);
}


/**
 * Sanitize callback per field type
 */
function nasim_customizer_sanitize_callback($type) {

    $callbacks = [
        'text'     => 'sanitize_text_field',
        'textarea' => 'sanitize_textarea_field',
        'url'      => 'esc_url_raw',
        'image'    => 'esc_url_raw',
        'number'   => 'absint',
        'select'   => 'sanitize_text_field',
    ];

    return $callbacks[$type] ?? 'sanitize_text_field';
}


/**
 * Sanitize content_source select to allowed values only.
 */
function nasim_sanitize_content_source($value) {

    return in_array($value, ['theme', 'custom'], true) ? $value : 'theme';
}


/**
 * Register panel, sections, settings and controls
 */
function nasim_customize_register($wp_customize) {

    $wp_customize->add_panel('niloo_front_page', [
        'title'    => __('تنظیمات قالب نیلو', 'niloo-web'),
        'priority' => 30,
    ]);

    foreach (nasim_customizer_sections() as $section_id => $section) {

        $wp_customize->add_section($section_id, [
            'title'       => $section['title'],
            'description' => $section['description'] ?? '',
            'panel'       => 'niloo_front_page',
        ]);

        foreach ($section['fields'] as $field_id => $field) {

            $type = $field['type'] ?? 'text';

            $sanitize = ('content_source' === $field_id)
                ? 'nasim_sanitize_content_source'
                : nasim_customizer_sanitize_callback($type);

            $wp_customize->add_setting($field_id, [
                'default'           => $field['default'],
                'sanitize_callback' => $sanitize,
            ]);

            if ('image' === $type) {
                $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, $field_id, [
                    'label'   => $field['label'],
                    'section' => $section_id,
                ]));
                continue;
            }

            // Plain text input so in-page anchors like "#programs" pass browser validation.
            $control_type = 'url' === $type ? 'text' : $type;

            $args = [
                'label'       => $field['label'],
                'section'     => $section_id,
                'type'        => $control_type,
                'input_attrs' => 'number' === $type ? ['min' => 1, 'max' => 12] : [],
            ];

            if ('select' === $type && !empty($field['choices'])) {
                $args['choices'] = $field['choices'];
            }

            $wp_customize->add_control($field_id, $args);
        }
    }
}
add_action('customize_register', 'nasim_customize_register');
