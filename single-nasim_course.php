<?php

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) :
    the_post();

    $course_id   = get_the_ID();
    $course_slug = get_post_field('post_name', $course_id);
    $lessons     = nasim_get_course_lessons($course_id);
    $curriculum  = nasim_get_curriculum($course_id);
    $visible_curriculum = array_values(array_filter($curriculum, static function ($sec) {
        return !empty($sec['lessons']) && is_array($sec['lessons']);
    }));

    $total_secs  = nasim_course_total_seconds($course_id);
    $dur_label   = trim((string) get_post_meta($course_id, '_nasim_course_duration_label', true))
        ?: ($total_secs ? nasim_format_total_duration($total_secs) : (count($lessons) ? nasim_fa_digits(count($lessons)) . ' جلسه' : 'پروژه‌محور'));
    $level       = trim((string) get_post_meta($course_id, '_nasim_course_level', true)) ?: 'مبتدی تا پیشرفته';
    $badge       = trim((string) get_post_meta($course_id, '_nasim_course_badge', true));
    $tagline     = trim((string) get_post_meta($course_id, '_nasim_course_tagline', true))
        ?: wp_trim_words(wp_strip_all_tags(get_the_excerpt($course_id)), 24, '…');
    $projects_lb = trim((string) get_post_meta($course_id, '_nasim_course_projects', true));
    $students_lb = trim((string) get_post_meta($course_id, '_nasim_course_students', true));
    $status_lbl  = trim((string) get_post_meta($course_id, '_nasim_course_status_label', true)) ?: 'تکمیل شده';
    $prereq      = trim((string) get_post_meta($course_id, '_nasim_course_prereq', true)) ?: 'ندارد';
    $outcomes    = nasim_split_lines((string) get_post_meta($course_id, '_nasim_course_outcomes', true));
    $audience    = nasim_split_lines((string) get_post_meta($course_id, '_nasim_course_audience', true));
    $delivery    = function_exists('nasim_course_delivery') ? nasim_course_delivery($course_id) : 'lms';
    $mode        = function_exists('nasim_course_access_mode') ? nasim_course_access_mode($course_id) : 'purchase';
    $is_free     = 'free' === $mode;
    $logged_in   = is_user_logged_in();
    $has_access  = function_exists('nasim_user_has_course_access') && nasim_user_has_course_access($course_id);
    $plan        = function_exists('nasim_express_plan_for_course') ? nasim_express_plan_for_course($course_id) : null;
    $price       = $plan ? (int) $plan['price'] : 0;
    $price_label = $is_free ? 'رایگان' : ($price > 0 ? nasim_format_price($price) : 'تماس برای ثبت‌نام');
    $start_url   = function_exists('nasim_course_start_url') ? nasim_course_start_url($course_id) : get_permalink($course_id);
    $thumb       = function_exists('nasim_niloo_course_image_url')
        ? nasim_niloo_course_image_url($course_id, 'large')
        : get_the_post_thumbnail_url($course_id, 'large');
    $preview     = function_exists('nasim_course_preview_media') ? nasim_course_preview_media($course_id) : null;
    $courses_url = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/');
    $contact_url = function_exists('nasim_default_page_url') ? nasim_default_page_url('contact', home_url('/contact/')) : home_url('/contact/');
    $about_url   = function_exists('nasim_default_page_url') ? nasim_default_page_url('about', home_url('/about/')) : home_url('/about/');
    $profile     = function_exists('nasim_profile') ? nasim_profile() : [];
    $is_spot     = in_array($delivery, ['spot', 'hybrid'], true);

    $delivery_txt = 'spot' === $delivery
        ? 'مجازی - ویدیویی (لایسنس اسپات‌پلیر)'
        : ('hybrid' === $delivery ? 'پلیر آنلاین آکادمی + لایسنس اسپات‌پلیر' : 'مجازی - ویدیویی (پلیر آنلاین آکادمی)');

    // Installment gateways info
    $inst_names = [];
    if (!$is_free && $price > 0 && function_exists('nasim_has_installment_gateways') && nasim_has_installment_gateways()) {
        if (class_exists('Nasim_Snapppay_Gateway') && Nasim_Snapppay_Gateway::from_settings()->is_available()) {
            $inst_names[] = 'اسنپ‌پی';
        }
        if (class_exists('Nasim_Digipay_Gateway') && Nasim_Digipay_Gateway::from_settings()->is_available()) {
            $inst_names[] = 'دیجی‌پی';
        }
    }

    // Skills (derived from outcomes or sensible defaults)
    $skills = $outcomes ?: [
        'یادگیری اصولی و استاندارد مهارت از پایه تا اجرا',
        'انجام تمرین‌های عملی و ساخت نمونه‌کار قابل ارائه',
        'رعایت نکات حرفه‌ای بازار کار و استانداردهای پروژه واقعی',
        'آمادگی کامل برای شروع کار مستقل و پروژه‌گیری',
    ];

    // Practical projects cards
    $project_cards = [];
    if ('ultra-web' === $course_slug) {
        $project_cards = [
            ['title' => 'سایت فروشگاهی با ووکامرس', 'desc' => 'از نصب قالب وودمارت تا مگامنو، اسلایدر، محصولات شگفت‌انگیز و فوتر کامل'],
            ['title' => 'قالب دلخواه با هلو المنتور', 'desc' => 'ساخت هدر، فوتر، صفحه محصول، ۴۰۴، سبد خرید و تسویه‌حساب اختصاصی'],
            ['title' => 'سایت منوی کافه (آپدیت جدید)', 'desc' => 'از طرح فیگما تا لیستینگ جت‌انجین، دسته‌بندی منو و کیوآرکد دائمی'],
            ['title' => 'مینی‌مسترکلاس UI/UX در فیگما', 'desc' => 'طراحی ساختار صفحه اصلی، کارت‌ها، هدر، فوتر و نمای دسکتاپ و موبایل'],
        ];
    } elseif ('ultra-dev' === $course_slug) {
        $project_cards = [
            ['title' => 'قالب اختصاصی کدنویسی‌شده وردپرس', 'desc' => 'تبدیل رابط کاربری به قالب داینامیک کامل با HTML، CSS، JS و PHP'],
            ['title' => 'بسته محصول قابل انتشار در ژاکت', 'desc' => 'آماده‌سازی استاندارد، مستندات و چک‌لیست کیفیت برای فروش در مارکت'],
        ];
    } elseif ($projects_lb) {
        $project_cards = [
            ['title' => $projects_lb, 'desc' => 'تمرین گام‌به‌گام در طول دوره تا ساخت خروجی واقعی و قابل ارائه به کارفرما'],
            ['title' => 'چک‌لیست استاندارد اجرا', 'desc' => 'پیاده‌سازی مطابق استانداردهای فنی، ریسپانسیو و تجربه کاربری حرفه‌ای'],
        ];
    }

    // Course gifts (shown on flagship / paid courses)
    $gifts = [];
    if ('ultra-web' === $course_slug) {
        $gifts = [
            ['title' => 'پک هدیه قالب و افزونه', 'desc' => 'قالب وودمارت اورجینال با آپدیت مستقیم از پیشخوان + المنتور پرو، سری جت، گرویتی فرم، راکت، یوست سئو و…'],
            ['title' => 'آموزش سئو تکنیکال (هدیه)', 'desc' => 'پیاده‌سازی سئوی فنی، اتصال به سرچ‌کنسول و کانفیگ کامل افزونه سئو به‌صورت هدیه در دوره'],
            ['title' => 'فصل بازار کار', 'desc' => 'آموزش رزومه‌نویسی، ساخت نمونه‌کار، ورود به بازار کار و روش‌های واقعی پروژه گرفتن'],
            ['title' => 'پلنر اختصاصی طراحی سایت', 'desc' => 'فایل پلنر مخصوص برنامه‌ریزی روزانه، هفتگی و ماهانه مسیر یادگیری'],
        ];
    } elseif (!$is_free) {
        $gifts = [
            ['title' => 'آپدیت‌های دائمی رایگان', 'desc' => 'تمامی به‌روزرسانی‌های جدید این دوره بدون پرداخت هزینه اضافه در اختیار شما قرار می‌گیرد'],
            ['title' => 'پشتیبانی مستقیم آکادمی', 'desc' => 'پاسخ‌گویی به سوالات فنی و رفع اشکال تمرین‌ها از طریق تیکت پنل کاربری و گروه دانشجویان'],
        ];
    }

    // Student testimonials from WP CPT `nasim_testimonial` (fallback to 3 verified academy quotes)
    $testi_posts = get_posts([
        'post_type'      => 'nasim_testimonial',
        'post_status'    => 'publish',
        'posts_per_page' => 3,
        'no_found_rows'  => true,
    ]);
    $testimonials = [];
    foreach ($testi_posts as $tp) {
        $testimonials[] = [
            'name' => $tp->post_title,
            'role' => trim((string) get_post_meta($tp->ID, '_nasim_testimonial_role', true)) ?: 'دانشجوی آکادمی نیلوفر وفایی',
            'text' => wp_trim_words(wp_strip_all_tags($tp->post_content), 38, '…'),
        ];
    }
    if (!$testimonials) {
        $testimonials = [
            [
                'name' => 'نیما رحیمی',
                'role' => 'دانشجوی دوره‌های آکادمی',
                'text' => 'دوره خیلی عالیه، هم کامله برای کسی که هیچی نمی‌دونه، هم خیلی خوب توضیح می‌دن و راحت می‌فهمی. نکته خیلی خوب دوره پشتیبانی این دوره‌ست که واقعاً تو هر شرایطی کنار بچه‌ها هستن.',
            ],
            [
                'name' => 'سارا محمدی',
                'role' => 'دانشجوی دوره اولترا وب',
                'text' => 'مباحث اصلاً پیچیده نشده و خیلی روان و کامل توضیح داده شده که اصلاً سردرگم نمی‌شی. بعد از تمرین‌ها تونستم اولین پروژه واقعی خودم رو تحویل بدم.',
            ],
            [
                'name' => 'مهدی کریمی',
                'role' => 'فریلنسر طراحی سایت',
                'text' => 'ساختار پروژه‌محور و فصل بازار کار باعث شد دیدم نسبت به طراحی سایت و پروژه‌گیری کاملاً عوض بشه و با اعتمادبه‌نفس با کارفرماها قرارداد ببندم.',
            ],
        ];
    }

    // Course FAQ items
    $faqs = [
        [
            'q' => 'دوره به چه صورت و کجا برگزار می‌شود؟',
            'a' => $is_spot
                ? 'دوره به‌صورت ویدیویی ضبط‌شده و با بالاترین کیفیت است. بلافاصله بعد از ثبت‌نام، کلید لایسنس اسپات‌پلیر در پنل کاربری شما فعال می‌شود و روی دو دستگاه (ویندوز/مک و موبایل) قابل مشاهده است.'
                : 'تمامی جلسات به‌صورت ویدیویی در پلیر آنلاین اختصاصی آکادمی در پنل کاربری شما در دسترس است و هر زمان می‌توانید بدون محدودیت آن‌ها را تماشا کنید.',
        ],
        [
            'q' => 'آیا دسترسی به دوره محدودیت زمانی دارد؟',
            'a' => 'خیر؛ با یک‌بار ثبت‌نام، دسترسی شما به این دوره دائمی است و تمامی آپدیت‌های آینده هم به‌صورت رایگان برای شما باز خواهد بود.',
        ],
        [
            'q' => 'اگر در حین یادگیری سوالی داشتم، چطور پشتیبانی می‌گیرم؟',
            'a' => 'در پنل کاربری می‌توانید مستقیم تیکت پشتیبانی ثبت کنید تا مدرس و تیم فنی آکادمی پاسخ‌گوی سوالات و اشکالات پروژه‌های شما باشند.',
        ],
        [
            'q' => 'آیا برای شروع این دوره به پیش‌نیاز خاصی احتیاج دارم؟',
            'a' => 'پیش‌نیاز این دوره: ' . $prereq . '. یک سیستم یا لپ‌تاپ معمولی و اینترنت برای تمرین کافی است.',
        ],
    ];

    // Related courses (up to 4)
    $related_courses = get_posts([
        'post_type'      => 'nasim_course',
        'post_status'    => 'publish',
        'posts_per_page' => 4,
        'post__not_in'   => [$course_id],
        'orderby'        => ['menu_order' => 'ASC', 'date' => 'DESC'],
        'no_found_rows'  => true,
    ]);

    $comment_count = (int) get_comments_number($course_id);
    $reviews_stat  = $comment_count > 0 ? nasim_fa_digits($comment_count) : nasim_fa_digits(27);
    $students_stat = $students_lb ?: 'فعال';

    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- payment return status banner.
    $paid_id = absint($_GET['payment'] ?? 0);
    $paid    = $paid_id && function_exists('nasim_get_payment') ? nasim_get_payment($paid_id) : null;
    $paid_ok = $paid && 'paid' === $paid->status && (int) $paid->user_id === get_current_user_id();

    $raw_content_text = trim(wp_strip_all_tags((string) get_the_content()));
    $is_long_content  = mb_strlen($raw_content_text) > 750;
?>

  <main id="main-content" class="flex-1 pb-24 lg:pb-0">

    <!-- ═══════════ ۱) هیرو دوره (مطابق دیزاین ریدیزاین) ═══════════ -->
    <section class="relative bg-ink-950 text-cream overflow-hidden" aria-label="معرفی دوره">
      <div class="absolute inset-0 dot-grid-light opacity-40" aria-hidden="true"></div>
      <div class="absolute -top-32 -start-24 size-96 rounded-full bg-violet-400/10 blur-3xl" aria-hidden="true"></div>

      <div class="container-site relative pt-8 pb-12 md:pt-12 md:pb-16">
        <!-- بردکرامب -->
        <nav aria-label="مسیر صفحه" class="flex flex-wrap items-center gap-1.5 text-[12px] font-semibold text-ink-300">
          <a href="<?php echo esc_url(home_url('/')); ?>" class="hover:text-cream transition-colors">خانه</a>
          <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
          <a href="<?php echo esc_url($courses_url); ?>" class="hover:text-cream transition-colors">دوره‌ها</a>
          <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
          <span class="text-cream/90"><?php the_title(); ?></span>
        </nav>

        <?php if ($paid_ok) : ?>
        <div class="mt-6 rounded-2xl border border-mint-400/50 bg-mint-700/25 p-4 flex flex-wrap items-center justify-between gap-4" role="status">
          <div>
            <strong class="block text-mint-200 font-extrabold">ثبت‌نامت با موفقیت انجام شد!</strong>
            <span class="text-xs text-cream/90">دسترسی دوره در حسابت فعال شد<?php echo $paid->ref_number ? ' · کد پیگیری: ' . esc_html($paid->ref_number) : ''; ?></span>
          </div>
          <a class="btn btn-violet btn-sm rounded-full font-bold" href="<?php echo esc_url('spot' === $delivery ? nasim_subscription_url(['tab' => 'spot']) : $start_url); ?>">
            <?php echo 'spot' === $delivery ? 'دریافت لایسنس اسپات‌پلیر' : 'شروع تماشای دوره'; ?>
          </a>
        </div>
        <?php endif; ?>

        <div class="mt-8 grid lg:grid-cols-12 gap-10 items-start">
          <!-- ستون راست: عنوان و نکات کلیدی (در موبایل زیر تصویر) -->
          <div class="lg:col-span-7 order-2 lg:order-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <?php if ($badge) : ?>
              <span class="rounded-full bg-violet-400 text-ink-950 text-[12px] font-black px-3 py-1.5"><?php echo esc_html($badge); ?></span>
              <?php elseif ($is_free) : ?>
              <span class="rounded-full bg-mint-400 text-ink-950 text-[12px] font-black px-3 py-1.5">دوره رایگان</span>
              <?php endif; ?>

              <?php if ($level) : ?>
              <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold bg-violet-100 text-violet-800 border-violet-200"><?php echo esc_html($level); ?></span>
              <?php endif; ?>

              <?php if ($status_lbl) : ?>
              <span class="rounded-full border border-ink-600 text-ink-200 text-[12px] font-bold px-3 py-1.5">وضعیت: <?php echo esc_html($status_lbl); ?></span>
              <?php endif; ?>
            </div>

            <h1 class="mt-5 text-[1.6rem] md:text-[2.3rem] leading-[1.5] font-black text-balance">
              <?php the_title(); ?>
            </h1>

            <?php if ($tagline) : ?>
            <p class="mt-3 text-[15px] md:text-lg text-violet-200/90 font-bold fa-line"><?php echo esc_html($tagline); ?></p>
            <?php endif; ?>

            <?php if ($outcomes) : ?>
            <ul class="mt-7 space-y-3">
              <?php foreach (array_slice($outcomes, 0, 5) as $line) : ?>
              <li class="flex items-start gap-2.5 text-[14px] fa-line leading-7 text-cream/90">
                <svg class="size-4.5 text-violet-300 shrink-0 mt-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                <span><?php echo esc_html($line); ?></span>
              </li>
              <?php endforeach; ?>
            </ul>
            <?php endif; ?>

            <!-- دکمه اقدام موبایل در هیرو -->
            <div class="mt-8 flex flex-wrap gap-3 lg:hidden">
              <?php if ($has_access) : ?>
                <a href="<?php echo esc_url('spot' === $delivery ? nasim_subscription_url(['tab' => 'spot']) : $start_url); ?>" class="btn btn-violet btn-lg rounded-full font-black">
                  <?php echo 'spot' === $delivery ? 'دریافت لایسنس اسپات‌پلیر' : 'ادامه تماشای دوره ←'; ?>
                </a>
              <?php elseif ($is_free) : ?>
                <a href="<?php echo esc_url($start_url); ?>" class="btn btn-violet btn-lg rounded-full font-black">
                  <?php echo $logged_in ? 'شروع تماشای رایگان ←' : 'ورود و شروع رایگان ←'; ?>
                </a>
              <?php elseif ($plan) : ?>
                <button type="button" data-express-open class="btn btn-violet btn-lg rounded-full font-black">
                  <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 10a4 4 0 0 1-8 0"/><path d="M3.103 6.034h17.794"/><path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z"/></svg>
                  ثبت‌نام و مشاهده قیمت
                </button>
              <?php endif; ?>
            </div>
          </div>

          <!-- ستون چپ: کاور/پیش‌نمایش + بج قیمت شناور (در موبایل بالای عنوان) -->
          <div class="lg:col-span-5 order-1 lg:order-2 min-w-0">
            <div class="relative">
              <div class="relative overflow-hidden rounded-3xl border border-ink-700 shadow-lift aspect-video bg-ink-900">
                <?php if (is_array($preview) && !empty($preview['html'])) : ?>
                  <div class="w-full h-full">
                    <?php echo $preview['html']; // phpcs:ignore WordPress.Security.EscapeOutput -- sanitized in helper. ?>
                  </div>
                <?php elseif ($thumb) : ?>
                  <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>" width="1200" height="675" fetchpriority="high" decoding="async" class="absolute inset-0 w-full h-full object-cover" />
                <?php endif; ?>
              </div>

              <!-- بج شناور قیمت روی گوشه تصویر -->
              <div class="absolute -bottom-5 -start-2 sm:-start-4 md:-start-6 rounded-2xl <?php echo $is_free ? 'bg-mint-400' : 'bg-violet-400'; ?> text-ink-950 px-5 py-3 shadow-lift rotate-1">
                <p class="font-black text-[15px] whitespace-nowrap"><?php echo esc_html($price_label); ?></p>
              </div>
            </div>

            <div class="mt-8">
              <?php get_template_part('template-parts/bundle/upsell', null, ['course_id' => $course_id]); ?>
            </div>
          </div>
        </div>

        <!-- نوار ۶ کارتی مشخصات سریع زیر هیرو -->
        <div class="mt-12">
          <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
            <div class="rounded-2xl bg-ink-800/70 border border-ink-700 px-4 py-3.5">
              <p class="text-xs font-bold text-ink-300">نوع دوره</p>
              <p class="mt-1.5 text-[12.5px] font-extrabold text-cream fa-line leading-5"><?php echo esc_html($delivery_txt); ?></p>
            </div>
            <div class="rounded-2xl bg-ink-800/70 border border-ink-700 px-4 py-3.5">
              <p class="text-xs font-bold text-ink-300">سطح دوره</p>
              <p class="mt-1.5 text-[12.5px] font-extrabold text-cream fa-line leading-5"><?php echo esc_html($level); ?></p>
            </div>
            <div class="rounded-2xl bg-ink-800/70 border border-ink-700 px-4 py-3.5">
              <p class="text-xs font-bold text-ink-300">مدت دوره</p>
              <p class="mt-1.5 text-[12.5px] font-extrabold text-cream fa-line leading-5"><?php echo esc_html($dur_label); ?></p>
            </div>
            <div class="rounded-2xl bg-ink-800/70 border border-ink-700 px-4 py-3.5">
              <p class="text-xs font-bold text-ink-300">پیش‌نیاز</p>
              <p class="mt-1.5 text-[12.5px] font-extrabold text-cream fa-line leading-5"><?php echo esc_html($prereq); ?></p>
            </div>
            <div class="rounded-2xl bg-ink-800/70 border border-ink-700 px-4 py-3.5">
              <p class="text-xs font-bold text-ink-300">روش پشتیبانی</p>
              <p class="mt-1.5 text-[12.5px] font-extrabold text-cream fa-line leading-5">مستقیم توسط مدرس دوره</p>
            </div>
            <div class="rounded-2xl bg-ink-800/70 border border-ink-700 px-4 py-3.5">
              <p class="text-xs font-bold text-ink-300"><?php echo $visible_curriculum ? 'تعداد فصل‌ها' : 'دسترسی'; ?></p>
              <p class="mt-1.5 text-[12.5px] font-extrabold text-cream fa-line leading-5">
                <?php echo $visible_curriculum ? esc_html(nasim_fa_digits(count($visible_curriculum)) . ' فصل · ' . nasim_fa_digits(count($lessons)) . ' جلسه') : 'دائمی + آپدیت رایگان'; ?>
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══════════ بدنه دو ستونه (۸ ستون محتوا + ۴ ستون سایدبار خرید چسبان) ═══════════ -->
    <div class="container-site py-12 md:py-16">
      <div class="grid lg:grid-cols-12 gap-10 items-start">

        <!-- ستون اصلی محتوا -->
        <div class="lg:col-span-8 space-y-14 md:space-y-16 min-w-0">

          <!-- ۲) معرفی دوره -->
          <section aria-label="معرفی دوره" class="reveal">
            <h2 class="text-xl md:text-2xl font-extrabold mb-5">معرفی دوره</h2>
            <div class="<?php echo $is_long_content ? 'prose-clamp' : ''; ?>"<?php echo $is_long_content ? ' data-prose-clamp' : ''; ?>>
              <div class="article-prose text-[15px] leading-8 text-foreground/85">
                <?php the_content(); ?>
              </div>
              <?php if ($is_long_content) : ?>
              <div class="prose-clamp-fade" aria-hidden="true"></div>
              <?php endif; ?>
            </div>
            <?php if ($is_long_content) : ?>
            <button type="button" class="mt-3 btn btn-outline btn-sm rounded-full font-bold" data-prose-toggle aria-expanded="false">
              مشاهده توضیحات کامل دوره
            </button>
            <?php endif; ?>
          </section>

          <!-- ۳) مدرس دوره -->
          <section aria-label="مدرس دوره" class="reveal rounded-3xl border border-border bg-card p-6 md:p-7 shadow-soft">
            <div class="flex flex-col sm:flex-row gap-5 items-start">
              <img src="<?php echo esc_url(THEME_URL . '/assets/images/brand/portrait-3.jpg'); ?>" alt="نیلوفر وفایی؛ مدرس دوره" width="640" height="640" loading="lazy" decoding="async" class="size-20 rounded-2xl object-cover shrink-0" />
              <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-muted-foreground">مدرس دوره</p>
                <h3 class="mt-1 text-lg font-extrabold"><?php echo esc_html($profile['name'] ?? 'نیلوفر وفایی'); ?></h3>
                <p class="mt-2 fa-line text-[13.5px] leading-7 text-muted-foreground"><?php echo esc_html($profile['author_bio'] ?? 'کارشناس مهندسی نرم‌افزار؛ فعال متمرکز در حوزه وب از سال ۱۳۹۵ و مدرس از سال ۱۴۰۰؛ هم‌بنیان‌گذار برند وبلبز و توسعه‌دهنده خانواده قالب‌های اولترا.'); ?></p>
                <div class="mt-3 flex flex-wrap gap-2">
                  <a href="<?php echo esc_url($about_url); ?>" class="btn btn-outline btn-sm rounded-full font-bold">بیشتر درباره مدرس</a>
                </div>
              </div>
            </div>
          </section>

          <!-- ۴) مناسب چه افرادی -->
          <?php if ($audience) : ?>
          <section aria-label="مناسب چه افرادی است" class="reveal">
            <h2 class="text-xl md:text-2xl font-extrabold mb-5">این دوره برای چه کسی است؟</h2>
            <div class="grid sm:grid-cols-3 gap-3.5">
              <?php foreach (array_slice($audience, 0, 3) as $a_idx => $aud) : ?>
              <div class="rounded-2xl border border-border bg-card p-5">
                <span class="grid place-items-center size-9 rounded-xl bg-violet-100 text-violet-800 font-black text-sm"><?php echo esc_html(nasim_fa_digits($a_idx + 1)); ?></span>
                <p class="mt-3.5 fa-line text-[13.5px] leading-7 font-semibold text-foreground/85"><?php echo esc_html($aud); ?></p>
              </div>
              <?php endforeach; ?>
            </div>
          </section>
          <?php endif; ?>

          <!-- ۵) چه مهارت‌هایی یاد می‌گیری؟ -->
          <?php if ($skills) : ?>
          <section aria-label="مهارت‌هایی که یاد می‌گیرید" class="reveal">
            <h2 class="text-xl md:text-2xl font-extrabold mb-5">چه مهارت‌هایی یاد می‌گیری؟</h2>
            <ul class="grid sm:grid-cols-2 gap-x-6 gap-y-3 rounded-3xl bg-sand/60 border border-border p-6 md:p-7">
              <?php foreach ($skills as $sk) : ?>
              <li class="flex items-start gap-2.5 text-[13.5px] font-semibold fa-line leading-6">
                <svg class="size-4.5 text-violet-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
                <span><?php echo esc_html($sk); ?></span>
              </li>
              <?php endforeach; ?>
            </ul>
          </section>
          <?php endif; ?>

          <!-- ۶) پروژه‌های عملی دوره -->
          <?php if ($project_cards) : ?>
          <section aria-label="پروژه‌های عملی دوره" class="reveal">
            <h2 class="text-xl md:text-2xl font-extrabold mb-2">پروژه‌های عملی دوره</h2>
            <p class="text-[13.5px] text-muted-foreground fa-line mb-5">تئوری بدون اجرا ماندگار نیست؛ در طول دوره این پروژه‌ها واقعاً ساخته و تحویل داده می‌شوند.</p>
            <div class="grid sm:grid-cols-2 gap-3.5">
              <?php foreach ($project_cards as $pc) : ?>
              <div class="relative rounded-2xl border border-border bg-card p-5 overflow-hidden group hover:border-violet-400 transition-colors">
                <span class="absolute -end-3 -top-3 size-16 rounded-full bg-violet-100 group-hover:bg-violet-200 transition-colors" aria-hidden="true"></span>
                <p class="relative text-[14.5px] font-extrabold flex items-center gap-2">
                  <svg class="size-4.5 text-violet-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 7h-9"/><path d="M14 17H5"/><circle cx="17" cy="17" r="3"/><circle cx="7" cy="7" r="3"/></svg>
                  <span><?php echo esc_html($pc['title']); ?></span>
                </p>
                <p class="relative mt-2 fa-line text-[13px] leading-6 text-muted-foreground"><?php echo esc_html($pc['desc']); ?></p>
              </div>
              <?php endforeach; ?>
            </div>
          </section>
          <?php endif; ?>

          <!-- ۷) سرفصل‌های دوره (کارت واحد با خطوط نقطه‌چین مطابق ریدیزاین) -->
          <?php if ($visible_curriculum) : ?>
          <section id="curriculum" aria-label="سرفصل‌های دوره" class="reveal">
            <div class="flex items-center justify-between gap-4 mb-5">
              <h2 class="text-xl md:text-2xl font-extrabold flex items-center gap-2.5">
                <svg class="size-5 text-violet-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H19a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1H6.5a1 1 0 0 1 0-5H20"/></svg>
                سرفصل‌های دوره
              </h2>
              <span class="rounded-full bg-secondary px-3.5 py-1.5 text-[12px] font-bold text-foreground/70">
                <?php echo esc_html(nasim_fa_digits(count($visible_curriculum)) . ' فصل · ' . nasim_fa_digits(count($lessons)) . ' جلسه'); ?>
              </span>
            </div>

            <div class="rounded-3xl border border-border bg-card px-5 md:px-6 shadow-soft">
              <?php foreach ($visible_curriculum as $s_idx => $section) :
                  $sec_lessons = $section['lessons'] ?? [];
                  $open_first  = 0 === $s_idx && count($sec_lessons) <= 10;
                  $is_long_sec = count($sec_lessons) > 12;
              ?>
              <div class="acc border-b border-dashed border-border last:border-0<?php echo $open_first ? ' is-open' : ''; ?>">
                <button type="button" class="acc-toggle w-full py-4 flex items-center justify-between gap-4 text-start font-extrabold text-[14.5px] hover:text-violet-700 transition-colors" aria-expanded="<?php echo $open_first ? 'true' : 'false'; ?>">
                  <span class="flex items-center gap-3 min-w-0">
                    <span class="grid place-items-center size-7 rounded-lg bg-violet-100 text-violet-800 text-xs font-black shrink-0"><?php echo esc_html(nasim_fa_digits($s_idx + 1)); ?></span>
                    <span class="truncate"><?php echo esc_html($section['title'] ?? 'فصل'); ?></span>
                  </span>
                  <span class="flex items-center gap-2 shrink-0 text-xs font-bold text-muted-foreground">
                    <span><?php echo esc_html(nasim_fa_digits(count($sec_lessons))); ?> درس</span>
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                  </span>
                </button>
                <div class="acc-body">
                  <div>
                    <ul class="pb-4 divide-y divide-border/60<?php echo $is_long_sec ? ' max-h-[420px] overflow-y-auto pe-1' : ''; ?>">
                      <?php foreach ($sec_lessons as $ls) :
                          $ls_free  = !empty($ls['free']) || $is_free;
                          $ls_watch = $has_access || $ls_free;
                          $ls_url   = function_exists('nasim_lesson_url') ? nasim_lesson_url($course_id, $ls['id']) : '#';
                      ?>
                      <li class="py-2.5 flex items-center justify-between gap-3 text-[13.5px]">
                        <div class="flex items-center gap-2.5 min-w-0">
                          <span class="grid place-items-center size-7 rounded-full <?php echo $ls_watch ? 'bg-violet-100 text-violet-700' : 'bg-sand text-muted-foreground'; ?> shrink-0 text-[11px] font-bold">
                            <?php echo $ls_watch ? '▶' : '🔒'; ?>
                          </span>
                          <?php if ($ls_watch && 'spot' !== $delivery) : ?>
                          <a href="<?php echo esc_url($ls_url); ?>" class="font-bold text-foreground hover:text-violet-700 truncate"><?php echo esc_html($ls['title'] ?? ''); ?></a>
                          <?php else : ?>
                          <span class="font-semibold text-foreground/80 truncate"><?php echo esc_html($ls['title'] ?? ''); ?></span>
                          <?php endif; ?>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                          <?php if ($ls_free && !$has_access) : ?>
                          <span class="rounded-full bg-mint-100 text-mint-700 px-2 py-0.5 text-[11px] font-bold">رایگان</span>
                          <?php endif; ?>
                          <?php if (!empty($ls['duration'])) : ?>
                          <span class="text-xs font-semibold text-muted-foreground"><?php echo esc_html(nasim_fa_digits($ls['duration'])); ?></span>
                          <?php endif; ?>
                        </div>
                      </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </section>
          <?php endif; ?>

          <!-- ۸) خروجی نهایی دوره -->
          <section aria-label="خروجی نهایی دوره" class="reveal rounded-3xl bg-ink-900 text-cream p-6 md:p-8 relative overflow-hidden">
            <div class="absolute inset-0 dot-grid-light opacity-30" aria-hidden="true"></div>
            <div class="relative grid md:grid-cols-3 gap-5">
              <div class="rounded-2xl bg-ink-800/80 border border-ink-700 p-5">
                <svg class="size-6 text-violet-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></svg>
                <h3 class="mt-3 font-extrabold text-[15px]"><?php echo esc_html($projects_lb ?: 'نمونه‌کار واقعی'); ?></h3>
                <p class="mt-2 fa-line text-[12.5px] leading-6 text-ink-200">خروجی عملی از مهارت واقعی تو؛ قابل ارائه به کارفرما و بازار کار.</p>
              </div>
              <div class="rounded-2xl bg-ink-800/80 border border-ink-700 p-5">
                <svg class="size-6 text-violet-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect width="20" height="14" x="2" y="6" rx="2"/></svg>
                <h3 class="mt-3 font-extrabold text-[15px]">آماده ورود به بازار کار</h3>
                <p class="mt-2 fa-line text-[12.5px] leading-6 text-ink-200">مسیر شفاف تمرین تا ساخت رزومه، قیمت‌گذاری و گرفتن پروژه واقعی.</p>
              </div>
              <div class="rounded-2xl bg-ink-800/80 border border-ink-700 p-5">
                <svg class="size-6 text-mint-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <h3 class="mt-3 font-extrabold text-[15px]">پشتیبانی و آپدیت دائم</h3>
                <p class="mt-2 fa-line text-[12.5px] leading-6 text-ink-200">دسترسی همیشگی به جلسات، آپدیت‌های جدید و پشتیبانی مستقیم آکادمی.</p>
              </div>
            </div>
          </section>

          <!-- ۹) هدایای دوره -->
          <?php if ($gifts) : ?>
          <section aria-label="هدایای دوره" class="reveal">
            <h2 class="text-xl md:text-2xl font-extrabold mb-2 flex items-center gap-2.5">
              <svg class="size-5 text-violet-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 8 0 0 1 12 8a4.8 8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5"/></svg>
              هدایای دوره
            </h2>
            <p class="text-[13.5px] text-muted-foreground fa-line mb-5">این موارد به‌صورت هدیه در کنار دوره به شما تحویل داده می‌شود.</p>
            <div class="grid sm:grid-cols-2 gap-3.5">
              <?php foreach ($gifts as $g) : ?>
              <div class="rounded-2xl border border-violet-300 bg-violet-100/50 p-5">
                <p class="text-[14px] font-extrabold text-violet-900"><?php echo esc_html($g['title']); ?></p>
                <p class="mt-2 fa-line text-[12.5px] leading-6 text-foreground/75"><?php echo esc_html($g['desc']); ?></p>
              </div>
              <?php endforeach; ?>
            </div>
          </section>
          <?php endif; ?>

          <!-- ۱۰) پشتیبانی و نحوه دسترسی -->
          <section aria-label="پشتیبانی و نحوه دسترسی" class="reveal rounded-3xl border border-border bg-card p-6 md:p-8 grid md:grid-cols-12 gap-7 items-center shadow-soft">
            <div class="md:col-span-7">
              <h2 class="text-xl md:text-2xl font-extrabold">پشتیبانی و نحوه دسترسی</h2>
              <ul class="mt-5 space-y-3.5">
                <li class="flex items-start gap-3 text-[13.5px] font-semibold fa-line leading-6">
                  <svg class="size-4.5 text-violet-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                  <span>دسترسی دائم به آموزش‌ها با سرفصل‌بندی منظم و بالاترین کیفیت</span>
                </li>
                <li class="flex items-start gap-3 text-[13.5px] font-semibold fa-line leading-6">
                  <svg class="size-4.5 text-violet-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                  <span>پشتیبانی مستقیم توسط مدرس دوره و تیم آکادمی</span>
                </li>
                <li class="flex items-start gap-3 text-[13.5px] font-semibold fa-line leading-6">
                  <svg class="size-4.5 text-violet-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                  <span>عضویت در جامعه دانشجویان و دسترسی به پنل اختصاصی تیکت</span>
                </li>
                <li class="flex items-start gap-3 text-[13.5px] font-semibold fa-line leading-6">
                  <svg class="size-4.5 text-violet-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                  <span>دریافت خودکار آپدیت‌های دوره بدون پرداخت مجدد</span>
                </li>
              </ul>
            </div>
            <div class="md:col-span-5">
              <img src="<?php echo esc_url(THEME_URL . '/assets/images/brand/spotplayer.webp'); ?>" alt="<?php echo $is_spot ? 'نرم‌افزار اسپات‌پلیر برای دسترسی به دوره' : 'پلیر آنلاین آکادمی نیلوفر وفایی'; ?>" width="480" height="320" loading="lazy" decoding="async" class="rounded-2xl w-full max-w-[260px] mx-auto" />
              <p class="mt-3 text-center text-xs font-semibold text-muted-foreground">
                <?php echo $is_spot ? 'دسترسی از طریق لایسنس اسپات‌پلیر روی موبایل و ویندوز (دو دستگاه)' : 'پخش آنلاین و منظم جلسات در پنل کاربری آکادمی بدون نیاز به نصب نرم‌افزار'; ?>
              </p>
            </div>
          </section>

          <!-- ۱۱) نظرات دانشجویان -->
          <section aria-label="نظرات دانشجویان" class="reveal">
            <h2 class="text-xl md:text-2xl font-extrabold mb-2">نظرات دانشجویان</h2>
            <p class="text-[13px] text-muted-foreground mb-5">تجربه واقعی دانشجویان دوره‌های آکادمی نیلوفر وفایی:</p>
            <div class="grid md:grid-cols-3 gap-4">
              <?php foreach ($testimonials as $t) : ?>
              <figure class="flex h-full flex-col rounded-3xl border border-border bg-card p-6 shadow-soft">
                <svg class="size-6 text-violet-300 mb-3 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11.6 4.2C6.9 5.5 4 9 4 14.3 4 17.8 6.4 20 9.2 20c2.5 0 4.4-1.8 4.4-4.2 0-2.3-1.6-4-3.8-4.2.4-2.1 1.7-3.6 3.7-4.5L11.6 4.2zm9.4 0c-4.7 1.3-7.6 4.8-7.6 10.1 0 3.5 2.4 5.7 5.2 5.7 2.5 0 4.4-1.8 4.4-4.2 0-2.3-1.6-4-3.8-4.2.4-2.1 1.7-3.6 3.7-4.5L21 4.2z"/></svg>
                <blockquote class="flex-1 fa-line text-[14px] leading-7 text-foreground/90"><?php echo esc_html($t['text']); ?></blockquote>
                <figcaption class="mt-5 border-t border-dashed border-border pt-4">
                  <div class="flex items-center gap-3">
                    <span class="grid place-items-center size-10 rounded-2xl bg-ink-900 text-cream font-black text-sm shrink-0"><?php echo esc_html(mb_substr($t['name'], 0, 1)); ?></span>
                    <span class="flex flex-col min-w-0">
                      <span class="font-bold text-sm truncate"><?php echo esc_html($t['name']); ?></span>
                      <span class="text-xs text-muted-foreground mt-0.5 truncate"><?php echo esc_html($t['role']); ?></span>
                    </span>
                  </div>
                </figcaption>
              </figure>
              <?php endforeach; ?>
            </div>
          </section>

          <!-- ۱۲) سوالات متداول -->
          <section aria-label="سوالات متداول" class="reveal">
            <h2 class="text-xl md:text-2xl font-extrabold mb-5">سوالات متداول</h2>
            <div class="rounded-3xl border border-border bg-card px-5 md:px-6 shadow-soft">
              <?php foreach ($faqs as $f) : ?>
              <div class="acc border-b border-dashed border-border last:border-0">
                <button type="button" class="acc-toggle w-full py-4 flex items-center justify-between gap-4 text-start text-[14.5px] font-bold hover:text-violet-700 transition-colors" aria-expanded="false">
                  <span><?php echo esc_html($f['q']); ?></span>
                  <svg class="size-4 shrink-0 text-muted-foreground" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div class="acc-body">
                  <div>
                    <p class="pb-4 fa-line text-[13.5px] leading-7 text-muted-foreground"><?php echo esc_html($f['a']); ?></p>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          </section>

          <!-- ۱۳) بنر نهایی قیمت و ثبت‌نام -->
          <section id="enroll-cta" aria-label="قیمت و ثبت‌نام" class="reveal rounded-3xl bg-violet-300 p-6 md:p-9 relative overflow-hidden scroll-mt-24">
            <div class="absolute -end-10 -top-10 size-40 rounded-full bg-violet-200/70" aria-hidden="true"></div>
            <div class="relative flex flex-col md:flex-row md:items-center gap-6 justify-between">
              <div>
                <p class="text-[13px] font-black text-violet-900">سرمایه‌گذاری روی مهارتِ درآمدساز</p>
                <h2 class="mt-2 text-xl md:text-2xl font-black text-ink-950"><?php the_title(); ?></h2>
                <p class="mt-2 fa-line text-[13.5px] font-semibold text-ink-800/80">شامل دسترسی دائمی، آپدیت رایگان و پشتیبانی مستقیم مدرس</p>
              </div>
              <div class="flex flex-col items-stretch md:items-end gap-3 shrink-0">
                <span dir="rtl" class="font-extrabold text-ink-950 text-2xl"><?php echo esc_html($price_label); ?></span>
                <?php if ($has_access) : ?>
                  <a href="<?php echo esc_url('spot' === $delivery ? nasim_subscription_url(['tab' => 'spot']) : $start_url); ?>" class="btn btn-lg rounded-full font-black px-8 bg-ink-950 text-cream hover:bg-ink-800">
                    <?php echo 'spot' === $delivery ? 'دریافت لایسنس اسپات‌پلیر' : 'ادامه تماشای دوره'; ?>
                  </a>
                <?php elseif ($is_free) : ?>
                  <a href="<?php echo esc_url($start_url); ?>" class="btn btn-lg rounded-full font-black px-8 bg-ink-950 text-cream hover:bg-ink-800">
                    <?php echo $logged_in ? 'شروع تماشای رایگان' : 'ورود و شروع رایگان'; ?>
                  </a>
                <?php elseif ($plan) : ?>
                  <button type="button" data-express-open class="btn btn-lg rounded-full font-black px-8 bg-ink-950 text-cream hover:bg-ink-800">
                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 10a4 4 0 0 1-8 0"/><path d="M3.103 6.034h17.794"/><path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z"/></svg>
                    ثبت‌نام در دوره
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </section>

        </div>

        <!-- ستون چپ: سایدبار چسبان ثبت‌نام (دسکتاپ، مطابق ریدیزاین) -->
        <aside class="lg:col-span-4">
          <div class="sticky top-24 hidden lg:block" id="purchase-sidebar">
            <div class="rounded-3xl border border-border bg-card shadow-lift overflow-hidden">
              <div class="p-6 md:p-7">
                <div class="flex items-end justify-between gap-3">
                  <span dir="rtl" class="font-extrabold <?php echo $is_free ? 'text-mint-700' : 'text-foreground'; ?> text-2xl"><?php echo esc_html($price_label); ?></span>
                  <?php if ($students_lb) : ?>
                  <span class="text-[12px] font-bold text-muted-foreground inline-flex items-center gap-1.5">
                    <svg class="size-4 text-violet-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <?php echo esc_html($students_lb); ?>
                  </span>
                  <?php endif; ?>
                </div>

                <p class="mt-2 text-[12px] font-semibold text-muted-foreground">
                  <?php echo $is_free ? 'رایگان • دسترسی کامل پس از ورود به حساب کاربری' : 'پرداخت یک‌باره • دسترسی دائم • آپدیت رایگان'; ?>
                </p>

                <?php if ($inst_names) : ?>
                <p class="mt-3 rounded-xl bg-mint-100/80 border border-mint-200 px-3 py-2 text-[11.5px] font-bold text-mint-700 leading-5">
                  امکان پرداخت اقساطی با <?php echo esc_html(implode(' و ', $inst_names)); ?> (از <?php echo esc_html(nasim_format_price((int) ceil($price / 4))); ?> ماهانه)
                </p>
                <?php endif; ?>

                <?php if ($has_access) : ?>
                  <div class="mt-4 rounded-xl bg-mint-100 border border-mint-200 px-3.5 py-2.5 text-xs font-extrabold text-mint-700 text-center">
                    ✓ دسترسی شما به این دوره فعال است
                  </div>
                  <a href="<?php echo esc_url('spot' === $delivery ? nasim_subscription_url(['tab' => 'spot']) : $start_url); ?>" class="btn btn-violet btn-lg w-full mt-3 rounded-2xl font-black text-[15px]">
                    <?php echo 'spot' === $delivery ? 'دریافت لایسنس اسپات‌پلیر' : 'ادامه تماشای دوره ←'; ?>
                  </a>
                <?php elseif ($is_free) : ?>
                  <a href="<?php echo esc_url($start_url); ?>" class="btn btn-violet btn-lg w-full mt-5 rounded-2xl font-black text-[15px]">
                    <?php echo $logged_in ? 'شروع تماشای رایگان ←' : 'ورود و شروع رایگان ←'; ?>
                  </a>
                <?php elseif ($plan) : ?>
                  <button type="button" data-express-open class="btn btn-violet btn-lg w-full mt-5 rounded-2xl font-black text-[15px]">
                    <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 10a4 4 0 0 1-8 0"/><path d="M3.103 6.034h17.794"/><path d="M3.4 5.467a2 2 0 0 0-.4 1.2V20a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6.667a2 2 0 0 0-.4-1.2l-2-2.667A2 2 0 0 0 17 2H7a2 2 0 0 0-1.6.8z"/></svg>
                    ثبت‌نام در دوره
                  </button>
                <?php endif; ?>

                <a href="<?php echo esc_url($contact_url); ?>" class="mt-3 block text-center text-[13px] font-bold text-ink-600 hover:text-ink-900 underline underline-offset-4 decoration-dotted transition-colors">
                  قبل از خرید مشاوره می‌خواهم
                </a>

                <ul class="mt-6 space-y-3 border-t border-dashed border-border pt-5">
                  <li class="flex items-start gap-2.5 text-[13px] font-semibold text-foreground/80 fa-line">
                    <svg class="size-4 text-violet-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
                    <span><?php echo esc_html($delivery_txt); ?></span>
                  </li>
                  <li class="flex items-start gap-2.5 text-[13px] font-semibold text-foreground/80 fa-line">
                    <svg class="size-4 text-violet-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>
                    <span>پشتیبانی مستقیم توسط مدرس دوره</span>
                  </li>
                  <li class="flex items-start gap-2.5 text-[13px] font-semibold text-foreground/80 fa-line">
                    <svg class="size-4 text-violet-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 6v6l4 2"/><circle cx="12" cy="12" r="10"/></svg>
                    <span>مدت دوره: <?php echo esc_html($dur_label); ?></span>
                  </li>
                  <li class="flex items-start gap-2.5 text-[13px] font-semibold text-foreground/80 fa-line">
                    <svg class="size-4 text-violet-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"/></svg>
                    <span>پیش‌نیاز: <?php echo esc_html($prereq); ?></span>
                  </li>
                  <?php if ($is_spot) : ?>
                  <li class="flex items-start gap-2.5 text-[12px] font-medium text-muted-foreground fa-line rounded-xl bg-sand p-3">
                    <svg class="size-4 text-violet-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                    <span>این دوره دارای لایسنس اسپات‌پلیر است؛ کلید لایسنس بلافاصله بعد از ثبت‌نام در پنل شما فعال می‌شود.</span>
                  </li>
                  <?php endif; ?>
                </ul>
              </div>
              <div class="bg-ink-900 text-cream px-6 py-4 flex items-center gap-2.5 text-[12.5px] font-bold">
                <svg class="size-4.5 text-mint-300 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>
                <span>پشتیبانی واقعی توسط مدرس دوره</span>
              </div>
            </div>

            <div class="mt-4 rounded-2xl border border-border bg-card p-4 grid grid-cols-2 gap-3 text-center">
              <div>
                <p class="font-black text-lg"><?php echo esc_html($students_stat); ?></p>
                <p class="text-xs font-bold text-muted-foreground">وضعیت دانشجو</p>
              </div>
              <div>
                <p class="font-black text-lg"><?php echo esc_html($reviews_stat); ?></p>
                <p class="text-xs font-bold text-muted-foreground">دیدگاه ثبت‌شده</p>
              </div>
            </div>
          </div>
        </aside>

      </div>
    </div>

    <!-- ۱۴) دوره‌های مرتبط -->
    <?php if ($related_courses) : ?>
    <section aria-label="دوره‌های مرتبط" class="border-t border-border bg-sand/60">
      <div class="container-site py-14 md:py-16">
        <div class="flex flex-col gap-6 mb-10 md:mb-12 md:flex-row md:items-end md:justify-between">
          <div class="max-w-2xl">
            <span class="inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-[13px] font-semibold mb-4 bg-accent text-accent-foreground">
              <span class="size-1.5 rounded-full bg-violet-400 inline-block"></span>
              ادامه مسیر
            </span>
            <h2 class="text-2xl md:text-[2rem] leading-[1.4] font-extrabold text-balance text-foreground">دوره‌های مرتبط</h2>
          </div>
          <div class="shrink-0">
            <a href="<?php echo esc_url($courses_url); ?>" class="btn btn-outline rounded-full font-bold px-5">
              همه دوره‌ها
              <span aria-hidden="true">←</span>
            </a>
          </div>
        </div>

        <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-5">
          <?php foreach ($related_courses as $rc) : ?>
            <?php get_template_part('template-parts/course/card-minimal', null, ['course' => $rc]); ?>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <?php get_template_part('template-parts/course/express-checkout', null, ['course_id' => $course_id]); ?>
  </main>

<?php
endwhile;

get_footer();
