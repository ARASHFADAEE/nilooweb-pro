<?php

if (!defined('ABSPATH')) {
    exit;
}

$eyebrow   = nasim_mod('testimonials_eyebrow') ?: 'نظرات و نتایج دانشجویان';
$title     = nasim_mod('testimonials_title') ?: 'موفقیت شما';
$highlight = nasim_mod('testimonials_title_highlight') ?: 'افتخار ماست';

$testimonials = get_posts([
    'post_type'      => 'nasim_testimonial',
    'post_status'    => 'publish',
    'posts_per_page' => 6,
    'orderby'        => ['menu_order' => 'ASC', 'date' => 'DESC'],
    'no_found_rows'  => true,
]);

if (!$testimonials) {
    return;
}
?>

<section id="testimonials" class="container-site py-16 md:py-20" aria-label="نتایج و نظرات دانشجویان">
  <div class="reveal text-center max-w-2xl mx-auto mb-12">
    <span class="inline-flex items-center gap-2 rounded-full bg-accent px-3.5 py-1.5 text-[13px] font-bold text-accent-foreground">
      <?php echo esc_html($eyebrow); ?>
    </span>
    <h2 class="mt-4 text-2xl md:text-3xl font-black">
      <?php echo esc_html($title); ?>
      <?php if ($highlight) : ?> <span class="mark-violet"><?php echo esc_html($highlight); ?></span><?php endif; ?>
    </h2>
  </div>

  <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5 md:gap-6">
    <?php foreach ($testimonials as $i => $item) :
        $role   = (string) get_post_meta($item->ID, '_nasim_testimonial_role', true) ?: 'دانشجوی آکادمی';
        $rating = max(1, min(5, (int) get_post_meta($item->ID, '_nasim_testimonial_rating', true) ?: 5));
        $quote  = wp_strip_all_tags($item->post_content);
        $name   = $item->post_title;
    ?>
    <article class="reveal flex flex-col rounded-3xl border border-border bg-card p-6 shadow-soft" style="--reveal-delay:<?php echo esc_attr(($i % 3) * 60); ?>ms">
      <div class="flex items-center justify-between gap-2 mb-4">
        <span class="text-violet-500 text-sm tracking-widest" aria-label="<?php echo esc_attr($rating . ' از ۵ ستاره'); ?>">
          <?php echo esc_html(str_repeat('★', $rating)); ?>
        </span>
        <svg class="size-6 text-violet-200" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9.5 7C6.5 8 5 10.4 5 13.5V17h4.5v-4.5H7.3c0-2 .9-3.3 2.7-4.2zm9 0c-3 1-4.5 3.4-4.5 6.5V17h4.5v-4.5h-2.2c0-2 .9-3.3 2.7-4.2z"/></svg>
      </div>

      <p class="fa-line text-[14px] text-foreground/85 flex-1"><?php echo esc_html($quote); ?></p>

      <div class="mt-5 pt-4 border-t border-dashed border-border flex items-center gap-3">
        <span class="grid place-items-center size-10 rounded-xl bg-ink-900 text-violet-300 font-black text-sm shrink-0">
          <?php echo esc_html(mb_substr($name, 0, 1)); ?>
        </span>
        <div class="min-w-0">
          <p class="text-sm font-extrabold truncate"><?php echo esc_html($name); ?></p>
          <p class="text-xs font-semibold text-muted-foreground truncate"><?php echo esc_html($role); ?></p>
        </div>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</section>
