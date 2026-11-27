<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * کارت تیره «رادیو نیل» (آخرین قسمت‌ها).
 * استفاده: بخش «محتوای رایگان» صفحه اصلی و ویجت رادیو نیل المنتور.
 * پارامتر اختیاری: $args['count'] (پیش‌فرض ۳)
 */
$radio_cat_slug = nasim_mod('radio_cat') ?: 'radionil';
$more_url       = function_exists('nasim_radio_url') ? nasim_radio_url() : home_url('/blog/');
$count          = isset($args['count']) ? max(1, min(6, (int) $args['count'])) : 3;

$episodes = get_posts([
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => $count,
    'category_name'  => $radio_cat_slug,
    'no_found_rows'  => true,
]);

if (!$episodes) {
    return;
}
?>

<div class="h-full rounded-3xl bg-ink-900 text-cream p-6 md:p-7 flex flex-col">
  <div class="flex items-center justify-between gap-3">
    <h3 class="text-xl font-extrabold flex items-center gap-2.5">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5.5 text-violet-300" aria-hidden="true"><path d="M12 19v3"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><rect x="9" y="2" width="6" height="13" rx="3"/></svg>
      رادیو نیل
    </h3>
    <a href="<?php echo esc_url($more_url); ?>" class="inline-flex items-center gap-1.5 px-2.5 py-2.5 -my-2.5 rounded-xl text-[13px] font-bold text-violet-300 hover:text-violet-200 hover:bg-ink-800 transition-colors shrink-0">
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
