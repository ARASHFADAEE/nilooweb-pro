<?php

if (!defined('ABSPATH')) {
    exit;
}

$ultra_web_post = get_page_by_path('ultra-web', OBJECT, 'nasim_course');
$ultra_web_url  = ($ultra_web_post instanceof WP_Post)
    ? get_permalink($ultra_web_post->ID)
    : (function_exists('nasim_courses_archive_url') ? nasim_courses_archive_url() : home_url('/courses/'));

$success_url = function_exists('nasim_page_url') ? nasim_page_url('success') : home_url('/success/');

$wp_testimonials = get_posts([
    'post_type'      => 'nasim_testimonial',
    'post_status'    => 'publish',
    'posts_per_page' => max(1, min(12, (int) (nasim_mod('testimonials_count') ?: 3))),
    'orderby'        => ['menu_order' => 'ASC', 'date' => 'DESC'],
    'no_found_rows'  => true,
]);

$cards = [];
if ($wp_testimonials) {
    foreach ($wp_testimonials as $item) {
        $name        = trim((string) $item->post_title);
        $quote       = trim(wp_strip_all_tags((string) $item->post_content));
        $role        = trim((string) get_post_meta($item->ID, '_nasim_testimonial_role', true)) ?: 'دانشجوی دوره‌های آکادمی';
        $course_id   = (int) get_post_meta($item->ID, '_nasim_testimonial_course_id', true);
        $course_url  = ($course_id && get_post_status($course_id)) ? get_permalink($course_id) : '';
        $course_name = ($course_id && get_post_status($course_id)) ? get_the_title($course_id) : '';
        $source      = trim((string) get_post_meta($item->ID, '_nasim_testimonial_source', true));
        if (!$source) {
            $source = $course_name
                ? 'ثبت‌شده روی صفحه رسمی دوره ' . $course_name
                : 'ثبت‌شده روی صفحات دوره‌های آکادمی';
        }
        $cards[] = [
            'name'       => $name,
            'role'       => $role,
            'quote'      => $quote,
            'course_url' => $course_url,
            'source'     => $source,
        ];
    }
} else {
    $cards = [
        [
            'name'       => 'نیما رحیمی',
            'role'       => 'دانشجوی دوره‌های آکادمی',
            'quote'      => 'دوره خیلی عالیه، هم کامله برای کسی که هیچی نمیدونه، هم خیلی خوب توضیح میدن و راحت میفهمین با هر سطح یادگیری. نکته خیلی خوب دوره بنظرم پشتیبانی این دوره هست؛ واقعاً تو پشتیبانی خوبن، تو هر شرایطی پشتیبانی رو داریم.',
            'course_url' => '',
            'source'     => 'ثبت‌شده روی صفحات دوره‌های آکادمی',
        ],
        [
            'name'       => 'مبینا جسری',
            'role'       => 'دانشجوی دوره اولترا وب و فتوشاپ',
            'quote'      => 'من از دانشجوهای دوره اولترا وب هستم و دوره فتوشاپ رو هم شروع کردم به دیدن؛ خیلی خوب بود، اصلاً مباحث رو پیچیده نکرده بودند و روان و کامل توضیح میدادن که اصلاً سر درگم نمیشدید. و چون داخل دوره وب هم ثبت‌نام کردم، بهم پشتیبانی دادن و کمک کردن.',
            'course_url' => $ultra_web_url,
            'source'     => 'ثبت‌شده روی صفحه رسمی دوره اولترا وب',
        ],
        [
            'name'       => 'سمیه ترابی',
            'role'       => 'دانشجوی دوره‌های آکادمی',
            'quote'      => 'سلام، دوره عالی هست؛ مخصوصاً پشتیبانی فوق‌العاده‌ای که حتی در این شرایط دارید. ازتون سپاسگزارم.',
            'course_url' => '',
            'source'     => 'ثبت‌شده روی صفحات دوره‌های آکادمی',
        ],
    ];
}

$projects = [
    [
        'title' => 'سایت فروشگاهی با ووکامرس',
        'desc'  => 'از نصب قالب وودمارت تا مگامنو، اسلایدر، محصولات شگفت‌انگیز و فوتر کامل',
    ],
    [
        'title' => 'قالب دلخواه با هلو المنتور',
        'desc'  => 'ساخت هدر، فوتر، صفحه محصول، ۴۰۴، سبد خرید و تسویه‌حساب اختصاصی',
    ],
    [
        'title' => 'سایت منوی کافه (آپدیت جدید)',
        'desc'  => 'از طرح فیگما تا لیستینگ جت‌انجین، تب منو و ساخت QR کد دائمی',
    ],
    [
        'title' => 'اولین پروژه کامل (فصل اول)',
        'desc'  => 'راه‌اندازی وردپرس لوکال، نصب قالب و افزونه و ساخت یک سایت کامل',
    ],
];
?>

<section id="testimonials" class="relative bg-ink-950 text-cream overflow-hidden" aria-label="نتایج دانشجویان">
  <div class="absolute inset-0 dot-grid-light opacity-50" aria-hidden="true"></div>
  <div class="absolute -bottom-40 -start-40 size-[30rem] rounded-full bg-violet-400/10 blur-3xl" aria-hidden="true"></div>

  <div class="container-site relative py-16 md:py-24">
    <div class="flex flex-col gap-6 mb-10 md:mb-14">
      <div class="max-w-2xl">
        <span class="inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-[13px] font-semibold mb-4 bg-ink-800 text-violet-300 border border-ink-700">
          <span class="size-1.5 rounded-full bg-violet-400 inline-block"></span>
          نتایج دانشجویان
        </span>
        <h2 class="text-2xl md:text-[2rem] leading-[1.4] font-extrabold text-balance text-cream">
          نتیجه واقعی، از زبان خود دانشجوها
        </h2>
        <p class="mt-3 fa-line text-[15px] md:text-base text-ink-200">
          نقل‌قول‌های واقعی ثبت‌شده روی صفحات دوره‌ها؛ همراه با دوره و محل ثبت هر نظر.
        </p>
      </div>
    </div>

    <!-- کارت‌های نظرات دانشجویان -->
    <div class="grid md:grid-cols-3 gap-5 md:gap-6">
      <?php foreach ($cards as $card) :
          $initial = mb_substr($card['name'], 0, 1);
      ?>
      <div class="h-full">
        <figure class="flex h-full flex-col rounded-3xl border border-ink-800 bg-ink-900 p-6">
          <svg width="34" height="34" viewBox="0 0 24 24" class="text-violet-400 mb-4 shrink-0" fill="currentColor" aria-hidden="true">
            <path d="M10 8c-3.3 0-6 2.7-6 6v2h4v-2c0-1.1.9-2 2-2v-2c0-.7-.1-1.3-.3-1.9.7-.1 1.1-.1 1.3-.1zM20 8c-3.3 0-6 2.7-6 6v2h4v-2c0-1.1.9-2 2-2v-2c0-.7-.1-1.3-.3-1.9.7-.1 1.1-.1 1.3-.1z"/>
          </svg>

          <blockquote class="flex-1 fa-line text-[15px] leading-8 text-cream/90">
            <?php echo esc_html($card['quote']); ?>
          </blockquote>

          <figcaption class="mt-5 border-t border-dashed border-ink-800 pt-4">
            <div class="flex items-center gap-3">
              <span class="grid place-items-center size-11 rounded-2xl bg-ink-800 text-cream font-black text-base shrink-0">
                <?php echo esc_html($initial); ?>
              </span>
              <span class="flex flex-col min-w-0 flex-1">
                <span class="font-bold text-sm text-cream truncate"><?php echo esc_html($card['name']); ?></span>
                <span class="text-xs text-ink-300 mt-0.5 truncate"><?php echo esc_html($card['role']); ?></span>
              </span>
              <?php if (!empty($card['course_url'])) : ?>
              <a href="<?php echo esc_url($card['course_url']); ?>" class="shrink-0 inline-flex items-center gap-1 rounded-full bg-ink-800 px-3 py-2 text-xs font-bold text-violet-200 hover:bg-ink-700 hover:text-cream transition-colors">
                مشاهده دوره
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
              </a>
              <?php endif; ?>
            </div>
            <p class="mt-2.5 text-xs font-medium text-ink-300">
              <?php echo esc_html($card['source']); ?>
            </p>
          </figcaption>
        </figure>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- باکس بررسی نمونه‌کار دانشجویان و ۴ پروژه عملی -->
    <div class="mt-10 rounded-3xl border border-ink-800 bg-ink-900/70 p-6 md:p-8">
      <div class="grid md:grid-cols-12 gap-6 items-center">
        <div class="md:col-span-8">
          <h3 class="text-lg md:text-xl font-extrabold flex items-center gap-2.5 text-cream">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5.5 text-violet-300 shrink-0" aria-hidden="true"><path d="M12 21V7"/><path d="m16 12 2 2 4-4"/><path d="M22 6V4a1 1 0 0 0-1-1h-5a4 4 0 0 0-4 4 4 4 0 0 0-4-4H3a1 1 0 0 0-1 1v13a1 1 0 0 0 1 1h6a3 3 0 0 1 3 3 3 3 0 0 1 3-3h6a1 1 0 0 0 1-1v-1.3"/></svg>
            بررسی نمونه‌کار دانشجویان — فصل اختصاصی اولترا وب
          </h3>
          <p class="mt-2 fa-line text-sm text-ink-200">
            در فصل «بررسی نمونه‌کار دانشجویان»، پروژه‌های ساخته‌شده توسط دانشجوها به‌صورت ویدیویی کامل تحلیل و بازخورد داده می‌شود؛ دقیقاً همان چیزی که یک رزومه قوی لازم دارد.
          </p>
        </div>
        <div class="md:col-span-4 flex md:justify-end">
          <a href="<?php echo esc_url($success_url); ?>" class="btn rounded-full h-11 px-5 bg-cream text-ink-900 hover:bg-violet-100 font-bold">
            صفحه نتایج و نمونه‌کارها
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
          </a>
        </div>
      </div>

      <p class="mt-7 text-[13px] font-bold text-ink-300">نمونه‌ی پروژه‌هایی که در دوره ساخته می‌شوند:</p>
      <div class="mt-3.5 grid grid-cols-2 lg:grid-cols-4 gap-3">
        <?php foreach ($projects as $proj) : ?>
        <a href="<?php echo esc_url($ultra_web_url); ?>" class="group rounded-2xl border border-ink-800 bg-ink-900 p-4 hover:border-violet-400/60 hover:bg-ink-800 transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5 text-violet-300" aria-hidden="true"><path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"/><path d="M2 12a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 12"/><path d="M2 17a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 17"/></svg>
          <p class="mt-2.5 text-[13px] font-bold leading-6 text-cream"><?php echo esc_html($proj['title']); ?></p>
          <p class="mt-1.5 text-xs font-medium text-ink-300 fa-line leading-5 line-clamp-2"><?php echo esc_html($proj['desc']); ?></p>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
