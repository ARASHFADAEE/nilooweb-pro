<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * سکشن تعاملی مسیر یادگیری ۵ مرحله‌ای صفحه اصلی (#learning-path)
 * هر مرحله به دوره‌های واقعی WordPress (nasim_course) متصل است.
 */
$steps = [
    [
        'id'      => 'start',
        'num'     => '۱',
        'title'   => 'شروع از صفر',
        'desc'    => 'اول مسیر و پایه‌های دنیای طراحی وب را بشناس تا با دید روشن و بدون سردرگمی وارد یادگیری شوی.',
        'courses' => ['capsule-ultra', 'basic-webdesign'],
    ],
    [
        'id'      => 'learn',
        'num'     => '۲',
        'title'   => 'یادگیری طراحی سایت',
        'desc'    => 'جامع‌ترین آموزش: از وردپرس و المنتور تا فروشگاه‌سازی و UI/UX فیگما.',
        'courses' => ['ultra-web'],
    ],
    [
        'id'      => 'build',
        'num'     => '۳',
        'title'   => 'اجرای پروژه‌های واقعی',
        'desc'    => 'با پیاده‌سازی پروژه‌های شرکتی، فروشگاهی، چندزبانه و رزرواسیون به سطح اجرای حرفه‌ای برس.',
        'courses' => ['ultra-plus-webdesign'],
    ],
    [
        'id'      => 'code',
        'num'     => '۴',
        'title'   => 'ارتقای مهارت و کدنویسی',
        'desc'    => 'با یادگیری HTML و CSS و ساخت قالب اختصاصی وردپرس، از یک طراح معمولی به توسعه‌دهنده حرفه‌ای تبدیل شو.',
        'courses' => ['learning-html-css', 'ultra-dev'],
    ],
    [
        'id'      => 'career',
        'num'     => '۵',
        'title'   => 'رزومه و ورود به بازار کار',
        'desc'    => 'ساخت نمونه‌کار، جذب اولین پروژه، مذاکره با کارفرما، قیمت‌گذاری و مسیر واقعی درآمدزایی.',
        'courses' => ['ultra-business'],
    ],
];

$default_step = 'learn';

// Resolve real WordPress nasim_course posts for each step
$resolve_course = static function (string $slug): ?array {
    $post = get_page_by_path($slug, OBJECT, 'nasim_course');
    if (!$post instanceof WP_Post || 'publish' !== $post->post_status) {
        return null;
    }
    $cid   = (int) $post->ID;
    $mode  = function_exists('nasim_course_access_mode') ? nasim_course_access_mode($cid) : 'paid';
    $price = function_exists('nasim_course_price') ? (int) nasim_course_price($cid) : 0;
    $is_free = ('free' === $mode || $price <= 0);
    $price_label = $is_free
        ? 'رایگان'
        : (function_exists('nasim_format_price') ? nasim_format_price($price) : number_format($price) . ' تومان');
    $thumb = get_the_post_thumbnail_url($cid, 'medium');
    if (!$thumb) {
        $thumb = get_template_directory_uri() . '/assets/images/brand/niloo-banner.webp';
    }
    return [
        'id'          => $cid,
        'title'       => get_the_title($cid),
        'url'         => get_permalink($cid),
        'thumb'       => $thumb,
        'price_label' => $price_label,
        'is_free'     => $is_free,
    ];
};

$courses_archive_url = function_exists('nasim_courses_archive_url')
    ? nasim_courses_archive_url()
    : get_post_type_archive_link('nasim_course');
?>

<section class="container-site py-16 md:py-24" id="learning-path" aria-label="مسیر یادگیری" data-learning-path>
  <div class="flex flex-col gap-6 mb-10 md:mb-14">
    <div class="max-w-2xl">
      <span class="inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-[13px] font-semibold mb-4 bg-accent text-accent-foreground">
        <span class="size-1.5 rounded-full bg-violet-400 inline-block"></span>
        مسیر یادگیری
      </span>
      <h2 class="text-2xl md:text-[2rem] leading-[1.4] font-extrabold text-balance text-foreground">
        طبق سطح فعلی‌ات مسیر را انتخاب کن؛<span class="text-ink-500"> نه طبق حرف همه</span>
      </h2>
      <p class="mt-3 fa-line text-[15px] md:text-base text-muted-foreground">
        به‌جای لیست طولانی دوره‌ها، مسیر از صفر تا ورود به بازار کار را طی کن. هر مرحله به دوره مرتبط خودش وصل است.
      </p>
    </div>
  </div>

  <!-- نوار ۵ مرحله‌ای مسیر -->
  <div class="relative">
    <div class="absolute top-6 inset-x-6 h-0.5 bg-border hidden md:block" aria-hidden="true"></div>
    <ol class="relative flex overflow-x-auto no-scrollbar snap-x snap-mandatory gap-2 md:gap-4 md:grid md:grid-cols-5 md:overflow-visible" role="tablist" aria-label="مراحل مسیر یادگیری">
      <?php foreach ($steps as $step) :
          $is_active = ($step['id'] === $default_step);
          $tab_id    = 'path-tab-' . $step['id'];
          $panel_id  = 'path-panel-' . $step['id'];
      ?>
      <li role="presentation" class="relative z-10 snap-start shrink-0 min-w-[130px] md:min-w-0 flex">
        <button
          type="button"
          id="<?php echo esc_attr($tab_id); ?>"
          role="tab"
          aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
          aria-controls="<?php echo esc_attr($panel_id); ?>"
          tabindex="<?php echo $is_active ? '0' : '-1'; ?>"
          data-path-tab="<?php echo esc_attr($step['id']); ?>"
          class="group w-full flex flex-col items-center gap-3 text-center rounded-2xl p-2 transition-all cursor-pointer <?php echo $is_active ? 'bg-accent/50' : ''; ?>"
        >
          <span
            data-path-badge
            class="grid place-items-center size-12 rounded-2xl border-2 font-black text-[15px] transition-all <?php echo $is_active
                ? 'bg-ink-900 text-violet-300 border-ink-900 shadow-lift scale-105'
                : 'bg-card text-ink-500 border-border group-hover:border-violet-400'; ?>"
          >
            <?php echo esc_html($step['num']); ?>
          </span>
          <span
            data-path-label
            class="text-xs md:text-[13.5px] font-bold leading-5 max-w-[110px] md:max-w-none transition-colors <?php echo $is_active
                ? 'text-ink-900'
                : 'text-muted-foreground group-hover:text-foreground'; ?>"
          >
            <?php echo esc_html($step['title']); ?>
          </span>
        </button>
      </li>
      <?php endforeach; ?>
    </ol>
  </div>

  <!-- پنل‌های محتوای هر مرحله -->
  <?php foreach ($steps as $step) :
      $is_active = ($step['id'] === $default_step);
      $tab_id    = 'path-tab-' . $step['id'];
      $panel_id  = 'path-panel-' . $step['id'];

      $step_courses = [];
      foreach ($step['courses'] as $c_slug) {
          $resolved = $resolve_course($c_slug);
          if ($resolved) {
              $step_courses[] = $resolved;
          }
      }
  ?>
  <div
    class="mt-8 rounded-3xl border border-border bg-card p-6 md:p-8 shadow-soft"
    role="tabpanel"
    id="<?php echo esc_attr($panel_id); ?>"
    aria-labelledby="<?php echo esc_attr($tab_id); ?>"
    tabindex="0"
    data-path-panel="<?php echo esc_attr($step['id']); ?>"
    <?php echo $is_active ? '' : 'hidden'; ?>
  >
    <div class="grid md:grid-cols-12 gap-6 items-center">
      <div class="md:col-span-7 min-w-0">
        <p class="text-xs font-bold text-violet-700">مرحله <?php echo esc_html($step['num']); ?> از ۵</p>
        <h3 class="mt-2 text-xl md:text-2xl font-extrabold text-foreground"><?php echo esc_html($step['title']); ?></h3>
        <p class="mt-3 fa-line text-[15px] text-muted-foreground"><?php echo esc_html($step['desc']); ?></p>
      </div>

      <div class="md:col-span-5 min-w-0 flex flex-col gap-2.5">
        <p class="text-xs font-bold text-muted-foreground">دوره‌های پیشنهادی این مرحله:</p>
        <?php if ($step_courses) : ?>
          <?php foreach ($step_courses as $rc) : ?>
          <a href="<?php echo esc_url($rc['url']); ?>" class="flex items-center justify-between gap-3 rounded-2xl border border-border bg-background px-4 py-3 hover:border-violet-400 hover:bg-violet-100/40 transition-all group">
            <span class="flex items-center gap-3 min-w-0">
              <img
                src="<?php echo esc_url($rc['thumb']); ?>"
                alt="<?php echo esc_attr($rc['title']); ?>"
                loading="lazy"
                width="128"
                height="72"
                decoding="async"
                class="w-16 h-9 rounded-lg object-cover shrink-0 bg-ink-900"
              />
              <span class="min-w-0">
                <span class="block text-sm font-bold text-foreground truncate"><?php echo esc_html($rc['title']); ?></span>
                <span class="block text-xs font-semibold text-muted-foreground mt-0.5">
                  <span dir="rtl" class="font-extrabold <?php echo $rc['is_free'] ? 'text-mint-700' : 'text-foreground'; ?> text-sm"><?php echo esc_html($rc['price_label']); ?></span>
                </span>
              </span>
            </span>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4.5 text-ink-400 group-hover:text-ink-900 group-hover:-translate-x-1 transition-all shrink-0" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
          </a>
          <?php endforeach; ?>
        <?php else : ?>
          <a href="<?php echo esc_url($courses_archive_url); ?>" class="flex items-center justify-between gap-3 rounded-2xl border border-border bg-background px-4 py-3 hover:border-violet-400 hover:bg-violet-100/40 transition-all group">
            <span class="text-sm font-bold text-foreground">مشاهده دوره‌های این مرحله</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4.5 text-ink-400 group-hover:text-ink-900 group-hover:-translate-x-1 transition-all shrink-0" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</section>
