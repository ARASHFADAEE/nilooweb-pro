<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Bundle card (Tailwind v4 design system)
 */
$bundle    = isset($args['bundle']) && $args['bundle'] instanceof WP_Post ? $args['bundle'] : get_post();
if (!$bundle) {
    return;
}
$bundle_id = (int) $bundle->ID;
$courses   = nasim_bundle_courses($bundle_id);
$price     = nasim_bundle_price($bundle_id);
$regular   = nasim_bundle_regular_price($bundle_id);
$savings   = nasim_bundle_savings($bundle_id);
$stats     = nasim_bundle_stats($bundle_id);
$badge     = (string) get_post_meta($bundle_id, '_nasim_bundle_badge', true);
$owned     = nasim_user_owns_bundle($bundle_id);
$url       = get_permalink($bundle_id);
$thumb     = get_the_post_thumbnail_url($bundle_id, 'medium_large');
?>
<article class="reveal card-hover flex h-full flex-col rounded-3xl border <?php echo $badge ? 'border-violet-400 ring-1 ring-violet-300/60' : 'border-border'; ?> bg-card p-6 shadow-soft">
  <div class="flex items-center justify-between gap-3">
    <div class="flex items-center -space-x-2 space-x-reverse" aria-hidden="true">
      <?php if ($thumb) : ?>
      <img src="<?php echo esc_url($thumb); ?>" alt="" loading="lazy" decoding="async" width="56" height="56" class="size-12 rounded-2xl object-cover border border-border" />
      <?php else : ?>
        <?php foreach (array_slice($courses, 0, 3) as $course) :
            $cover = function_exists('nasim_niloo_course_image_url') ? nasim_niloo_course_image_url($course->ID, 'thumbnail') : get_the_post_thumbnail_url($course->ID, 'thumbnail');
        ?>
        <span class="grid place-items-center size-11 rounded-2xl bg-ink-900 text-cream text-xs font-black border-2 border-card overflow-hidden">
          <?php if ($cover) : ?>
          <img src="<?php echo esc_url($cover); ?>" alt="" loading="lazy" width="44" height="44" class="w-full h-full object-cover" />
          <?php else : ?>
          <?php echo esc_html(mb_substr($course->post_title, 0, 1)); ?>
          <?php endif; ?>
        </span>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <?php if ($badge) : ?>
    <span class="rounded-full bg-violet-400 text-ink-950 text-xs font-black px-3 py-1"><?php echo esc_html($badge); ?></span>
    <?php elseif (!empty($savings['percent'])) : ?>
    <span class="rounded-full bg-mint-100 text-mint-700 border border-mint-200 text-xs font-black px-3 py-1"><?php echo esc_html(nasim_fa_digits($savings['percent']) . '٪ صرفه‌جویی'); ?></span>
    <?php endif; ?>
  </div>

  <h3 class="mt-4 text-lg font-extrabold leading-snug">
    <a href="<?php echo esc_url($url); ?>" class="hover:text-violet-700 transition-colors"><?php echo esc_html(get_the_title($bundle_id)); ?></a>
  </h3>

  <p class="mt-1.5 text-xs font-bold text-muted-foreground">
    <?php echo esc_html(implode(' · ', array_filter([
        nasim_fa_digits($stats['courses']) . ' دوره',
        $stats['lessons'] ? nasim_fa_digits($stats['lessons']) . ' جلسه' : '',
        $stats['seconds'] ? nasim_format_total_duration($stats['seconds']) : '',
    ]))); ?>
  </p>

  <?php if ($courses) : ?>
  <ul class="mt-4 space-y-2 flex-1">
    <?php foreach (array_slice($courses, 0, 4) as $course) : ?>
    <li class="flex items-start gap-2 text-[13px] font-semibold text-foreground/85">
      <svg class="size-4 text-violet-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
      <span class="line-clamp-1"><?php echo esc_html($course->post_title); ?></span>
    </li>
    <?php endforeach; ?>
    <?php if (count($courses) > 4) : ?>
    <li class="text-xs font-bold text-violet-700">+ <?php echo esc_html(nasim_fa_digits(count($courses) - 4)); ?> دوره دیگر</li>
    <?php endif; ?>
  </ul>
  <?php endif; ?>

  <div class="mt-6 pt-4 border-t border-dashed border-border flex items-center justify-between gap-3">
    <div>
      <?php if ($owned) : ?>
      <span class="text-xs font-black text-mint-700">در حساب شما فعال است</span>
      <?php else : ?>
        <?php if (!empty($savings['amount'])) : ?>
        <del class="block text-xs font-semibold text-muted-foreground"><?php echo esc_html(nasim_fa_digits(number_format($regular))); ?> تومان</del>
        <?php endif; ?>
      <strong class="text-base font-black text-foreground"><?php echo esc_html(nasim_fa_digits(number_format($price))); ?> <span class="text-xs font-bold">تومان</span></strong>
      <?php endif; ?>
    </div>
    <a class="btn <?php echo $owned ? 'btn-outline' : 'btn-violet'; ?> btn-sm rounded-full font-bold" href="<?php echo esc_url($url); ?>">
      <?php echo $owned ? 'مشاهده' : 'جزئیات و خرید'; ?>
    </a>
  </div>
</article>
