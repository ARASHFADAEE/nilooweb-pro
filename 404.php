<?php

if (!defined('ABSPATH')) {
    exit;
}

$courses_url = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/');
$blog_url    = function_exists('nasim_blog_url') ? nasim_blog_url() : home_url('/blog/');

get_header();
?>

  <main id="main-content" class="flex-1 flex items-center">
    <section class="relative w-full overflow-hidden py-16 md:py-24">
      <div class="absolute inset-0 dot-grid opacity-50" aria-hidden="true"></div>
      <div class="absolute -top-24 -start-24 size-80 rounded-full bg-violet-200/50 blur-3xl" aria-hidden="true"></div>

      <div class="container-site relative text-center max-w-2xl mx-auto">
        <span class="inline-flex items-center justify-center rounded-full border border-violet-300 bg-violet-100 px-4 py-1.5 text-xs font-black text-violet-800">خطای ۴۰۴</span>
        <p class="mt-4 text-6xl md:text-8xl font-black text-ink-900 tracking-tight" aria-hidden="true">۴۰۴</p>
        <h1 class="mt-4 text-2xl md:text-3xl font-black text-foreground">این صفحه پیدا نشد</h1>
        <p class="mt-3 fa-line text-[15px] text-muted-foreground">
          ممکن است آدرس اشتباه وارد شده باشد یا این صفحه جابه‌جا شده باشد. می‌توانی جستجو کنی یا به یکی از بخش‌های اصلی برگردی.
        </p>

        <form class="mt-7 flex flex-col sm:flex-row gap-2.5 max-w-md mx-auto" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
          <input type="search" name="s" placeholder="دنبال چه دوره یا مقاله‌ای هستی؟" aria-label="جستجو در سایت" class="field flex-1" />
          <button type="submit" class="btn btn-violet rounded-xl px-6">جستجو</button>
        </form>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
          <a class="btn btn-default rounded-full" href="<?php echo esc_url(home_url('/')); ?>">بازگشت به خانه</a>
          <a class="btn btn-outline rounded-full" href="<?php echo esc_url($courses_url); ?>">مشاهده دوره‌ها</a>
          <a class="btn btn-outline rounded-full" href="<?php echo esc_url($blog_url); ?>">مجله آموزشی</a>
        </div>
      </div>
    </section>
  </main>

<?php
get_footer();
