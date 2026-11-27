<?php

if (!defined('ABSPATH')) {
    exit;
}

$course_id = (int) ($args['course_id'] ?? 0);

if (!$course_id || !function_exists('nasim_bundles_for_course') || nasim_user_has_course_access($course_id)) {
    return;
}

foreach (nasim_bundles_for_course($course_id) as $bundle) {
    $savings = nasim_bundle_savings($bundle->ID);
    if (nasim_bundle_price($bundle->ID) <= 0) {
        continue;
    }
    ?>
<div class="mt-6 rounded-2xl border border-violet-300 bg-violet-100/60 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
  <div class="flex items-start gap-3">
    <span class="grid place-items-center size-10 rounded-xl bg-ink-900 text-violet-300 shrink-0" aria-hidden="true">
      <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"/><path d="M2 12a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 12"/><path d="M2 17a1 1 0 0 0 .58.91l8.6 3.91a2 2 0 0 0 1.65 0l8.58-3.9A1 1 0 0 0 22 17"/></svg>
    </span>
    <div>
      <strong class="block text-sm font-extrabold text-ink-950"><?php echo esc_html(sprintf('این دوره در پکیج «%s» هم هست', $bundle->post_title)); ?></strong>
      <small class="block mt-0.5 text-xs font-bold text-violet-800"><?php echo esc_html(sprintf('%s دوره با %s', nasim_fa_digits(count(nasim_bundle_course_ids($bundle->ID))), $savings['percent'] ? nasim_fa_digits($savings['percent']) . '٪ تخفیف' : nasim_format_price(nasim_bundle_price($bundle->ID)))); ?></small>
    </div>
  </div>
  <a class="btn btn-violet btn-sm rounded-full font-bold shrink-0" href="<?php echo esc_url(get_permalink($bundle)); ?>">مشاهده پکیج</a>
</div>
    <?php
    break;
}
