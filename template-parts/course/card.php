<?php

if (!defined('ABSPATH')) {
    exit;
}

$course_id = get_the_ID();
$lessons   = nasim_get_course_lessons($course_id);
$duration  = nasim_format_total_duration(nasim_course_total_seconds($course_id));
$level     = get_post_meta($course_id, '_nasim_course_level', true);
$meta      = array_filter([nasim_fa_digits(count($lessons)) . ' درس', $duration, $level]);
$thumb     = function_exists('nasim_niloo_course_image_url')
    ? nasim_niloo_course_image_url($course_id, 'medium_large')
    : (string) get_the_post_thumbnail_url($course_id, 'medium_large');
$label     = function_exists('nasim_course_access_label') ? nasim_course_access_label($course_id) : '';
$badge     = function_exists('nasim_course_access_badge_class') ? nasim_course_access_badge_class($course_id) : '';
?>
<article class="program-card course-card">
  <a class="program-image" href="<?php the_permalink(); ?>">
    <?php if ($thumb) : ?>
      <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" decoding="async">
    <?php else : ?>
      <span class="course-hero-placeholder"><span class="brand-mark">ن</span></span>
    <?php endif; ?>
    <?php if ($label) : ?>
    <span class="<?php echo esc_attr($badge); ?> course-access-badge--overlay"><?php echo esc_html($label); ?></span>
    <?php endif; ?>
    <span class="image-label"><?php echo esc_html(implode(' · ', $meta)); ?></span>
    <span class="image-arrow">↙</span>
  </a>
  <div class="program-content">
    <div class="program-title-row"><span class="program-symbol">↗</span><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3></div>
    <?php if (has_excerpt()) : ?><p><?php echo esc_html(get_the_excerpt()); ?></p><?php endif; ?>
    <a class="text-link" href="<?php the_permalink(); ?>">مشاهده دوره <span>←</span></a>
  </div>
</article>
