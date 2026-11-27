<?php

if (!defined('ABSPATH')) {
    exit;
}

$eyebrow   = nasim_mod('about_eyebrow') ?: 'درباره مدرس';
$title     = nasim_mod('about_title') ?: 'سلام، من';
$highlight = nasim_mod('about_title_highlight') ?: 'نیلوفر وفایی هستم';
$text      = nasim_mod('about_text') ?: 'مدرس و توسعه‌دهنده وب، بنیان‌گذار وبلبز و آکادمی اولترا. تجربه‌ام در آموزش، طراحی و اجرای پروژه‌های واقعی کمک می‌کند مسیر ورود شما به بازار کار را هموارتر کنیم.';
$img       = nasim_mod('about_image') ?: (THEME_URL . '/assets/images/brand/portrait-2.jpg');
$btn_text  = nasim_mod('about_button_text') ?: 'بیشتر درباره نیلوفر';
$btn_url   = nasim_url(nasim_mod('about_button_url')) ?: (function_exists('nasim_default_page_url') ? nasim_default_page_url('about', home_url('/about/')) : home_url('/about/'));

$stats = [
    [nasim_mod('about_stat_1_value') ?: 'وبلبز', nasim_mod('about_stat_1_label') ?: 'محصولات وردپرس'],
    [nasim_mod('about_stat_2_value') ?: 'اولترا', nasim_mod('about_stat_2_label') ?: 'مسیر مهارت تا پروژه'],
    [nasim_mod('about_stat_3_value') ?: 'U3P', nasim_mod('about_stat_3_label') ?: 'متد بازارمحور'],
];
?>

<section id="about" class="bg-ink-950 text-cream relative overflow-hidden" aria-label="معرفی مدرس">
  <div class="absolute inset-0 dot-grid-light opacity-35" aria-hidden="true"></div>
  <div class="absolute -bottom-32 -end-32 size-96 rounded-full bg-violet-400/15 blur-3xl" aria-hidden="true"></div>

  <div class="container-site relative py-16 md:py-22 grid lg:grid-cols-12 gap-10 items-center">
    <div class="lg:col-span-5 reveal">
      <div class="relative max-w-md mx-auto">
        <div class="rounded-[2rem] overflow-hidden border border-ink-700 shadow-lift aspect-square bg-ink-900">
          <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($highlight); ?>" width="960" height="960" loading="lazy" decoding="async" class="w-full h-full object-cover" />
        </div>
        <span class="hidden sm:inline-flex items-center gap-2 absolute -bottom-4 start-6 rounded-full bg-violet-400 text-ink-950 px-4 py-1.5 text-xs font-black tracking-wide" dir="ltr">
          <?php echo esc_html(nasim_mod('about_sticker') ?: 'DESIGN · DEVELOP · EDUCATE'); ?>
        </span>
      </div>
    </div>

    <div class="lg:col-span-7 reveal min-w-0" style="--reveal-delay: 80ms">
      <span class="inline-flex items-center gap-2 rounded-full border border-ink-700 bg-ink-900 px-3.5 py-1.5 text-[13px] font-bold text-violet-300">
        <?php echo esc_html($eyebrow); ?>
      </span>

      <h2 class="mt-4 text-2xl md:text-4xl font-black leading-[1.45]">
        <?php echo esc_html($title); ?>
        <?php if ($highlight) : ?> <span class="text-violet-300"><?php echo esc_html($highlight); ?></span><?php endif; ?>
      </h2>

      <p class="mt-5 fa-line text-[15px] md:text-base text-ink-200 max-w-2xl">
        <?php echo esc_html($text); ?>
      </p>

      <div class="mt-7 grid grid-cols-3 gap-3.5 max-w-xl">
        <?php foreach ($stats as $st) : ?>
        <div class="rounded-2xl border border-ink-800 bg-ink-900/80 p-4">
          <p class="text-lg font-black text-violet-300"><?php echo esc_html($st[0]); ?></p>
          <p class="mt-1 text-xs font-semibold text-ink-300"><?php echo esc_html($st[1]); ?></p>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="mt-8">
        <a href="<?php echo esc_url($btn_url); ?>" class="btn btn-violet btn-lg rounded-full font-bold">
          <?php echo esc_html($btn_text); ?>
          <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
        </a>
      </div>
    </div>
  </div>
</section>
