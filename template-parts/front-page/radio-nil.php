<?php

if (!defined('ABSPATH')) {
    exit;
}

$radio_cat = nasim_mod('radio_cat') ?: 'radionil';
$more_url  = function_exists('nasim_radio_url') ? nasim_radio_url() : home_url('/blog/');

$episodes = get_posts([
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 3,
    'category_name'  => $radio_cat,
    'no_found_rows'  => true,
]);

if (!$episodes) {
    return;
}
?>

<section id="radio-nil" class="container-site py-16 md:py-20" aria-label="پادکست رادیو نیل">
  <div class="reveal flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10">
    <div>
      <span class="inline-flex items-center gap-2 rounded-full bg-ink-900 text-violet-300 px-3.5 py-1.5 text-[13px] font-bold">
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 19v3"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><rect x="9" y="2" width="6" height="13" rx="3"/></svg>
        <?php echo esc_html(nasim_mod('radio_eyebrow') ?: 'رادیو نیل'); ?>
      </span>
      <h2 class="mt-4 text-2xl md:text-3xl font-black">
        <?php echo esc_html(nasim_mod('radio_title') ?: 'پادکست و گفتگو'); ?>
        <?php if (nasim_mod('radio_title_highlight')) : ?>
        <span class="mark-violet"><?php echo esc_html(nasim_mod('radio_title_highlight')); ?></span>
        <?php endif; ?>
      </h2>
    </div>
    <a href="<?php echo esc_url($more_url); ?>" class="btn btn-outline rounded-full font-bold self-start md:self-auto">
      همه قسمت‌های رادیو نیل
      <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
    </a>
  </div>

  <div class="grid md:grid-cols-3 gap-5">
    <?php foreach ($episodes as $i => $ep) : ?>
    <article class="reveal rounded-3xl border border-border bg-card p-5 md:p-6 shadow-soft flex flex-col" style="--reveal-delay:<?php echo esc_attr($i * 70); ?>ms">
      <div class="flex items-start gap-3.5">
        <a href="<?php echo esc_url(get_permalink($ep->ID)); ?>" aria-label="<?php echo esc_attr('شنیدن ' . $ep->post_title); ?>" class="grid place-items-center size-12 rounded-2xl shrink-0 bg-ink-900 text-violet-300 hover:bg-ink-800 transition-colors">
          <svg class="size-5 me-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="6 3 20 12 6 21 6 3"/></svg>
        </a>
        <div class="min-w-0 flex-1">
          <span class="text-xs font-bold text-muted-foreground"><?php echo esc_html(get_the_date('', $ep->ID)); ?></span>
          <h3 class="mt-1 text-[15px] font-extrabold leading-6">
            <a href="<?php echo esc_url(get_permalink($ep->ID)); ?>" class="hover:text-violet-700 transition-colors"><?php echo esc_html($ep->post_title); ?></a>
          </h3>
        </div>
      </div>

      <p class="mt-3 fa-line text-[13px] text-muted-foreground line-clamp-3 flex-1">
        <?php echo esc_html(wp_strip_all_tags(get_the_excerpt($ep->ID))); ?>
      </p>

      <div class="mt-4 pt-3.5 border-t border-dashed border-border flex items-center justify-between">
        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-muted-foreground">پادکست صوتی</span>
        <a href="<?php echo esc_url(get_permalink($ep->ID)); ?>" class="inline-flex items-center gap-1 text-xs font-bold text-ink-700 hover:text-violet-700">
          شنیدن قسمت
          <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
        </a>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</section>
