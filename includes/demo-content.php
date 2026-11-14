<?php

if(!defined('ABSPATH')) exit;



/**
 * Sample homepage testimonials: [name, role, quote, rating]
 */
function nasim_demo_testimonials() {

    return [
        ['مریم', 'همراه برنامه تمرینی', 'برنامه‌ام با شرایط زندگی من هماهنگ بود و همین باعث شد بتوانم ادامه بدهم.', 5],
        ['سارا', 'همراه برنامه تغذیه', 'تغییرهای کوچک و قابل اجرا کمک کرد کم‌کم عادت‌های بهتری بسازم.', 5],
        ['نگار', 'همراه برنامه تمرینی', 'توضیحات روشن بود و می‌دانستم هر تمرین را با چه هدفی انجام می‌دهم.', 5],
        ['الهام', 'همراه دوره تمرین در خانه', 'بدون باشگاه و وسیله خاص، سه ماه است که هفته‌ای چهار روز تمرین می‌کنم و انرژی‌ام خیلی بیشتر شده.', 5],
        ['زهرا', 'همراه برنامه تغذیه', 'دیگر رژیم سخت نمی‌گیرم؛ یاد گرفتم با همان غذاهای خانگی، بشقاب متعادل‌تری داشته باشم.', 4],
        ['فاطمه', 'همراه اشتراک ماهانه', 'ویدیوها کوتاه و دقیق‌اند و هر وقت سؤالی داشتم، جوابش را سریع گرفتم.', 5],
    ];
}


/**
 * Create the sample testimonials once, on first activation of a site that has none
 */
function nasim_install_demo_testimonials() {

    if (get_option('nasim_demo_testimonials_installed')) {
        return;
    }

    update_option('nasim_demo_testimonials_installed', 1, false);

    $existing = get_posts([
        'post_type'      => 'nasim_testimonial',
        'post_status'    => ['publish', 'draft', 'pending', 'private', 'future', 'trash'],
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);

    if ($existing) {
        return;
    }

    foreach (nasim_demo_testimonials() as $order => [$name, $role, $quote, $rating]) {
        wp_insert_post([
            'post_type'    => 'nasim_testimonial',
            'post_status'  => 'publish',
            'post_title'   => $name,
            'post_content' => $quote,
            'menu_order'   => $order + 1,
            'meta_input'   => [
                '_nasim_testimonial_role'   => $role,
                '_nasim_testimonial_rating' => $rating,
            ],
        ]);
    }
}
add_action('after_switch_theme', 'nasim_install_demo_testimonials');


/**
 * Default subscription plans: [title, days, price in toman, features, featured]
 */
function nasim_default_plans() {

    $base = [
        'دسترسی کامل به همه دوره‌ها و جلسات',
        'تماشا روی موبایل، تبلت و کامپیوتر',
        'دوره‌ها و جلسات جدید بدون هزینه اضافه',
    ];

    return [
        ['شروع مسیر', 30, 290000, $base, false],
        ['ساختن عادت', 90, 790000, array_merge($base, ['زمان کافی برای ساختن یک عادت پایدار']), true],
        ['تغییر ماندگار', 180, 1490000, array_merge($base, ['کمترین هزینه ماهانه']), false],
    ];
}


/**
 * Create the default plans once, on first activation of a site that has none
 */
function nasim_install_default_plans() {

    if (get_option('nasim_default_plans_installed')) {
        return;
    }

    update_option('nasim_default_plans_installed', 1, false);

    $existing = get_posts([
        'post_type'      => 'nasim_plan',
        'post_status'    => ['publish', 'draft', 'pending', 'private', 'future', 'trash'],
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ]);

    if ($existing) {
        return;
    }

    foreach (nasim_default_plans() as $order => [$title, $days, $price, $features, $featured]) {
        wp_insert_post([
            'post_type'   => 'nasim_plan',
            'post_status' => 'publish',
            'post_title'  => $title,
            'menu_order'  => $order + 1,
            'meta_input'  => [
                '_nasim_plan_price'    => $price,
                '_nasim_plan_days'     => $days,
                '_nasim_plan_features' => implode("\n", $features),
                '_nasim_plan_featured' => $featured ? 1 : 0,
            ],
        ]);
    }
}
add_action('after_switch_theme', 'nasim_install_default_plans');
