<?php

if (!defined('ABSPATH')) {
    exit;
}

$course_id = get_the_ID();
$lessons   = function_exists('nasim_get_course_lessons') ? nasim_get_course_lessons($course_id) : [];
$duration  = function_exists('nasim_format_total_duration') ? nasim_format_total_duration(nasim_course_total_seconds($course_id)) : '';
$level     = (string) get_post_meta($course_id, '_nasim_course_level', true);
$mode      = function_exists('nasim_course_access_mode') ? nasim_course_access_mode($course_id) : 'subscription';
$label     = function_exists('nasim_course_access_label') ? nasim_course_access_label($course_id) : '';
$badge     = function_exists('nasim_course_access_badge_class') ? nasim_course_access_badge_class($course_id) : '';
$cta       = function_exists('nasim_course_cta_label') ? nasim_course_cta_label($course_id) : 'مشاهده';
$buy_url   = function_exists('nasim_course_checkout_url') ? nasim_course_checkout_url($course_id) : get_permalink();
$thumb     = function_exists('nasim_niloo_course_image_url')
    ? nasim_niloo_course_image_url($course_id, 'medium_large')
    : (string) get_the_post_thumbnail_url($course_id, 'medium_large');
$price     = function_exists('nasim_course_catalog_price') ? nasim_course_catalog_price($course_id) : 0;
$terms     = get_the_terms($course_id, 'nasim_course_cat');
$meta_bits = array_filter([
    $lessons ? nasim_fa_digits(count($lessons)) . ' درس' : '',
    $duration,
    $level,
]);
$primary_url = ('free' === $mode) ? get_permalink() : $buy_url;
?>
<article class="ccard ccard--<?php echo esc_attr($mode); ?>">
  <a class="ccard-media" href="<?php the_permalink(); ?>">
    <?php if ($thumb) : ?>
    <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" decoding="async" width="400" height="400">
    <?php else : ?>
    <span class="ccard-fallback" aria-hidden="true">ن</span>
    <?php endif; ?>
    <?php if ($label) : ?>
    <span class="<?php echo esc_attr($badge); ?> ccard-badge"><?php echo esc_html($label); ?></span>
    <?php endif; ?>
  </a>

  <div class="ccard-body">
    <?php if ($terms && !is_wp_error($terms)) : ?>
    <div class="ccard-cats">
      <?php foreach (array_slice($terms, 0, 1) as $term) : ?>
      <span class="ccard-cat"><?php echo esc_html($term->name); ?></span>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <h3 class="ccard-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>

    <?php if (has_excerpt()) : ?>
    <p class="ccard-excerpt"><?php echo esc_html(wp_html_excerpt(get_the_excerpt(), 72, '…')); ?></p>
    <?php endif; ?>

    <?php if ($meta_bits) : ?>
    <ul class="ccard-meta">
      <?php foreach (array_slice($meta_bits, 0, 2) as $bit) : ?>
      <li><?php echo esc_html($bit); ?></li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <div class="ccard-foot">
      <div class="ccard-price">
        <?php if ('free' === $mode) : ?>
        <strong class="is-free">رایگان</strong>
        <?php elseif ($price > 0) : ?>
        <strong><?php echo esc_html(function_exists('nasim_format_price') ? nasim_format_price($price) : number_format($price) . ' تومان'); ?></strong>
        <?php else : ?>
        <strong>پولی</strong>
        <?php endif; ?>
      </div>
      <a class="button button-primary button-small ccard-cta" href="<?php echo esc_url($primary_url); ?>"><?php echo esc_html($cta); ?></a>
    </div>
  </div>
</article>
