<?php

if (!defined('ABSPATH')) {
    exit;
}

$eyebrow     = nasim_mod('hero_eyebrow') ?: 'آکادمی نیلوفر وفایی';
$title       = nasim_mod('hero_title') ?: 'از علاقه به طراحی سایت،';
$highlight   = nasim_mod('hero_title_highlight') ?: 'آینده حرفه‌ای';
$description = nasim_mod('hero_description') ?: 'اینجا فقط طراحی سایت یاد نمی‌گیری؛ مهارت می‌سازی، پروژه واقعی اجرا می‌کنی و برای ورود به بازار کار آماده می‌شی.';
$courses_url = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/');
$about_url   = function_exists('nasim_page_url') ? nasim_page_url('about') : home_url('/about/');
$primary_txt = nasim_mod('hero_primary_text') ?: 'مشاهده دوره‌ها';
$primary_url = nasim_url(nasim_mod('hero_primary_url')) ?: $courses_url;
$second_txt  = nasim_mod('hero_secondary_text') ?: 'آشنایی با نیلوفر';
$second_url  = nasim_url(nasim_mod('hero_secondary_url')) ?: $about_url;
$hero_img    = nasim_mod('hero_image') ?: (THEME_URL . '/assets/images/brand/portrait-3.jpg');
?>

<!-- ۱) Hero -->
<section class="relative overflow-hidden" aria-label="معرفی آکادمی">
  <div class="absolute inset-0 dot-grid opacity-60" aria-hidden="true"></div>
  <div class="absolute -top-32 -start-32 size-96 rounded-full bg-violet-200/50 blur-3xl" aria-hidden="true"></div>
  <div class="absolute top-40 -end-40 size-[28rem] rounded-full bg-mint-200/40 blur-3xl" aria-hidden="true"></div>

  <div class="container-site relative pt-10 pb-14 md:pt-16 md:pb-20 grid lg:grid-cols-12 gap-12 items-center">
    <div class="lg:col-span-6 order-1 min-w-0">
      <span class="inline-flex items-center gap-2 rounded-full border border-violet-300 bg-violet-100 px-4 py-1.5 text-[13px] font-bold text-violet-800">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4" aria-hidden="true"><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/></svg>
        <?php echo esc_html($eyebrow); ?>
      </span>

      <h1 class="mt-6 text-[1.9rem] leading-[1.5] md:text-[2.7rem] md:leading-[1.45] font-black text-balance">
        <?php echo esc_html($title); ?><br>تا ساختن <span class="mark-violet"><?php echo esc_html($highlight); ?></span> خودت
      </h1>

      <p class="mt-5 fa-line text-[15px] md:text-lg text-muted-foreground max-w-xl">
        <?php echo esc_html($description); ?>
      </p>

      <div class="mt-8 flex flex-wrap gap-3">
        <a href="<?php echo esc_url($primary_url); ?>" class="btn btn-violet btn-lg rounded-full shadow-[0_8px_20px_-8px_rgba(147,145,255,0.6)] font-bold">
          <?php echo esc_html($primary_txt); ?>
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4.5" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
        </a>
        <a href="<?php echo esc_url($second_url); ?>" class="btn btn-outline btn-lg rounded-full font-bold bg-background">
          <?php echo esc_html($second_txt); ?>
        </a>
      </div>

      <div class="mt-9 flex flex-wrap items-center gap-x-6 gap-y-2 text-[13px] font-semibold text-foreground/70">
        <span class="inline-flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4.5 text-violet-600" aria-hidden="true"><path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/></svg>
          ۱۰ سال فعالیت در حوزه وب
        </span>
        <span class="inline-flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4.5 text-violet-600" aria-hidden="true"><path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"/><path d="M22 10v6"/><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"/></svg>
          تدریس از سال ۱۴۰۰
        </span>
        <span class="inline-flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4.5 text-violet-600" aria-hidden="true"><path d="M18 21a8 8 0 0 0-16 0"/><circle cx="10" cy="8" r="5"/><path d="M22 20c0-3.37-2-6.5-4-8a5 5 0 0 0-.45-8.3"/></svg>
          ۳۲۹ دانشجوی دوره جامع اولترا وب
        </span>
      </div>
    </div>

    <!-- تصویر هیرو و کارت‌های شناور -->
    <div class="lg:col-span-6 order-2 min-w-0">
      <div class="relative mx-auto max-w-[520px]">
        <div class="relative">
          <div class="absolute -inset-3 rounded-[2.5rem] bg-ink-900 rotate-2" aria-hidden="true"></div>
          <div class="absolute -inset-3 rounded-[2.5rem] border-2 border-violet-400 -rotate-1" aria-hidden="true"></div>

          <div class="relative overflow-hidden rounded-[2.5rem] aspect-[4/3] sm:aspect-[4/3.4] lg:aspect-[5/5.2] bg-ink-900">
            <img
              src="<?php echo esc_url($hero_img); ?>"
              alt="نیلوفر وفایی؛ مدرس، طراح و توسعه‌دهنده وب"
              width="1040"
              height="1080"
              fetchpriority="high"
              decoding="async"
              class="absolute inset-0 w-full h-full object-cover object-top"
            />
            <div class="absolute inset-x-0 bottom-0 h-28 bg-gradient-to-t from-ink-950/70 to-transparent" aria-hidden="true"></div>
            <div class="absolute bottom-4 start-5 text-cream">
              <p class="font-extrabold text-lg">نیلوفر وفایی</p>
              <p class="text-xs font-semibold text-cream/90 mt-0.5">طراح، توسعه‌دهنده وب و مدرس</p>
            </div>
          </div>

          <div class="hidden md:block absolute -end-8 top-10 rounded-2xl bg-card border border-border shadow-lift p-3.5 w-44 animate-float" aria-hidden="true">
            <div class="flex items-center gap-1.5 mb-2.5">
              <span class="size-2.5 rounded-full bg-[#F26D6D]"></span>
              <span class="size-2.5 rounded-full bg-[#F2C94C]"></span>
              <span class="size-2.5 rounded-full bg-violet-400"></span>
            </div>
            <p class="text-xs font-bold text-foreground">پروژه ۵؛ سایت فروشگاهی</p>
            <div class="mt-2 h-2 rounded-full bg-sand overflow-hidden">
              <div class="h-full w-[86%] rounded-full bg-gradient-to-l from-violet-400 to-mint-300"></div>
            </div>
            <p class="mt-1.5 text-xs font-semibold text-muted-foreground">تکمیل ۸۶٪ — تحویل به کارفرما</p>
          </div>

          <div class="hidden md:block absolute -start-8 bottom-24 rounded-2xl bg-ink-900 text-cream shadow-lift p-3.5 w-40 animate-float-slow" aria-hidden="true">
            <p class="text-xs font-semibold text-ink-300">دوره جامع اولترا وب</p>
            <p class="mt-1 font-extrabold text-[15px]">۱۳ فصل • ۵۳ ساعت</p>
            <p class="mt-1.5 text-xs font-semibold text-violet-300">۳۲۹ دانشجوی فعال</p>
          </div>

          <div class="absolute top-2 start-6 rounded-full bg-violet-400 text-ink-950 px-4 py-2 text-xs font-black shadow-lift animate-float" aria-hidden="true" style="animation-delay: 1.1s;">
            مهارت ← پروژه ← درآمد
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
