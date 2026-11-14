<?php

if(!defined('ABSPATH')) exit;

$course_id = get_the_ID();
$lessons   = nasim_get_course_lessons($course_id);
$duration  = nasim_format_total_duration(nasim_course_total_seconds($course_id));
$level     = get_post_meta($course_id, '_nasim_course_level', true);
$meta      = array_filter([nasim_fa_digits(count($lessons)) . ' درس', $duration, $level]);
?>
<article class="program-card course-card">
  <a class="program-image" href="<?php the_permalink(); ?>">
    <?php if (has_post_thumbnail()) : ?>
      <?php the_post_thumbnail('medium_large', ['loading' => 'lazy', 'alt' => the_title_attribute(['echo' => false])]); ?>
    <?php else : ?>
      <span class="course-hero-placeholder"><span class="brand-mark">N</span></span>
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
