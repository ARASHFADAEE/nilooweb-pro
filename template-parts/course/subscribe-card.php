<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Purchase CTA for users without course access.
 *
 * @var array $args { course_id?: int, variant?: string }
 */
$course_id = isset($args['course_id']) ? (int) $args['course_id'] : (int) get_the_ID();
$variant   = isset($args['variant']) ? sanitize_key((string) $args['variant']) : 'default';
$mode      = function_exists('nasim_course_access_mode') ? nasim_course_access_mode($course_id) : 'purchase';
$cta_label = function_exists('nasim_course_cta_label') ? nasim_course_cta_label($course_id) : 'خرید این دوره';
$plan_id   = (int) get_post_meta($course_id, '_nasim_linked_plan_id', true);
$plan      = $plan_id ? nasim_get_plan($plan_id, false) : null;

if ('free' === $mode) {
    return;
}

$title    = 'ثبت‌نام در دوره';
$text     = 'با یک‌بار پرداخت، دسترسی دائمی به همین دوره را می‌گیری.';
$features = $plan && !empty($plan['features']) ? $plan['features'] : [
    'دسترسی کامل به همه جلسات',
    'پشتیبانی آکادمی نیلوفر وفایی',
    $plan && !empty($plan['type']) && 'spot_course' === $plan['type'] ? 'توکن اسپات‌پلیر بعد از پرداخت' : 'مشاهده در پلیر دوره',
];
$price = $plan ? (int) $plan['price'] : 0;
$lessons = function_exists('nasim_get_course_lessons') ? nasim_get_course_lessons($course_id) : [];
$duration = function_exists('nasim_format_total_duration')
    ? nasim_format_total_duration(nasim_course_total_seconds($course_id))
    : '';
$curriculum = function_exists('nasim_get_curriculum') ? nasim_get_curriculum($course_id) : [];
?>
<div class="subscribe-card subscribe-card--<?php echo esc_attr($variant); ?>">
  <div class="subscribe-card-head">
    <span class="subscribe-card-icon">✦</span>
    <div>
      <h3><?php echo esc_html($title); ?></h3>
      <p><?php echo esc_html($text); ?></p>
    </div>
  </div>

  <?php if ($price > 0) : ?>
  <div class="subscribe-card-price">
    <strong><?php echo esc_html(nasim_format_price($price)); ?></strong>
    <small>پرداخت یک‌باره</small>
  </div>
  <?php endif; ?>

  <ul class="subscribe-card-meta">
    <?php if ($curriculum) : ?>
    <li><span>محتوا</span><b><?php echo esc_html(nasim_fa_digits(count($curriculum))); ?> سرفصل · <?php echo esc_html(nasim_fa_digits(count($lessons))); ?> جلسه</b></li>
    <?php endif; ?>
    <?php if ($duration) : ?>
    <li><span>مدت آموزش</span><b><?php echo esc_html($duration); ?></b></li>
    <?php endif; ?>
  </ul>

  <ul>
    <?php foreach ($features as $feature) : ?>
    <li><span>✓</span><?php echo esc_html($feature); ?></li>
    <?php endforeach; ?>
  </ul>

  <button type="button" class="button button-primary" data-express-open><?php echo esc_html($cta_label); ?> <span>←</span></button>
</div>
