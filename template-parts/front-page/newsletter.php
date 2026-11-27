<?php

if (!defined('ABSPATH')) {
    exit;
}

$courses_url = function_exists('nasim_courses_listing_url') ? nasim_courses_listing_url() : home_url('/courses/');
$contact_url = function_exists('nasim_default_page_url') ? nasim_default_page_url('contact', home_url('/contact/')) : home_url('/contact/');
$eyebrow     = nasim_mod('cta_eyebrow') ?: 'شروع مسیر حرفه‌ای';
$title       = nasim_mod('cta_title') ?: 'آماده‌ای اولین قدم را برای ورود به بازار طراحی سایت برداری؟';
$text        = nasim_mod('cta_text') ?: 'اگر هنوز مطمئن نیستی کدام دوره برایت مناسب است، می‌توانی مشاوره بگیری یا مستقیم با دوره‌های رایگان شروع کنی.';
$btn_text    = nasim_mod('cta_button_text') ?: 'مشاوره رایگان';
$btn_url     = nasim_url(nasim_mod('cta_button_url')) ?: $contact_url;
?>

<section class="container-site py-16 md:py-20" aria-label="دعوت به اقدام">
  <div class="reveal relative overflow-hidden rounded-[2rem] bg-ink-900 text-cream p-8 md:p-14 text-center">
    <div class="absolute inset-0 dot-grid-light opacity-35" aria-hidden="true"></div>
    <div class="absolute -top-24 -start-24 size-72 rounded-full bg-violet-400/20 blur-3xl" aria-hidden="true"></div>
    <div class="absolute -bottom-24 -end-24 size-72 rounded-full bg-mint-300/15 blur-3xl" aria-hidden="true"></div>

    <div class="relative max-w-2xl mx-auto">
      <span class="inline-flex items-center gap-2 rounded-full border border-ink-700 bg-ink-800 px-3.5 py-1.5 text-xs font-bold text-violet-300">
        <?php echo esc_html($eyebrow); ?>
      </span>
      <h2 class="mt-4 text-2xl md:text-3xl font-black leading-[1.5]">
        <?php echo esc_html($title); ?>
      </h2>
      <p class="mt-3 fa-line text-sm md:text-[15px] text-ink-200">
        <?php echo esc_html($text); ?>
      </p>
      <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
        <a href="<?php echo esc_url($courses_url); ?>" class="btn btn-violet btn-lg rounded-full font-black">
          مشاهده دوره‌ها
          <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
        </a>
        <a href="<?php echo esc_url($btn_url); ?>" class="btn btn-cream btn-lg rounded-full font-bold">
          <?php echo esc_html($btn_text); ?>
        </a>
      </div>
    </div>
  </div>
</section>
