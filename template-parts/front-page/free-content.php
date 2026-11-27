<?php

if (!defined('ABSPATH')) {
    exit;
}

$radio_cat_slug = nasim_mod('radio_cat') ?: 'radionil';
$radio_more_url = nasim_mod('radio_more_url') ?: home_url('/%d8%b1%d8%a7%d8%af%db%8c%d9%88%d9%86%db%8c%d9%84/');
$blog_url       = function_exists('nasim_blog_url') ? nasim_blog_url() : home_url('/blog/');

/* ── رادیو نیل: ۳ قسمت آخر ── */
$radio_cat = get_category_by_slug($radio_cat_slug);
$episodes  = get_posts([
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 3,
    'category_name'  => $radio_cat_slug,
    'no_found_rows'  => true,
]);

/* ── مجله آموزشی: ۳ مقاله آخر (به‌جز دسته رادیو) ── */
$blog_args = [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 3,
    'no_found_rows'  => true,
];
if ($radio_cat && !is_wp_error($radio_cat)) {
    $blog_args['category__not_in'] = [(int) $radio_cat->term_id];
}
$articles = get_posts($blog_args);

/* ── کپسول اولترا: دوره رایگان ── */
$capsule_post = get_page_by_path('capsule-ultra', OBJECT, 'nasim_course');
$capsule      = null;
if ($capsule_post instanceof WP_Post && 'publish' === $capsule_post->post_status) {
    $cid   = (int) $capsule_post->ID;
    $thumb = get_the_post_thumbnail_url($cid, 'large');
    $capsule = [
        'url'      => get_permalink($cid),
        'title'    => get_the_title($cid),
        'excerpt'  => wp_strip_all_tags(get_the_excerpt($cid)),
        'thumb'    => $thumb ?: (THEME_URL . '/assets/images/brand/niloo-banner.webp'),
        'is_free'  => function_exists('nasim_course_access_mode') && 'free' === nasim_course_access_mode($cid),
    ];
}

if (!$episodes && !$articles && !$capsule) {
    return;
}
?>

<section id="free-content" class="container-site py-16 md:py-24" aria-label="محتوای رایگان">
  <div class="flex flex-col gap-6 mb-10 md:mb-14">
    <div class="max-w-2xl">
      <span class="inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-[13px] font-semibold mb-4 bg-accent text-accent-foreground">
        <span class="size-1.5 rounded-full bg-violet-400 inline-block"></span>
        محتوای رایگان
      </span>
      <h2 class="text-2xl md:text-[2rem] leading-[1.4] font-extrabold text-balance text-foreground">
        قبل از خرید، با سبک ما آشنا شو
      </h2>
      <p class="mt-3 fa-line text-[15px] md:text-base text-muted-foreground">
        رادیو نیل، مقالات و آموزش‌های رایگان؛ ارتباط بلندمدت با برند، بدون هیچ هزینه‌ای.
      </p>
    </div>
  </div>

  <div class="grid lg:grid-cols-12 gap-6">

    <!-- رادیو نیل (تیره) -->
    <?php if ($episodes) : ?>
    <div class="lg:col-span-5 min-w-0">
      <div class="h-full rounded-3xl bg-ink-900 text-cream p-6 md:p-7 flex flex-col">
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-xl font-extrabold flex items-center gap-2.5">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5.5 text-violet-300" aria-hidden="true"><path d="M12 19v3"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><rect x="9" y="2" width="6" height="13" rx="3"/></svg>
            رادیو نیل
          </h3>
          <a href="<?php echo esc_url($radio_more_url); ?>" class="inline-flex items-center gap-1.5 px-2.5 py-2.5 -my-2.5 rounded-xl text-[13px] font-bold text-violet-300 hover:text-violet-200 hover:bg-ink-800 transition-colors shrink-0">
            همه قسمت‌ها
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
          </a>
        </div>
        <p class="mt-2.5 text-[13px] fa-line text-ink-200">پادکست تخصصی تکنولوژی و وب با صدای نیلوفر وفایی.</p>

        <ul class="mt-5 space-y-2.5 flex-1">
          <?php foreach ($episodes as $ep) : ?>
          <li>
            <a href="<?php echo esc_url(get_permalink($ep->ID)); ?>" class="flex items-center gap-3 rounded-2xl bg-ink-800/70 border border-ink-800 px-4 py-3 hover:bg-ink-800 transition-colors">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-7 text-violet-300 shrink-0" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
              <span class="min-w-0">
                <span class="block text-[13.5px] font-bold truncate"><?php echo esc_html($ep->post_title); ?></span>
                <span class="block text-xs font-semibold text-ink-300 mt-0.5"><?php echo esc_html(get_the_date('', $ep->ID)); ?></span>
              </span>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <?php endif; ?>

    <!-- مجله آموزشی (کارت سفید) -->
    <?php if ($articles) : ?>
    <div class="lg:col-span-4 min-w-0">
      <div class="h-full rounded-3xl border border-border bg-card p-6 md:p-7 flex flex-col">
        <div class="flex items-center justify-between gap-3">
          <h3 class="text-xl font-extrabold flex items-center gap-2.5">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5.5 text-ink-600" aria-hidden="true"><path d="M15 18h-5"/><path d="M18 14h-8"/><path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-4 0v-9a2 2 0 0 1 2-2h2"/><rect width="8" height="4" x="10" y="6" rx="1"/></svg>
            مجله آموزشی
          </h3>
          <a href="<?php echo esc_url($blog_url); ?>" class="inline-flex items-center gap-1.5 px-2.5 py-2.5 -my-2.5 rounded-xl text-[13px] font-bold text-ink-600 hover:text-ink-900 hover:bg-sand transition-colors shrink-0">
            همه مقالات
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
          </a>
        </div>

        <ul class="mt-5 space-y-4 flex-1">
          <?php foreach ($articles as $art) :
              $cats = get_the_category($art->ID);
              $cat  = !empty($cats) ? $cats[0]->name : 'آموزش';
          ?>
          <li>
            <a href="<?php echo esc_url(get_permalink($art->ID)); ?>" class="group block">
              <span class="flex items-center gap-2 text-xs font-bold text-violet-700">
                <span class="rounded-full bg-violet-100 px-2.5 py-1"><?php echo esc_html($cat); ?></span>
              </span>
              <span class="block mt-2 text-[14.5px] font-bold leading-6 group-hover:text-ink-600 transition-colors"><?php echo esc_html($art->post_title); ?></span>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <?php endif; ?>

    <!-- کپسول اولترا (رایگان) -->
    <?php if ($capsule) : ?>
    <div class="lg:col-span-3 min-w-0">
      <a href="<?php echo esc_url($capsule['url']); ?>" class="group h-full rounded-3xl overflow-hidden border border-violet-300 bg-violet-100/60 flex flex-col hover:shadow-lift transition-all">
        <div class="relative aspect-[16/10] overflow-hidden">
          <img src="<?php echo esc_url($capsule['thumb']); ?>" alt="<?php echo esc_attr($capsule['title']); ?>" loading="lazy" decoding="async" class="size-full object-cover object-top transition-transform duration-500 group-hover:scale-105" />
        </div>
        <div class="p-5 flex flex-col flex-1">
          <?php if ($capsule['is_free']) : ?>
          <span class="self-start rounded-full bg-mint-600 text-white text-xs font-black px-2.5 py-1">رایگان</span>
          <?php endif; ?>
          <h3 class="mt-3 text-lg font-extrabold leading-7"><?php echo esc_html($capsule['title']); ?></h3>
          <p class="mt-2 fa-line text-[13px] text-muted-foreground flex-1"><?php echo esc_html($capsule['excerpt']); ?></p>
          <span class="mt-4 inline-flex items-center gap-1.5 text-sm font-bold text-violet-800 group-hover:gap-2.5 transition-all">
            شروع کن
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
          </span>
        </div>
      </a>
    </div>
    <?php endif; ?>

  </div>
</section>
