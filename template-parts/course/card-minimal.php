<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Unified Course Card (Tailwind v4 design system)
 * Accepts $args['course'] (WP_Post) or current post, plus optional $args['delay'] (ms).
 */
$course = (isset($args['course']) && $args['course'] instanceof WP_Post) ? $args['course'] : get_post();
if (!$course) {
    return;
}

$cid       = (int) $course->ID;
$delay     = isset($args['delay']) ? (int) $args['delay'] : 0;
$url       = get_permalink($cid);
$thumb     = function_exists('nasim_niloo_course_image_url')
    ? nasim_niloo_course_image_url($cid, 'large')
    : get_the_post_thumbnail_url($cid, 'large');
$is_free   = function_exists('nasim_course_access_mode') && 'free' === nasim_course_access_mode($cid);
$owned     = is_user_logged_in() && function_exists('nasim_user_has_course_access') && nasim_user_has_course_access($cid);
$level     = trim((string) get_post_meta($cid, '_nasim_course_level', true));
$badge     = trim((string) get_post_meta($cid, '_nasim_course_badge', true));
$tagline   = trim((string) get_post_meta($cid, '_nasim_course_tagline', true)) ?: wp_strip_all_tags(get_the_excerpt($cid));
$projects  = trim((string) get_post_meta($cid, '_nasim_course_projects', true));
$students  = trim((string) get_post_meta($cid, '_nasim_course_students', true));
$dur_label = trim((string) get_post_meta($cid, '_nasim_course_duration_label', true));

if (!$dur_label && function_exists('nasim_course_total_seconds')) {
    $secs = nasim_course_total_seconds($cid);
    if ($secs > 0) {
        $dur_label = nasim_format_total_duration($secs);
    } else {
        $lessons_n = count(nasim_get_course_lessons($cid));
        if ($lessons_n > 0) {
            $dur_label = nasim_fa_digits($lessons_n) . ' جلسه';
        }
    }
}

$price = 0;
if (!$is_free) {
    $linked_plan = (int) get_post_meta($cid, '_nasim_linked_plan_id', true);
    if ($linked_plan) {
        $price = (int) get_post_meta($linked_plan, '_nasim_plan_price', true);
    } elseif (function_exists('nasim_course_catalog_price')) {
        $price = (int) nasim_course_catalog_price($cid);
    }
}

$terms     = wp_get_post_terms($cid, 'nasim_course_cat', ['fields' => 'slugs']);
$cat_slugs = is_array($terms) ? implode(',', $terms) : '';
?>
<a href="<?php echo esc_url($url); ?>"
   data-course-card
   data-cats="<?php echo esc_attr($cat_slugs); ?>"
   data-level="<?php echo esc_attr($level); ?>"
   data-free="<?php echo $is_free ? '1' : '0'; ?>"
   class="reveal group card-hover flex h-full flex-col rounded-3xl border border-border bg-card overflow-hidden"
   <?php echo $delay ? 'style="--reveal-delay:' . esc_attr($delay) . 'ms"' : ''; ?>>

  <div class="relative aspect-video overflow-hidden bg-ink-900">
    <?php if ($thumb) : ?>
    <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($course->post_title); ?>" width="1200" height="675" loading="lazy" decoding="async" class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-[1.04]" />
    <?php else : ?>
    <div class="absolute inset-0 dot-grid-light opacity-40" aria-hidden="true"></div>
    <div class="absolute inset-0 grid place-items-center text-cream/80 font-black text-xl px-6 text-center"><?php echo esc_html($course->post_title); ?></div>
    <?php endif; ?>

    <?php if ($badge) : ?>
    <span class="absolute top-3 start-3 rounded-full bg-ink-900/90 text-cream text-xs font-bold px-3 py-1.5 backdrop-blur"><?php echo esc_html($badge); ?></span>
    <?php elseif ($is_free) : ?>
    <span class="absolute top-3 start-3 rounded-full bg-mint-600 text-white text-xs font-black px-3 py-1.5">رایگان</span>
    <?php endif; ?>

    <?php if ($owned && !$is_free) : ?>
    <span class="absolute top-3 end-3 rounded-full bg-mint-100 text-mint-700 border border-mint-200 text-xs font-black px-3 py-1">در حساب شما</span>
    <?php endif; ?>
  </div>

  <div class="flex flex-col flex-1 p-5 md:p-6">
    <h3 class="text-lg md:text-xl font-extrabold leading-snug group-hover:text-ink-600 transition-colors">
      <?php echo esc_html($course->post_title); ?>
    </h3>

    <?php if ($tagline) : ?>
    <p class="mt-2 fa-line text-sm text-muted-foreground line-clamp-2"><?php echo esc_html($tagline); ?></p>
    <?php endif; ?>

    <div class="mt-4 flex flex-wrap items-center gap-2">
      <?php if ($level) : ?>
      <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold bg-violet-100 text-violet-800 border-violet-200"><?php echo esc_html($level); ?></span>
      <?php endif; ?>
      <?php if ($dur_label) : ?>
      <span class="inline-flex items-center gap-1.5 rounded-full border border-border bg-background px-2.5 py-1 text-xs font-semibold text-muted-foreground"><?php echo esc_html($dur_label); ?></span>
      <?php endif; ?>
      <?php if ($students) : ?>
      <span class="inline-flex items-center gap-1.5 rounded-full border border-violet-300 bg-violet-100 px-2.5 py-1 text-xs font-bold text-violet-800"><?php echo esc_html($students); ?></span>
      <?php endif; ?>
    </div>

    <?php if ($projects) : ?>
    <p class="mt-3 text-[13px] font-semibold text-ink-600"><?php echo esc_html($projects); ?></p>
    <?php endif; ?>

    <div class="mt-auto pt-5 flex items-center justify-between border-t border-dashed border-border">
      <?php if ($is_free) : ?>
      <span class="font-extrabold text-base text-mint-700">رایگان</span>
      <?php elseif ($price > 0) : ?>
      <span class="font-extrabold text-lg text-foreground"><?php echo esc_html(nasim_format_price($price)); ?></span>
      <?php else : ?>
      <span class="font-bold text-sm text-muted-foreground">مشاهده شرایط ثبت‌نام</span>
      <?php endif; ?>

      <span class="inline-flex items-center gap-1.5 text-sm font-bold text-ink-700 group-hover:gap-2.5 transition-all">
        <?php echo $owned ? 'ادامه دوره' : 'مشاهده جزئیات'; ?>
        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5m0 0 6 6m-6-6 6-6"/></svg>
      </span>
    </div>
  </div>
</a>
