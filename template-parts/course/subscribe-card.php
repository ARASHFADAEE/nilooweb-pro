<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Purchase CTA for users without course access.
 */
$course_id = isset($args['course_id']) ? (int) $args['course_id'] : (int) get_the_ID();
$mode      = function_exists('nasim_course_access_mode') ? nasim_course_access_mode($course_id) : 'purchase';
$cta_url   = function_exists('nasim_course_checkout_url') ? nasim_course_checkout_url($course_id) : get_permalink($course_id);
$cta_label = function_exists('nasim_course_cta_label') ? nasim_course_cta_label($course_id) : 'خرید این دوره';
$plan_id   = (int) get_post_meta($course_id, '_nasim_linked_plan_id', true);
$plan      = $plan_id ? nasim_get_plan($plan_id, false) : null;

if ('free' === $mode) {
    return;
}

$title    = 'خرید این دوره';
$text     = 'با یک‌بار پرداخت، دسترسی دائمی به همین دوره را می‌گیری.';
$features = $plan && $plan['features'] ? $plan['features'] : [
    'دسترسی دائمی به همین دوره',
    'پشتیبانی آکادمی',
    $plan && !empty($plan['type']) && 'spot_course' === $plan['type'] ? 'توکن اسپات‌پلیر بعد از پرداخت' : 'مشاهده در پلیر دوره',
];
$price = $plan ? (int) $plan['price'] : 0;
?>
<div class="subscribe-card">
  <span class="subscribe-card-icon">✦</span>
  <h3><?php echo esc_html($title); ?></h3>
  <p><?php echo esc_html($text); ?></p>

  <?php if ($price > 0) : ?>
  <div class="subscribe-card-price">
    <strong><?php echo esc_html(nasim_format_price($price)); ?></strong>
  </div>
  <?php endif; ?>

  <ul>
    <?php foreach ($features as $feature) : ?>
    <li><span>✓</span><?php echo esc_html($feature); ?></li>
    <?php endforeach; ?>
  </ul>

  <button type="button" class="button button-primary" data-express-open><?php echo esc_html($cta_label); ?> <span>←</span></button>

  <?php if (!is_user_logged_in()) : ?>
  <small class="subscribe-card-login">قبلاً خریده‌ای؟ <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>">وارد شو</a></small>
  <?php endif; ?>
</div>
