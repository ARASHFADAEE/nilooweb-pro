<?php

if (!defined('ABSPATH')) {
    exit;
}

$eyebrow     = nasim_mod('hero_eyebrow') ?: 'آکادمی نیلوفر وفایی';
$title       = nasim_mod('hero_title') ?: 'از علاقه به طراحی سایت،';
$highlight   = nasim_mod('hero_title_highlight') ?: 'آینده حرفه‌ای';
$description = nasim_mod('hero_description') ?: 'اینجا فقط طراحی سایت یاد نمی‌گیری؛ مهارت می‌سازی، پروژه واقعی اجرا می‌کنی و برای ورود به بازار کار آماده می‌شی.';
$courses_url = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/');
$primary_txt = nasim_mod('hero_primary_text') ?: 'مشاهده دوره‌ها';
$primary_url = nasim_url(nasim_mod('hero_primary_url')) ?: $courses_url;
$second_txt  = nasim_mod('hero_secondary_text') ?: 'شروع رایگان با کپسول اولترا';
$second_url  = nasim_url(nasim_mod('hero_secondary_url')) ?: $courses_url;
$hero_img    = nasim_mod('hero_image') ?: (THEME_URL . '/assets/images/brand/portrait-1.jpg');
$stats       = function_exists('nasim_home_stats') ? nasim_home_stats() : ['courses' => 0, 'lessons' => 0, 'hours' => 0];
?>

<!-- ۱) Hero -->
<section class="relative overflow-hidden" aria-label="معرفی آکادمی">
  <div class="absolute inset-0 dot-grid opacity-60" aria-hidden="true"></div>
  <div class="absolute -top-32 -start-32 size-96 rounded-full bg-violet-200/50 blur-3xl" aria-hidden="true"></div>
  <div class="absolute top-40 -end-40 size-[28rem] rounded-full bg-mint-200/40 blur-3xl" aria-hidden="true"></div>

  <div class="container-site relative pt-10 pb-14 md:pt-16 md:pb-20 grid lg:grid-cols-12 gap-12 items-center">
    <div class="lg:col-span-6 order-1 min-w-0">
      <span class="inline-flex items-center gap-2 rounded-full border border-violet-300 bg-violet-100 px-4 py-1.5 text-[13px] font-bold text-violet-800">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/></svg>
        <?php echo esc_html($eyebrow); ?>
      </span>

      <h1 class="mt-6 text-[1.9rem] leading-[1.5] md:text-[2.7rem] md:leading-[1.45] font-black text-balance">
        <?php echo esc_html($title); ?>
        <?php if ($highlight) : ?>
        <br />
        <span class="mark-violet"><?php echo esc_html($highlight); ?></span>
        <?php endif; ?>
      </h1>

      <p class="mt-5 fa-line text-[15px] md:text-lg text-muted-foreground max-w-xl">
        <?php echo esc_html($description); ?>
      </p>

      <div class="mt-8 flex flex-wrap gap-3">
        <a href="<?php echo esc_url($primary_url); ?>" class="btn btn-violet btn-lg rounded-full">
          <?php echo esc_html($primary_txt); ?>
          <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
        </a>
        <a href="<?php echo esc_url($second_url); ?>" class="btn btn-outline btn-lg rounded-full font-bold">
          <?php echo esc_html($second_txt); ?>
        </a>
      </div>

      <!-- آمار زنده آکادمی -->
      <div class="mt-9 flex flex-wrap items-center gap-x-6 gap-y-3 text-[13px] font-bold text-foreground/80">
        <?php if (!empty($stats['courses'])) : ?>
        <span class="inline-flex items-center gap-2">
          <span class="size-2 rounded-full bg-violet-500"></span>
          <?php echo esc_html(nasim_fa_digits($stats['courses'])); ?> دوره تخصصی و کاربردی
        </span>
        <?php endif; ?>
        <?php if (!empty($stats['hours'])) : ?>
        <span class="inline-flex items-center gap-2">
          <span class="size-2 rounded-full bg-mint-500"></span>
          بیش از <?php echo esc_html(nasim_fa_digits($stats['hours'])); ?> ساعت آموزش ویدیویی
        </span>
        <?php endif; ?>
        <span class="inline-flex items-center gap-2">
          <span class="size-2 rounded-full bg-ink-700"></span>
          پشتیبانی مستقیم تا ورود به بازار کار
        </span>
      </div>
    </div>

    <!-- تصویر هیرو -->
    <div class="lg:col-span-6 order-2 min-w-0">
      <div class="relative mx-auto max-w-lg">
        <div class="relative rounded-[2rem] overflow-hidden border border-border bg-card shadow-lift aspect-square">
          <img src="<?php echo esc_url($hero_img); ?>" alt="<?php echo esc_attr($eyebrow); ?>" width="1024" height="1024" fetchpriority="high" decoding="async" class="w-full h-full object-cover" />
        </div>

        <div class="hidden sm:flex items-center gap-3 absolute -bottom-5 -start-5 rounded-2xl border border-border bg-card/95 backdrop-blur px-4 py-3 shadow-lift animate-float">
          <span class="grid place-items-center size-10 rounded-xl bg-violet-100 text-violet-700">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21V7"/><path d="m16 12 2 2 4-4"/><path d="M22 6V4a1 1 0 0 0-1-1h-5a4 4 0 0 0-4 4 4 4 0 0 0-4-4H3a1 1 0 0 0-1 1v13a1 1 0 0 0 1 1h6a3 3 0 0 1 3 3 3 3 0 0 1 3-3h6a1 1 0 0 0 1-1v-1.3"/></svg>
          </span>
          <div>
            <p class="text-xs font-black text-foreground"><?php echo esc_html(nasim_mod('hero_note_top_title') ?: 'پروژه‌محور و عملی'); ?></p>
            <p class="text-[11.5px] font-semibold text-muted-foreground"><?php echo esc_html(nasim_mod('hero_note_top_text') ?: 'یادگیری با ساخت سایت واقعی'); ?></p>
          </div>
        </div>

        <div class="hidden sm:flex items-center gap-3 absolute top-6 -end-5 rounded-2xl border border-ink-700 bg-ink-900 text-cream px-4 py-3 shadow-lift animate-float-slow">
          <span class="grid place-items-center size-10 rounded-xl bg-violet-400 text-ink-950">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
          </span>
          <div>
            <p class="text-xs font-black"><?php echo esc_html(nasim_mod('hero_note_bottom_title') ?: 'پشتیبانی مستقیم'); ?></p>
            <p class="text-[11.5px] font-semibold text-ink-300"><?php echo esc_html(nasim_mod('hero_note_bottom_text') ?: 'همراهی تا اولین پروژه'); ?></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ۲) نوار اعتبار و تجربه -->
<section class="border-y border-border bg-card/70" aria-label="اعتبار و تجربه آکادمی">
  <div class="container-site py-8">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
      <div class="reveal flex items-center gap-3.5">
        <span class="grid place-items-center size-11 rounded-2xl bg-violet-100 text-violet-700 shrink-0 font-black text-base">+۱۰</span>
        <div>
          <p class="text-sm font-extrabold">سال تجربه وب</p>
          <p class="text-xs font-semibold text-muted-foreground">فعالیت مستمر از ۱۳۹۵</p>
        </div>
      </div>
      <div class="reveal flex items-center gap-3.5" style="--reveal-delay: 60ms">
        <span class="grid place-items-center size-11 rounded-2xl bg-mint-100 text-mint-700 shrink-0 font-black text-base">U3P</span>
        <div>
          <p class="text-sm font-extrabold">متد بازارمحور</p>
          <p class="text-xs font-semibold text-muted-foreground">مهارت + رزومه + پروژه‌گیری</p>
        </div>
      </div>
      <div class="reveal flex items-center gap-3.5" style="--reveal-delay: 120ms">
        <span class="grid place-items-center size-11 rounded-2xl bg-violet-100 text-violet-700 shrink-0 font-black text-base">VIP</span>
        <div>
          <p class="text-sm font-extrabold">پشتیبانی مستقیم</p>
          <p class="text-xs font-semibold text-muted-foreground">تیکت اختصاصی و گروه دانشجویان</p>
        </div>
      </div>
      <div class="reveal flex items-center gap-3.5" style="--reveal-delay: 180ms">
        <span class="grid place-items-center size-11 rounded-2xl bg-ink-900 text-violet-300 shrink-0 font-black text-base">∞</span>
        <div>
          <p class="text-sm font-extrabold">دسترسی و آپدیت دائمی</p>
          <p class="text-xs font-semibold text-muted-foreground">به‌روزرسانی رایگان دوره‌ها</p>
        </div>
      </div>
    </div>
  </div>
</section>
