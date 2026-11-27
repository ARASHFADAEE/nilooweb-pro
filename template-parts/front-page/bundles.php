<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('nasim_get_bundles')) {
    return;
}

$bundles = nasim_get_bundles(3);
if (!$bundles) {
    return;
}

$archive_url = function_exists('nasim_bundles_archive_url') ? nasim_bundles_archive_url() : home_url('/bundles/');
?>

<section id="bundles" class="container-site py-16 md:py-20" aria-label="پکیج‌های آموزشی">
  <div class="reveal flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10">
    <div>
      <span class="inline-flex items-center gap-2 rounded-full border border-violet-300 bg-violet-100 px-3.5 py-1.5 text-[13px] font-bold text-violet-800">
        پکیج‌های اقتصادی
      </span>
      <h2 class="mt-4 text-2xl md:text-3xl font-black">چند دوره مکمل، <span class="mark-violet">با قیمت کمتر</span></h2>
      <p class="mt-2.5 fa-line text-sm md:text-[15px] text-muted-foreground max-w-xl">
        یک‌بار ثبت‌نام کن و به تمام دوره‌های پکیج دسترسی دائمی و لایسنس مشترک اسپات‌پلیر بگیر.
      </p>
    </div>
    <a href="<?php echo esc_url($archive_url); ?>" class="btn btn-outline rounded-full font-bold self-start md:self-auto">
      همه پکیج‌ها
      <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
    </a>
  </div>

  <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($bundles as $bundle) : ?>
      <?php get_template_part('template-parts/bundle/card', null, ['bundle' => $bundle]); ?>
    <?php endforeach; ?>
  </div>
</section>
