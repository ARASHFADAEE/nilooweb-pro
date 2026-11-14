<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Seed core academy pages from live Niloo copy (connect / request / legal).
 */

define('NASIM_NILOO_PAGES_SEED_VERSION', '1');

/**
 * @return array<string, array{0:string,1:callable}>
 */
function nasim_niloo_pages_definitions(): array
{
    return [
        'connect' => ['معرفی نیلوفر وفایی', 'nasim_niloo_page_blocks_connect'],
        'about'   => ['درباره من', 'nasim_niloo_page_blocks_connect'],
        'request' => ['مشاوره و رزرو', 'nasim_niloo_page_blocks_request'],
        'contact' => ['تماس با ما', 'nasim_niloo_page_blocks_contact'],
        'faq'     => ['سوالات متداول', 'nasim_niloo_page_blocks_faq'],
        'terms'   => ['قوانین و شرایط استفاده', 'nasim_niloo_page_blocks_terms'],
        'privacy' => ['حریم خصوصی', 'nasim_niloo_page_blocks_privacy'],
        'courses' => ['دوره‌های آموزشی', 'nasim_niloo_page_blocks_courses'],
    ];
}

/**
 * @return string[]
 */
function nasim_niloo_page_blocks_connect(): array
{
    return [
        nasim_block_paragraph('<strong>سلام، من نیلوفر وفایی، طراح و توسعه‌دهنده وب هستم.</strong>'),
        nasim_block_paragraph('مدرس و توسعه‌دهنده وب، بنیان‌گذار وبلبز و آکادمی اولترا. برای ساخت وب‌سایت‌های حرفه‌ای، توسعه کسب‌وکار و یادگیری طراحی سایت کنار شما هستم.'),
        nasim_block_heading('فراتر از کدنویسی، ساختن مسیر رشد'),
        nasim_block_paragraph('تجربه‌ام در آموزش، طراحی و توسعه وب و اجرای پروژه‌های واقعی، به من کمک کرده تا ایده‌ها را به راهکارهای دیجیتال کاربردی تبدیل کنم؛ راهکارهایی که متناسب با نیاز هر کسب‌وکار طراحی می‌شوند و مسیر رشد آن را هموارتر می‌کنند.'),
        nasim_block_heading('چطور می‌توانیم همکاری کنیم؟'),
        nasim_block_paragraph('دو مسیر متفاوت، یک هدف مشترک: رشد.'),
        nasim_block_heading('توسعه کسب‌وکار شما (وبلبز)', 3),
        nasim_block_paragraph('طراحی و توسعه سایت‌های شرکتی و فروشگاهی، سامانه‌های اختصاصی، سئو و مشاوره دیجیتال.'),
        nasim_block_heading('یادگیری تا درآمدزایی (آکادمی اولترا)', 3),
        nasim_block_paragraph('آموزش طراحی سایت، ساخت نمونه‌کار و یادگیری مسیر گرفتن پروژه با متد بازارمحور U3P؛ برای تبدیل مهارت به فرصت‌های واقعی درآمدزایی.'),
        nasim_block_heading('مسیر ULTRA'),
        nasim_block_paragraph('ULTRA یک مسیر مهارت‌محور برای یادگیری عملی طراحی و توسعه وب، تقویت مهارت‌های حرفه‌ای و آماده‌شدن برای ورود به بازار کار است؛ از ساخت وب‌سایت و تمرین روی پروژه‌های واقعی تا یادگیری نحوه جذب پروژه.'),
        nasim_block_list([
            '<strong>مهارت ساخت سایت:</strong> یادگیری و تمرین طراحی و توسعه وب',
            '<strong>حرفه‌ای‌شدن:</strong> تقویت مهارت‌ها و آماده‌سازی برای بازار کار',
            '<strong>جذب پروژه:</strong> یادگیری مسیر پیدا کردن و گرفتن پروژه',
        ]),
        nasim_block_paragraph(
            'برای سفارش پروژه، آموزش یا همکاری: ' .
            nasim_block_link(home_url('/request/'), 'رزرو مشاوره رایگان') .
            ' یا از صفحه دوره‌ها شروع کن.'
        ),
    ];
}

/**
 * @return string[]
 */
function nasim_niloo_page_blocks_request(): array
{
    return [
        nasim_block_heading('رزرو مشاوره'),
        nasim_block_paragraph('اگر دوست داری طراحی سایت را یاد بگیری و وارد بازار کارش شوی، یا برای سفارش سایت نیاز به راهنمایی داری، اطلاعاتت را از مسیر ارتباط با ما بفرست تا از مشاوره رایگان استفاده کنی.'),
        nasim_block_list([
            'مشاوره مسیر یادگیری و انتخاب دوره مناسب',
            'بررسی نیاز پروژه و محدوده کار',
            'راهنمایی برای ورود به بازار کار طراحی سایت',
        ]),
        nasim_block_paragraph(
            'همین حالا از طریق ' .
            nasim_block_link(home_url('/contact/'), 'صفحه تماس') .
            ' پیام بده، یا در اینستاگرام ' .
            nasim_block_link('https://www.instagram.com/niloofarvafaei', '@niloofarvafaei') .
            ' در ارتباط باش.'
        ),
        nasim_block_paragraph(
            'عضویت در کانال بله برای به‌روزرسانی‌ها: ' .
            nasim_block_link('https://ble.ir/niloofarvafaei', 'ble.ir/niloofarvafaei')
        ),
    ];
}

/**
 * @return string[]
 */
function nasim_niloo_page_blocks_contact(): array
{
    return [
        nasim_block_paragraph('برای سفارش پروژه، آموزش، پشتیبانی دوره یا همکاری در ارتباط باشیم.'),
        nasim_block_heading('راه‌های ارتباط'),
        nasim_block_list([
            'اینستاگرام: ' . nasim_block_link('https://www.instagram.com/niloofarvafaei', '@niloofarvafaei'),
            'کانال بله: ' . nasim_block_link('https://ble.ir/niloofarvafaei', 'ble.ir/niloofarvafaei'),
            'صفحه معرفی: ' . nasim_block_link(home_url('/connect/'), 'معرفی نیلوفر وفایی'),
            'مشاوره: ' . nasim_block_link(home_url('/request/'), 'رزرو مشاوره رایگان'),
        ]),
        nasim_block_heading('قبل از پیام'),
        nasim_block_paragraph('اگر برای یادگیری می‌آیی، سطح فعلی و هدفت از طراحی سایت را بنویس. اگر برای سفارش سایت می‌آیی، نوع کسب‌وکار و نمونه‌هایی که دوست داری را کوتاه توضیح بده.'),
    ];
}

/**
 * @return string[]
 */
function nasim_niloo_page_blocks_faq(): array
{
    return [
        nasim_block_heading('دوره‌ها چطور ارائه می‌شوند؟', 3),
        nasim_block_paragraph('بخشی از دوره‌ها رایگان یا داخل اشتراک‌اند؛ دوره‌های پرچمدار معمولاً خرید جداگانه دارند و محتوای ضدکپی آن‌ها از طریق اسپات‌پلیر در اختیار دانشجو قرار می‌گیرد.'),
        nasim_block_heading('با خرید اشتراک به همه دوره‌ها دسترسی دارم؟', 3),
        nasim_block_paragraph('خیر. اشتراک فقط دوره‌های علامت‌خورده «رایگان با اشتراک» را باز می‌کند. دوره‌های «خرید جداگانه» باید جدا تهیه شوند.'),
        nasim_block_heading('قبل از خرید می‌توانم بخشی از دوره را ببینم؟', 3),
        nasim_block_paragraph('در بسیاری از دوره‌ها درس‌های پیش‌نمایش رایگان وجود دارد. جزئیات هر دوره را در صفحه همان دوره ببین.'),
        nasim_block_heading('چطور دوره مناسب خودم را انتخاب کنم؟', 3),
        nasim_block_paragraph(
            'اگر تازه‌کار هستی از مسیر پایه (مثل کپسول اولترا / اولترا وب) شروع کن. برای انتخاب دقیق‌تر از ' .
            nasim_block_link(home_url('/request/'), 'مشاوره رایگان') .
            ' استفاده کن.'
        ),
        nasim_block_heading('پشتیبانی دوره‌ها چگونه است؟', 3),
        nasim_block_paragraph('پس از ثبت‌نام، مسیر پشتیبانی هر دوره (گروه / تیکت / کانال اختصاصی) طبق توضیحات همان دوره در اختیارت قرار می‌گیرد.'),
    ];
}

/**
 * @return string[]
 */
function nasim_niloo_page_blocks_terms(): array
{
    return [
        nasim_block_paragraph('استفاده از وب‌سایت آکادمی نیلوفر وفایی و خرید دوره یا اشتراک به معنای پذیرش این قوانین است.'),
        nasim_block_heading('حساب کاربری'),
        nasim_block_list([
            'ثبت‌نام با اطلاعات معتبر انجام می‌شود و هر حساب شخصی است.',
            'در اختیار گذاشتن حساب به دیگران مجاز نیست و ممکن است دسترسی محدود شود.',
            'حفظ اطلاعات ورود بر عهده کاربر است.',
        ]),
        nasim_block_heading('خرید دوره و اشتراک'),
        nasim_block_list([
            'قیمت‌ها به تومان است و پرداخت از درگاه امن بانکی انجام می‌شود.',
            'پس از تأیید پرداخت، دسترسی مطابق نوع محصول (دوره جداگانه یا اشتراک) فعال می‌شود.',
            'دوره‌های اسپات‌پلیر لایسنس اختصاصی دارند و قوانین همان سرویس نیز اعمال می‌شود.',
            'اشتراک به‌صورت خودکار تمدید نمی‌شود مگر خلاف آن اعلام شود.',
        ]),
        nasim_block_heading('مالکیت محتوا'),
        nasim_block_paragraph('ویدیوها، متن‌ها، فایل‌ها و محتوای آموزشی متعلق به آکادمی نیلوفر وفایی است. ضبط، انتشار مجدد یا فروش بدون اجازه کتبی ممنوع است.'),
        nasim_block_heading('تغییر قوانین'),
        nasim_block_paragraph('این صفحه ممکن است به‌روزرسانی شود و نسخه منتشرشده از زمان انتشار معتبر است.'),
    ];
}

/**
 * @return string[]
 */
function nasim_niloo_page_blocks_privacy(): array
{
    return [
        nasim_block_paragraph('حفظ اطلاعات شخصی کاربران برای آکادمی نیلوفر وفایی مهم است.'),
        nasim_block_heading('چه اطلاعاتی جمع می‌شود؟'),
        nasim_block_list([
            'نام و اطلاعات تماس هنگام ثبت‌نام یا درخواست مشاوره',
            'اطلاعات پیشرفت دوره و سفارش‌ها در صورت خرید',
            'اطلاعات فنی لازم برای امنیت و عملکرد سایت (مثل لاگ ورود)',
        ]),
        nasim_block_heading('استفاده از اطلاعات'),
        nasim_block_list([
            'ارائه دسترسی به دوره و پشتیبانی',
            'ارسال کد تأیید یا پیام‌های ضروری سرویس',
            'بهبود تجربه آموزشی و پاسخ به درخواست‌ها',
        ]),
        nasim_block_heading('اشتراک‌گذاری'),
        nasim_block_paragraph('اطلاعات فروخته نمی‌شود. در صورت نیاز برای پرداخت، پیامک یا اسپات‌پلیر، حداقل داده لازم در اختیار سرویس‌دهنده مربوط قرار می‌گیرد.'),
        nasim_block_heading('حقوق شما'),
        nasim_block_paragraph(
            'برای ویرایش یا حذف اطلاعات حساب، از طریق ' .
            nasim_block_link(home_url('/contact/'), 'تماس با ما') .
            ' درخواست بده.'
        ),
    ];
}

/**
 * @return string[]
 */
function nasim_niloo_page_blocks_courses(): array
{
    $archive = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/');
    $courses = get_posts([
        'post_type'      => 'nasim_course',
        'post_status'    => 'publish',
        'posts_per_page' => 12,
        'orderby'        => ['menu_order' => 'ASC', 'date' => 'DESC'],
    ]);

    $blocks = [
        nasim_block_paragraph('مسیر یادگیری خود را از اینجا شروع کن. بعضی دوره‌ها رایگان یا داخل اشتراک‌اند؛ دوره‌های پرچمدار خرید جداگانه دارند.'),
    ];

    if ($courses) {
        $items = [];
        foreach ($courses as $course) {
            $label = function_exists('nasim_course_access_label') ? nasim_course_access_label($course->ID) : '';
            $items[] = nasim_block_link(get_permalink($course), get_the_title($course)) .
                ($label ? ' — ' . esc_html($label) : '');
        }
        $blocks[] = nasim_block_heading('دوره‌های موجود');
        $blocks[] = nasim_block_list($items);
    } else {
        $blocks[] = nasim_block_paragraph('به‌زودی فهرست دوره‌ها اینجا تکمیل می‌شود.');
    }

    $blocks[] = nasim_block_paragraph('برای انتخاب مسیر مناسب: ' . nasim_block_link(home_url('/request/'), 'مشاوره رایگان'));

    return $blocks;
}

/**
 * Create / refresh Niloo academy pages.
 *
 * @return array{created:int,updated:int,total:int}
 */
function nasim_niloo_install_pages_seed(bool $force = false): array
{
    if (!$force && get_option('nasim_niloo_pages_seed_version') === NASIM_NILOO_PAGES_SEED_VERSION) {
        return ['created' => 0, 'updated' => 0, 'total' => 0];
    }

    $ids     = (array) get_option('nasim_default_pages', []);
    $created = 0;
    $updated = 0;

    foreach (nasim_niloo_pages_definitions() as $slug => [$title, $builder]) {
        if (!is_callable($builder)) {
            continue;
        }

        $existing = get_page_by_path($slug);
        $content  = implode("\n\n", $builder());

        if ($existing) {
            // Only overwrite if empty/thin or previously seeded by us.
            $current = trim(wp_strip_all_tags((string) $existing->post_content));
            $owned   = (string) get_post_meta($existing->ID, '_nasim_niloo_page_seed', true) === '1';
            if ($owned || strlen($current) < 40) {
                wp_update_post([
                    'ID'           => $existing->ID,
                    'post_title'   => $title,
                    'post_content' => $content,
                    'post_status'  => 'publish',
                ]);
                update_post_meta($existing->ID, '_nasim_niloo_page_seed', '1');
                $updated++;
            }
            $ids[$slug] = (int) $existing->ID;
            if ('connect' === $slug) {
                $ids['about'] = (int) $existing->ID;
            }
            if ('request' === $slug) {
                $ids['contact'] = $ids['contact'] ?? (int) $existing->ID;
            }
            continue;
        }

        $page_id = wp_insert_post([
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'post_title'     => $title,
            'post_name'      => $slug,
            'post_content'   => $content,
            'comment_status' => 'closed',
        ], true);

        if ($page_id && !is_wp_error($page_id)) {
            update_post_meta($page_id, '_nasim_niloo_page_seed', '1');
            $ids[$slug] = (int) $page_id;
            if ('connect' === $slug) {
                $ids['about'] = (int) $page_id;
            }
            $created++;
        }
    }

    update_option('nasim_default_pages', $ids, false);

    if (!empty($ids['privacy'])) {
        update_option('wp_page_for_privacy_policy', (int) $ids['privacy']);
    }

    update_option('nasim_niloo_pages_seed_version', NASIM_NILOO_PAGES_SEED_VERSION, false);

    return [
        'created' => $created,
        'updated' => $updated,
        'total'   => count(nasim_niloo_pages_definitions()),
    ];
}

function nasim_niloo_maybe_seed_pages(): void
{
    if (function_exists('nasim_store_enabled') && nasim_store_enabled()) {
        return;
    }
    if (get_option('nasim_niloo_pages_seed_version') === NASIM_NILOO_PAGES_SEED_VERSION) {
        return;
    }
    nasim_niloo_install_pages_seed(false);
}
add_action('after_switch_theme', static function () {
    nasim_niloo_install_pages_seed(true);
}, 36);
add_action('init', 'nasim_niloo_maybe_seed_pages', 46);

function nasim_niloo_pages_seed_admin_menu(): void
{
    add_submenu_page(
        'edit.php?post_type=page',
        'سید برگه‌های نیلو',
        'سید برگه‌های نیلو',
        'manage_options',
        'nasim-niloo-pages-seed',
        static function () {
            if (!current_user_can('manage_options')) {
                return;
            }
            $result = null;
            if (isset($_POST['nasim_niloo_pages_seed']) && check_admin_referer('nasim_niloo_pages_seed')) {
                delete_option('nasim_niloo_pages_seed_version');
                $result = nasim_niloo_install_pages_seed(true);
            }
            echo '<div class="wrap"><h1>سید برگه‌های آکادمی نیلو</h1>';
            if ($result) {
                echo '<div class="notice notice-success"><p>' . esc_html(sprintf('%d جدید، %d به‌روز.', $result['created'], $result['updated'])) . '</p></div>';
            }
            echo '<p>برگه‌ها: معرفی (connect)، مشاوره (request)، تماس، FAQ، قوانین، حریم خصوصی، دوره‌ها.</p>';
            echo '<form method="post">';
            wp_nonce_field('nasim_niloo_pages_seed');
            echo '<p class="submit"><button class="button button-primary" name="nasim_niloo_pages_seed" value="1">اجرای مجدد</button></p></form></div>';
        }
    );
}
add_action('admin_menu', 'nasim_niloo_pages_seed_admin_menu');
