<?php

if(!defined('ABSPATH')) exit;



/**
 * ID of a default page (about, contact, terms, privacy, faq) when it is published
 */
function nasim_default_page_id($key) {

    $ids = (array) get_option('nasim_default_pages', []);
    $id  = (int) ($ids[$key] ?? 0);

    return ($id && 'publish' === get_post_status($id)) ? $id : 0;
}


function nasim_default_page_url($key, $fallback = '') {

    $id = nasim_default_page_id($key);

    return $id ? get_permalink($id) : $fallback;
}


/**
 * Block editor markup helpers, so the pages stay editable block by block
 */
function nasim_block_paragraph($html) {

    return "<!-- wp:paragraph -->\n<p>{$html}</p>\n<!-- /wp:paragraph -->";
}


function nasim_block_heading($text, $level = 2) {

    $attrs = 2 === $level ? '' : ' {"level":' . (int) $level . '}';

    return "<!-- wp:heading{$attrs} -->\n<h{$level} class=\"wp-block-heading\">{$text}</h{$level}>\n<!-- /wp:heading -->";
}


function nasim_block_list(array $items) {

    $html = '';

    foreach ($items as $item) {
        $html .= "<!-- wp:list-item -->\n<li>{$item}</li>\n<!-- /wp:list-item -->\n";
    }

    return "<!-- wp:list -->\n<ul class=\"wp-block-list\">\n{$html}</ul>\n<!-- /wp:list -->";
}


function nasim_block_link($url, $text) {

    return '<a href="' . esc_url($url) . '">' . esc_html($text) . '</a>';
}


/**
 * Default pages: slug => [title, content blocks]; content is built lazily so it can link to pages created before it
 */
function nasim_default_pages() {

    return [
        'about' => ['درباره من', function () {
            return function_exists('nasim_profile_about_page_blocks')
                ? nasim_profile_about_page_blocks()
                : [nasim_block_paragraph('سلام، من نیلوفر وفایی هستم؛ مدرس طراحی سایت.')];
        }],

        'faq' => ['سوالات متداول', function () {
            return [
                nasim_block_heading('اشتراک چطور کار می‌کند؟', 3),
                nasim_block_paragraph('با خرید یک اشتراک، در طول مدت آن به همه دوره‌ها و درس‌های سایت دسترسی داری. ' . nasim_block_link(nasim_subscription_url(), 'پلن‌های اشتراک را اینجا ببین') . '.'),
                nasim_block_heading('آیا اشتراک خودکار تمدید می‌شود؟', 3),
                nasim_block_paragraph('خیر. مبلغی خودکار از حسابت کسر نمی‌شود و هر وقت بخواهی خودت تمدید می‌کنی. اگر پیش از پایان اشتراک تمدید کنی، روزهای جدید به انتهای اشتراک فعلی اضافه می‌شود.'),
                nasim_block_heading('قبل از خرید می‌توانم دوره‌ها را ببینم؟', 3),
                nasim_block_paragraph('بله. درس‌هایی که برچسب «رایگان» دارند برای همه باز هستند تا با سبک آموزش آشنا شوی.'),
                nasim_block_heading('روی چه دستگاه‌هایی می‌توانم دوره‌ها را ببینم؟', 3),
                nasim_block_paragraph('روی موبایل، تبلت و کامپیوتر؛ فقط کافی است با حساب خودت در مرورگر وارد شوی.'),
                nasim_block_heading('مبتدی هستم؛ برنامه‌ها برای من مناسب است؟', 3),
                nasim_block_paragraph('بله. برنامه‌ها از سطح مقدماتی شروع می‌شوند و کم‌کم پیش می‌روند. اگر بیماری، آسیب‌دیدگی یا بارداری داری، پیش از شروع با پزشک مشورت کن.'),
                nasim_block_heading('پرداخت انجام شد ولی اشتراک فعال نشد؛ چه کنم؟', 3),
                nasim_block_paragraph('اگر پرداخت تأیید نشده باشد، مبلغ کسرشده حداکثر ظرف ۷۲ ساعت توسط بانک به حسابت برمی‌گردد. در غیر این صورت با شماره پیگیری تراکنش با پشتیبانی تماس بگیر.'),
            ];
        }],

        'contact' => ['ارتباط با من', function () {
            return function_exists('nasim_profile_contact_page_blocks')
                ? nasim_profile_contact_page_blocks()
                : [nasim_block_paragraph('از صفحه معرفی یا فرم مشاوره پیام بده.')];
        }],

        'terms' => ['قوانین و مقررات', function () {
            return [
                nasim_block_paragraph('استفاده از سایت آکادمی نیلو و خرید اشتراک یا دوره به معنای پذیرش قوانین زیر است.'),
                nasim_block_heading('حساب کاربری'),
                nasim_block_list([
                    'ثبت‌نام با شماره موبایل معتبر و متعلق به خود کاربر انجام می‌شود.',
                    'هر حساب شخصی است و در اختیار گذاشتن آن به دیگران مجاز نیست؛ در صورت مشاهده، دسترسی حساب محدود می‌شود.',
                    'حفظ رمز عبور و کدهای ورود بر عهده کاربر است.',
                ]),
                nasim_block_heading('اشتراک و پرداخت'),
                nasim_block_list([
                    'قیمت‌ها به تومان است و پرداخت از طریق درگاه امن بانکی انجام می‌شود.',
                    'اشتراک بلافاصله پس از تأیید پرداخت فعال می‌شود و در طول مدت آن، همه دوره‌ها در دسترس است.',
                    'اشتراک به‌صورت خودکار تمدید نمی‌شود؛ تمدید پیش از پایان اشتراک، به انتهای آن اضافه می‌شود.',
                    'اگر مبلغی کسر شد ولی پرداخت تأیید نشد، مبلغ حداکثر ظرف ۷۲ ساعت توسط بانک برگشت داده می‌شود.',
                ]),
                nasim_block_heading('مالکیت محتوا'),
                nasim_block_paragraph('همه ویدیوها، متن‌ها و محتوای آموزشی سایت متعلق به آکادمی نیلو است. ضبط، دانلود، فروش یا انتشار دوباره آن‌ها بدون اجازه کتبی ممنوع است.'),
                nasim_block_heading('سلامت و مسئولیت'),
                nasim_block_paragraph('محتوای سایت آموزشی است و جایگزین مشاوره پزشکی نیست. در صورت بیماری، آسیب‌دیدگی، بارداری یا هر شرایط خاص، پیش از شروع تمرین با پزشک مشورت کنید. اجرای تمرین‌ها با رعایت توان بدنی، بر عهده خود کاربر است.'),
                nasim_block_heading('تغییر قوانین'),
                nasim_block_paragraph('این قوانین ممکن است به‌روزرسانی شوند و نسخه جدید از زمان انتشار در همین صفحه معتبر است.'),
            ];
        }],

        'privacy' => ['حریم خصوصی', function () {
            return [
                nasim_block_paragraph('حفظ اطلاعات شخصی تو برای ما مهم است. در این صفحه توضیح داده‌ایم چه اطلاعاتی جمع می‌شود و چطور از آن استفاده می‌کنیم.'),
                nasim_block_heading('چه اطلاعاتی جمع می‌کنیم؟'),
                nasim_block_list([
                    'نام، نام کاربری و شماره موبایل هنگام ثبت‌نام',
                    'پیشرفت دوره‌ها، درس‌های ذخیره‌شده و یادداشت‌هایت',
                    'اطلاعات تراکنش‌ها مانند مبلغ و شماره پیگیری (اطلاعات کارت بانکی فقط در درگاه بانک وارد می‌شود و به دست ما نمی‌رسد)',
                ]),
                nasim_block_heading('از این اطلاعات چه استفاده‌ای می‌شود؟'),
                nasim_block_list([
                    'ورود به حساب و ارسال کد تأیید پیامکی',
                    'فعال‌سازی و مدیریت اشتراک',
                    'نمایش پیشرفت و پاسخ به درخواست‌های پشتیبانی',
                ]),
                nasim_block_heading('اشتراک‌گذاری اطلاعات'),
                nasim_block_paragraph('اطلاعات تو فروخته نمی‌شود. شماره موبایل فقط برای ارسال کد تأیید در اختیار سرویس پیامک و اطلاعات پرداخت فقط در اختیار درگاه بانکی قرار می‌گیرد، مگر به حکم مراجع قانونی.'),
                nasim_block_heading('امنیت و کوکی‌ها'),
                nasim_block_paragraph('رمز عبور به‌صورت رمزنگاری‌شده نگهداری می‌شود. سایت برای نگه‌داشتن وضعیت ورود از کوکی استفاده می‌کند.'),
                nasim_block_heading('حقوق تو'),
                nasim_block_paragraph('برای ویرایش اطلاعات یا حذف حساب کاربری، از صفحه ' . nasim_block_link(nasim_default_page_url('contact', home_url('/#contact')), 'ارتباط با من') . ' درخواست بده.'),
            ];
        }],
    ];
}


/**
 * Create the default pages once on theme activation; a page whose slug is already taken is kept as is
 */
function nasim_install_default_pages() {

    if (!nasim_should_install_seed_content()) {
        return;
    }

    if (get_option('nasim_default_pages_installed')) {
        return;
    }

    update_option('nasim_default_pages_installed', 1, false);

    $ids = (array) get_option('nasim_default_pages', []);

    foreach (nasim_default_pages() as $slug => [$title, $content]) {

        $existing = get_page_by_path($slug);

        if ($existing) {
            $ids[$slug] = $existing->ID;
            update_option('nasim_default_pages', $ids, false);
            continue;
        }

        $page_id = wp_insert_post([
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'post_title'     => $title,
            'post_name'      => $slug,
            'post_content'   => implode("\n\n", $content()),
            'comment_status' => 'closed',
        ]);

        if ($page_id && !is_wp_error($page_id)) {
            $ids[$slug] = $page_id;
            update_option('nasim_default_pages', $ids, false);
        }
    }

    $privacy_page = (int) get_option('wp_page_for_privacy_policy');

    if (!empty($ids['privacy']) && (!$privacy_page || 'publish' !== get_post_status($privacy_page))) {
        update_option('wp_page_for_privacy_policy', (int) $ids['privacy']);
    }
}
add_action('after_switch_theme', 'nasim_install_default_pages');


/**
 * Safety net: if the About page (/about/ or /connect/) was never created or was deleted,
 * still serve the designed About template instead of a 404.
 */
function nasim_about_page_fallback() {

    if (!is_404()) {
        return;
    }

    $home_path = trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');
    $req_path  = trim((string) wp_parse_url(isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '', PHP_URL_PATH), '/');

    if ($home_path && 0 === strpos($req_path, $home_path)) {
        $req_path = trim(substr($req_path, strlen($home_path)), '/');
    }

    if (!in_array(urldecode($req_path), ['about', 'connect'], true)) {
        return;
    }

    global $wp_query;
    $wp_query->is_404 = false;
    status_header(200);

    include THEME_DIR . '/page-about.php';
    exit;
}
add_action('template_redirect', 'nasim_about_page_fallback', 20);
